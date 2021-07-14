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
            \Log::error("Curl error while getting myfatoorah methods.\nError:\n" . json_encode($err));
            abort(500, 'Something went wrong while Getting payment methods');
        } else {
            if (isset($response["IsSuccess"]) && $response["IsSuccess"]) {
                return $response["Data"]["PaymentMethods"];
            } else {
                \Log::error("Validation errors while getting myfatoorah methods.\nData:\n$data\nErrors:\n" . json_encode($response));
                abort(500, 'Something went wrong while getting payment methods');
            }

        }

    }


    public function generatePaymentURL($paymentParameters){

        //create object to be converted to request body
        $data=new MyfatoorahParam();
        $data->PaymentMethodId=$paymentParameters->paymentMethodId;
        $data->InvoiceValue=$paymentParameters->amount;
        $data->CallBackUrl=$paymentParameters->returnURL;
        $data->ErrorUrl=$paymentParameters->cancelURL;
        if($paymentParameters->trackId!=null){
            $data->CustomerReference=$paymentParameters->trackId;
        }
        if(($paymentParameters->currency)<>null){
            $data->DisplayCurrencyIso=$paymentParameters->currency;
        }
        if(($paymentParameters->name)<>null){
            $data->CustomerName=$paymentParameters->name;
        }
        if(($paymentParameters->email)<>null){
            $data->CustomerEmail=$paymentParameters->email;
        }
        $data=json_encode($data);


        $result=$this->postCurl(($this->baseURL."/ExecutePayment"),$data,env('MYFATOORAH_API_KEY'));
        $response=$result->response;
        $err=$result->err;
        $response = json_decode($response, true);

        if ($err) {
            \Log::error("Curl error while generating myfatoorah payment url.\nError:\n" . json_encode($err));
            abort(500, 'Something went wrong while processing payment');
        } else {
            if (isset($response["IsSuccess"]) && $response["IsSuccess"]) {
                try {
                    //create new payment entry in the database
                    $payment = new MyFatoorah();
                    $payment->invoice_id = $response["Data"]["InvoiceId"];
                    $payment->payment_url = $response["Data"]["PaymentURL"];
                    $payment->customer_reference = $response["Data"]["CustomerReference"];
                    $data = json_decode($data);
                    $payment->payment_method_id = $data->PaymentMethodId;
                    $payment->customer_name = $data->CustomerName ?: null;
                    $payment->customer_email = $data->CustomerEmail ?: null;

                    $payment->save();

                    return $response["Data"]["PaymentURL"];
                }catch (\Exception $e){
                    \Log::error("Error while generating myfatoorah payment url.\nData:\n".json_encode($data)."\nResponse:\n".json_encode($response['data'])."\nErrors:\n" . json_encode($e));
                    abort(500, 'Something went wrong while processing payment');
                }

            }
            else {
                \Log::error("Validation errors while generating payment url.\nData:\n$data\nErrors:\n" . json_encode($response));
                abort(500, 'Something went wrong while processing payment');
            }
        }
    }

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    //tested for knet gateway
    //not working mastercard
    //tested for visa card
    public function isPaymentExecuted($paymentId=null){

        $data=new \stdClass();
        $data->KeyType=is_null($paymentId)?"PaymentId":"InvoiceId";
        $paymentId=$paymentId ?? request('paymentId');
        $data->Key="$paymentId";
        $data = json_encode($data);

        $result=$this->postCurl(($this->baseURL."/GetPaymentStatus"),$data,env('MYFATOORAH_API_KEY'));
        $response=$result->response;
        $err=$result->err;
        $responseData=$response;
        $response = json_decode($response, true);

        if ($err) {
            \Log::error("Curl error while gverifying myfatoorah payment.\nError:\n" . json_encode($err));
            abort(500, 'Something went wrong while verifying payment');
        } else {
            try {
                $payment = $this->getPayment($response["Data"]["InvoiceId"]);
                $status = $response["Data"]["InvoiceStatus"];
                $track_id = $response["Data"]["CustomerReference"];

                $returnResponse = new \stdClass();
                $returnResponse->track_id = $track_id;

                //update payment in database if paid
                if ($status == 'Paid') {

                    //get payment from database
                    $payment->invoice_status = $status;
                    $payment->currency = $response["Data"]["InvoiceTransactions"][0]["Currency"];
                    $payment->payment_method = $response["Data"]["InvoiceTransactions"][0]["PaymentGateway"];
                    $payment->payment_id = $response["Data"]["InvoiceTransactions"][0]["PaymentId"];
                    $payment->invoice_value = $response["Data"]["InvoiceTransactions"][0]["TransationValue"];
                    $payment->json = $responseData;
                    $payment->save();

                    $returnResponse->status = true;
                    return $returnResponse;
                }

                $returnResponse->status = false;
                return $returnResponse;
            }catch(\Exception $e){
                \Log::error("Error while generating myfatoorah payment url.\nData:\n$data\nErrors:\n" . json_encode($response));
                abort(500, 'Something went wrong while processing payment');
            }

        }

    }


    /////////////////////////////////////////////////////////////////////////////////////////////

    public function getPayment($invoice_Id){
        return MyFatoorah::where('invoice_id',$invoice_Id)->first();
    }
}
