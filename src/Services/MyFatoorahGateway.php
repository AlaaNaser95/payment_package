<?php


namespace beinmedia\payment\Services;
use beinmedia\payment\models\MyFatoorah;
use beinmedia\payment\Parameters\MyfatoorahParam;

class MyFatoorahGateway extends Curl implements \beinmedia\payment\Services\PaymentInterface
{

    //return all payment methods available for the account
    public function getMyFatoorahPaymentMethods($invoiceAmount,$currency){

        $data= new \stdClass();
        $data->InvoiceAmount=$invoiceAmount;
        $data->CurrencyIso=$currency;
        $data = json_encode($data);

        $result=$this->postCurl("https://apitest.myfatoorah.com/v2/InitiatePayment",$data,env('MYFATOORAH_API_KEY'));

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
        if($paymentParameters->currency<>null){
            $data->DisplayCurrencyIso=$paymentParameters->currency;
        }

        $data=json_encode($data);


        $result=$this->postCurl("https://apitest.myfatoorah.com/v2/ExecutePayment",$data,env('MYFATOORAH_API_KEY'));
        $response=$result->response;
        $err=$result->err;
        $response = json_decode($response, true);

        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            if ($response["IsSuccess"]) {

                session(['invoice_id'=>$response["Data"]["InvoiceId"]]);

                //create new payment entry in the database
                $payment = new MyFatoorah();
                $payment->invoice_id = $response["Data"]["InvoiceId"];
                $payment->payment_url = $response["Data"]["PaymentURL"];
                $payment->customer_reference = $response["Data"]["CustomerReference"];
                $data=json_decode($data);
                $payment->payment_method_id = $data->PaymentMethodId;
                $payment->invoice_value = $data->InvoiceValue;
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

        $invoiceId=session('invoice_id');
        $paymentId=request('paymentId');

        $data=new \stdClass();
        $data->Key="$paymentId";
        $data->KeyType="PaymentId";
        $data = json_encode($data);

        $result=$this->postCurl("https://apitest.myfatoorah.com/v2/GetPaymentStatus",$data,env('MYFATOORAH_API_KEY'));
        $response=$result->response;
        $err=$result->err;
        $responseData=$response;
        $response = json_decode($response, true);

        if ($err) {

            return "cURL Error #:" . $err;

        } else {

            $status=$response["Data"]["InvoiceStatus"];
            //update status in database
            $payment= MyFatoorah::where('invoice_id',$invoiceId)->first();
            $payment->invoice_status=$status;
            $payment->json=$responseData;
            $payment->save();

            if($status=='Paid'){
                return true;
            }

            return false;
        }

    }

}
