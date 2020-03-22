<?php


namespace beinmedia\payment\Services;
use beinmedia\payment\models\MyFatoorah;
use beinmedia\payment\Parameters\MyfatoorahParam;

class MyFatoorahGateway extends Curl implements \beinmedia\payment\Services\PaymentInterface
{
    protected $baseURL;

    public function __construct()
    {
        if (env('MYFATOORAH_TEST_MODE')== true) {
            $this->baseURL = "https://apitest.myfatoorah.com/v2";
        } else {
            $this->baseURL = "https://api.myfatoorah.com/v2";
        }
    }


    //return all payment methods available for the account
    public function getMyFatoorahPaymentMethods($invoiceAmount,$currency){

        $data= new \stdClass();
        $data->InvoiceAmount=$invoiceAmount;
        $data->CurrencyIso=$currency;
        $data = json_encode($data);

        $result=$this->postCurl(($this->baseURL."/InitiatePayment"),$data,env('MYFATOORAH_API_KEY'));

        $response=$result->response;
        $err=$result->err;

        $response = json_decode($response, true);

        if ($err) {
            return "cURL Error #:" . $err;
        } else
            {
            if($response["IsSuccess"]){
                return $response["Data"]["PaymentMethods"];
            }
            else{
                return $response["ValidationErrors"];
            }

        }

    }


    public function generatePaymentURL($paymentParameters){

        //create object to be converted to request body
        $data=new MyfatoorahParam();
        $data->PaymentMethodId=$paymentParameters->PaymentMethodId;
        $data->InvoiceValue=$paymentParameters->amount;
        $data->CallBackUrl=$paymentParameters->returnURL;
        $data->ErrorUrl=$paymentParameters->cancelURL;
        if($paymentParameters->trackId!=null){
            $data->UserDefinedField=$paymentParameters->trackId;
        }
        if(($paymentParameters->currency)<>null){
            $data->DisplayCurrencyIso=$paymentParameters->currency;
        }
        $data=json_encode($data);


        $result=$this->postCurl(($this->baseURL."/ExecutePayment"),$data,env('MYFATOORAH_API_KEY'));
        $response=$result->response;
        $err=$result->err;
        $response = json_decode($response, true);

        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            if ($response["IsSuccess"]) {

                //create new payment entry in the database
                $payment = new MyFatoorah();
                $payment->invoice_id = $response["Data"]["InvoiceId"];
                $payment->payment_url = $response["Data"]["PaymentURL"];
                $payment->customer_reference = $response["Data"]["CustomerReference"];
                $data=json_decode($data);
                $payment->payment_method_id = $data->PaymentMethodId;

                $payment->save();

                return $response["Data"]["PaymentURL"];

            }
            else {
                return $response["ValidationErrors"];
            }
        }
    }

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    //tested for knet gateway
    //not working mastercard
    //tested for visa card
    public function isPaymentExecuted(){

        $paymentId=request('paymentId');

        $data=new \stdClass();
        $data->Key="$paymentId";
        $data->KeyType="PaymentId";
        $data = json_encode($data);

        $result=$this->postCurl(($this->baseURL."/GetPaymentStatus"),$data,env('MYFATOORAH_API_KEY'));
        $response=$result->response;
        $err=$result->err;
        $responseData=$response;
        $response = json_decode($response, true);

        if ($err) {

            return "cURL Error #:" . $err;

        } else {
            $payment = $this->getPayment($response["Data"]["InvoiceId"]);
            $status=$response["Data"]["InvoiceStatus"];
            $track_id=$response["Data"]["UserDefinedField"];

            $returnResponse=new \stdClass();
            $returnResponse->track_id= $track_id;

            //update payment in database if paid
            if($status=='Paid'){

                //get payment from database
                $payment->invoice_status = $status;
                $payment->currency = $response["Data"]["InvoiceTransactions"][0]["Currency"];
                $payment->payment_method = $response["Data"]["InvoiceTransactions"][0]["PaymentGateway"];
                $payment->payment_id = $response["Data"]["InvoiceTransactions"][0]["PaymentId"];
                $payment->invoice_value = $response["Data"]["InvoiceTransactions"][0]["TransationValue"];
                $payment->json = $responseData;
                $payment->track_id = $track_id;
                $payment->save();

                $returnResponse->status=true;
                return $returnResponse;
            }

            $returnResponse->status= false;
            return $returnResponse;

        }

    }


    /////////////////////////////////////////////////////////////////////////////////////////////

    public function getPayment($invoice_Id){
        return MyFatoorah::where('invoice_id',$invoice_Id)->first();
    }
}
