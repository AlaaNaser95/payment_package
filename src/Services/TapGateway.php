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

            $url=$data->postURL;

            if($data->paymentMethodId=='src_eg.fawry'){

                //will set the post url to the pacakage route to process the payment once payed.
                //the package will send post curl request to the user defiened postURl
                //$chargeParam->post = new Post($data->postURL);
                if(env('FAWRY_TESTING_MODE'))
                    $chargeParam->post = new Post(env('FAWRY_TESTING_PUBLISHED_BASE_URL').'/fawry-check');
                else
                    $chargeParam->post = new Post(url('/fawry-check'));

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
                    $newCharge->post_url= $url;

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

                if ($err) {
                    return "cURL Error #:" . $err;
                }
                else {

                    $charge=$this->getPayment($chargeId);

                    if($response['status']=='CAPTURED' or $response['status']=='APPROVED'){

                        //update charge entry in database
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
                //$hashString = getallheaders();
                //$hashString = $hashString['hashstring'];
                $hashString = $_SERVER['HTTP_HASHSTRING'];
                $id = request('charge.id');
                $amount = request('charge.amount');
                $amount = number_format($amount,2);
                $currency = request('charge.currency');
                $gateway_reference = request('charge.reference.gateway');
                $payment_reference = request('charge.reference.payment');
                $status = request('charge.status');
                $created = request('charge.transaction.created');
                $SecretAPIKey = env('TAP_API_KEY', '');
                $toBeHashedString = 'x_id' . $id . 'x_amount' . $amount . 'x_currency' . $currency . 'x_gateway_reference' . $gateway_reference . 'x_payment_reference' . $payment_reference . 'x_status' . $status . 'x_created' . $created . '';
                $myHashString = hash_hmac('sha256', $toBeHashedString, $SecretAPIKey);
                if ($myHashString == $hashString) {
                    echo "Secure Post";
                    $savedCharge = $this->getPayment($id);
                    $savedCharge->status = $status;
                    $savedCharge->save();
                    $data='{"charge_id" : '.$id.' ,"status" : '.$status.'}';
                    try{
                        $post_url=$this->getPayment($id);
                        return $this->notifyUser($data,$post_url);
                    }catch(Exception $ex){
                        die($ex);
                    }
                    return $status;
                } else {
                    return "Insecure Post";

                }
            }
        }

////////////////////////////////////////////////////////////////////////////////////////////////////////////

        public function getPayment($charge_id){
            return Tap::where('charge_id',$charge_id)->first();
        }







}


