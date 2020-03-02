<?php


namespace beinmedia\payment\Services;
use beinmedia\payment\models\Tap;
use beinmedia\payment\Parameters\ChargeParam;
use beinmedia\payment\Parameters\Post;
use beinmedia\payment\Parameters\Source;
use beinmedia\payment\Parameters\Redirect;
use beinmedia\payment\Parameters\Phone;
use beinmedia\payment\Parameters\Client;
class TapGateway extends Curl implements PaymentInterface
{
        public function generatePaymentURL($data)
        {
            //create object to be converted to request body
            $chargeParam=new ChargeParam();

            $chargeParam->customer=new Client();
            $chargeParam->customer->phone=new Phone($data->countryCode,$data->phoneNumber);
            $chargeParam->customer->email=$data->email;
            $chargeParam->customer->first_name=$data->name;
            $chargeParam->description=$data->description;
            $chargeParam->amount=$data->amount;
            $chargeParam->source=new Source($data->paymentMethodId);
            $chargeParam->currency=$data->currency;

            if($data->paymentMethodId=='src_eg.fawry'){

                $chargeParam->post = new Post($data->postURL);

                //this is only for make tap return for me a payment url
                $chargeParam->redirect = new Redirect("https://beinmedia.com");
            }
            else {

                if ($data->postURL<>null) {
                    $chargeParam->post = new Post($data->postURL);;
                } else {
                    $chargeParam->redirect = new Redirect($data->returnURL);
                }

            }

            $data = json_encode($chargeParam);

            //use curl method to generate post request
            $result=$this->postCurl("https://api.tap.company/v2/charges",$data,env('TAP_API_KEY'));
            $err=$result->err;

            $response=$result->response;
            $response = json_decode($response, true);

            if ($err) {
                return "cURL Error #:" . $err;
            }
            else {
                //create new charge in the database
                //dd($response);
                $newCharge = new Tap();
                $newCharge->charge_id = $response['id'];
                $newCharge->amount = $response['amount'];
                $newCharge->currency = $response['currency'];
                $newCharge->status = $response['status'];
                $newCharge->description = $response['description'];
                $newCharge->source_id = $response['source']['id'];
                $newCharge->transaction_url = $response['transaction']['url'];
                $newCharge->transaction_created = $response['transaction']['created'];
                if($response['source']['id']=="src_eg.fawry"){
                    $newCharge->order_reference= $response['transaction']['order']['reference'];
                }
                $newCharge->save();

                //return payment url
                return $response['transaction']['url'];
            }
        }

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


        //not tested for any payment method
        public function isPaymentExecuted()
        {
            //check request method
            $requestMethod = $_SERVER['REQUEST_METHOD'];
            $chargeId=request('tap_id');

            //if redirectURl is given in the request body
            if ($requestMethod == 'GET') {

                //retrieve charge
                //result gives error
                $result=$this->getCurl("https://api.tap.company/v2/charges/$chargeId",env('TAP_API_KEY'));
                $jsonResponse=$result->response;
                $err=$result->err;
                $response = json_decode($jsonResponse, true);
                //dd($response);
                if ($err) {
                    return "cURL Error #:" . $err;
                }
                else {
                    $charge=Tap::where('charge_id',$chargeId)->first();

                    if($response['status']=='CAPTURED' or $response['status']=='APPROVED'){
                        //update charge entry in database
                        //dd($charge);
                        $charge->status="CAPTURED";
                        $charge->json=$jsonResponse;
                        $charge->payment_method=$response['source']['payment_method'];
                        $charge->save();

                        return true;
                    }
                    else
                        $charge->status = $response['status'];
                        $charge->json = $jsonResponse;
                        $charge->payment_method=$response['source']['payment_method'];
                        $charge->save();
                        return false;
                }
            }

            //not tested yet
            //if PostURl is given in the request body
            else {
                $hashString = request('header.hashstring');
                $id = request('charge.id');
                $amount = request('charge.amount');
                $currency = request('charge.currency');
                $gateway_reference = request('charge.reference.gateway');
                $payment_reference = request('charge.reference.payment');
                $status = request('charge.status');
                $created = request('charge.transaction.created');
                $SecretAPIKey = env('TAP_API_KEY', '');
                $toBeHashedString = 'x_id' . $id . 'x_amount' . $amount . 'x_currency' . $currency . 'x_gateway_reference' . $gateway_reference . 'x_payment_reference' . $payment_reference . 'x_status' . $status . 'x_created' . $created . '';
                $myHashString = hash_hmac('sha256', $toBeHashedString, $SecretAPIKey);
                if ($myHashString == request('header' . 'hashstring')) {
                    echo "Secure Post";
                    $savedCharge = Tap::where('charge_id', $id)->first();
                    $savedCharge->status = $status;
                    $savedCharge->save();

                    return $status;
                } else {
                    return "Insecure Post";

                }
            }
        }
}
