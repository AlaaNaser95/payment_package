<?php


namespace beinmedia\payment\Services;
use beinmedia\payment\models\Business;
use beinmedia\payment\models\File;
use beinmedia\payment\models\Tap;
use beinmedia\payment\Parameters\BusinessParam;
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

            if(!is_null($data->destination_id)){
                $chargeParam->destinations = new \stdClass();
                $destination = new \stdClass();
                $destination->id = $data->destination_id;
                $destination->amount = $data->amount;
                $destination->currency = $data->currency;
                $chargeParam->destinations->destination = [$destination];
            }

            if(($data->trackId)<>null){
                $meta=new \stdClass();
                $meta->track_id=$data->trackId;
                $chargeParam->metadata=$meta;
            }

            $url=$data->postURL;

            if($data->paymentMethodId=='src_eg.fawry'){

                //will set the post url to the pacakage route to process the payment once payed.
                //the package will send post curl request to the user defiened postURl
                //$chargeParam->post = new Post($data->postURL);
                if(env('FAWRY_TESTING_MODE'))
                    $chargeParam->post = new Post(env('FAWRY_TESTING_PUBLISHED_BASE_URL').'/fawry-check');
                else
                    //$chargeParam->post = new Post(url('/fawry-check'));
                    $chargeParam->post= new Post($url);

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
                //$newCharge->description = $response['description'];
                $newCharge->source_id = $response['source']['id'];
                $newCharge->track_id= $response['metadata']['track_id'];
                $newCharge->transaction_url = $response['transaction']['url'];
                $newCharge->transaction_created = $response['transaction']['created'];
                $newCharge->destination_id = !is_null($response['application'])? $response['application']['destination_id']:null;
                if($response['source']['id']=="src_eg.fawry"){
                    $newCharge->order_reference= $response['transaction']['order']['reference'];
                    $newCharge->post_url= $url;

                }
                $newCharge->save();

                //return payment url
                return (is_null($newCharge->order_reference)? $response['transaction']['url']:$response['transaction']['order']['reference']);
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
                    $returnResponse=new \stdClass();
                    $returnResponse->track_id=$charge->track_id;

                    if($response['status']=='CAPTURED' or $response['status']=='APPROVED'){

                        //update charge entry in database
                        $charge->status="CAPTURED";
                        $charge->json=$jsonResponse;
                        $charge->payment_method=$response['source']['payment_method'];
                        $charge->save();

                        $returnResponse->status=true;
                        return $returnResponse;
                    }
                    else
                        $charge->status = $response['status'];
                        $charge->json = $jsonResponse;
                        $charge->payment_method=$response['source']['payment_method'];
                        $charge->save();

                        $returnResponse->status=false;
                        return $returnResponse;
                }
            }

            //if PostURl is given in the request body
            else {

                $hashString = $_SERVER['HTTP_HASHSTRING'];
                $id = request('id');
                $amount = request('amount');
                $amount = number_format($amount,2);
                $currency = request('currency');
                $gateway_reference = request('reference.gateway');
                $payment_reference = request('reference.payment');
                $status = request('status');
                $created = request('transaction.created');
                $SecretAPIKey = env('TAP_API_KEY', '');
                $toBeHashedString = 'x_id' . $id . 'x_amount' . $amount . 'x_currency' . $currency . 'x_gateway_reference' . $gateway_reference . 'x_payment_reference' . $payment_reference . 'x_status' . $status . 'x_created' . $created . '';
                $myHashString = hash_hmac('sha256', $toBeHashedString, $SecretAPIKey);

                $savedCharge = $this->getPayment($id);

                $returnResponse= new \stdClass();
                $returnResponse->track_id= $savedCharge->track_id;
                $returnResponse->tap_id = $id;

                if ($myHashString == $hashString) {
                    echo "Secure Post";

                    $savedCharge->status = $status;
                    $savedCharge->save();
                    if ($savedCharge->status == "CAPTURED")
                        $returnResponse->status=true;
                    else
                        $returnResponse->status=false;
                    //$data='{"charge_id" : '.$id.' ,"status" : '.$status.'}';

                } else {
                    $returnResponse->status=false;
                }
                return $returnResponse;


            }
        }

////////////////////////////////////////////////////////////////////////////////////////////////////////////

        public function getPayment($charge_id){
            return Tap::where('charge_id',$charge_id)->first();
        }


/////////////////////////////////////Business && Destinations for multi-vendor/////////////////////////////////////////////////////////////////////

        public function getSectors(){
            // /opt/public_html/beinmedia/paymentGatwaysPackage/paymentGatwaysPackage/payment_package/packages/beinmedia/payment/src/sectors.json'
            return json_decode(file_get_contents('../sectors.json'), true);
        }

        public function createFile(){
            $newEpochTime = strtotime("+5 years", time());
            //$newEpochTime = DateTime::createFromFormat('U', $epoch)->add(new DateInterval('P5Y'))->format('U');
            //$data+=['name' => new \CurlFile($filePath, 'image/png', 'filename.png')];
            $result = $this->postCurl('https://api.tap.company/v2/files', request()->only(['file','purpose','title'] + ['file_link_create'=>true,'expires_at'=>$newEpochTime]) , env('TAP_API_KEY'), true);
            $jsonResponse=$result->response;
            $err=$result->err;
            $response = json_decode($jsonResponse, true);

            if ($err) {
                return "cURL Error #:" . $err;
            }
            else {
                $file = File::create(['file_id'=>$response['id'], 'url' => $response['url'], 'filename'=>$response['filename'], 'purpose'=>$response['purpose'], 'type'=>$response['type'], 'size'=> $response['size'], 'link_expires_at'=> $newEpochTime]);
            }

            return $file->file_id;
        }

        public function getFile($file_id){
            return File::where('file_id',$file_id)->get()->setHidden(['id','purpose','link_expires_at']);
        }

        public function createBusiness($data){
            //['business_name','type','business_legal_name','business_country','iban','contact_person','sector','website','documents'];
            $requestData = new BusinessParam();
            $name = new \stdClass();
            $name->en = $data->business_name;
            $requestData->name = $name;
            $requestData->type = $data->type ?? 'ind';
            $entity = new \stdClass();
            $entity->legal_name = new \stdClass();
            $entity->legal_name->en = $data->business_legal_name;
            $entity->country = $data->business_country;
            if(!empty($data->documents))
                $entity->documents = $data->documents;
            $entity->bank_account = new \stdClass();
            $entity->bank_account->iban = $data->iban;
            $requestData->entity=$entity;
            /*$contactPerson = new ContactPerson();
            $contactPerson->name = new \stdClass();
            $contactPerson->name->first = $data->contact_first_name;
            $contactPerson->name->last = $data->contact_last_name;
            $contactPerson->contact_info = new \stdClass();
            $contactPerson->contact_info->primary = new \stdClass();
            $contactPerson->contact_info->primary->email = $data->email;
            $contactPerson->contact_info->primary->phone = new Phone($data->country_code, $data->phone);
            $contactPerson->identification = $data->identification;
            $requestData->contact_person = $contactPerson;
            */
            $requestData->contact_person = $data->contact_person;
            $brand = new \stdClass();
            $brand->name = new \stdClass();
            $brand->name->en = $data->business_name;
            $brand->sector = [$data->sector];
            $brand->website = $data->website;
            $result = $this->postCurl("https://api.tap.company/v2/business",$data = json_encode($requestData),env('TAP_API_KEY'));
            $err=$result->err;

            $response=$result->response;
            $response = json_decode($response, true);

            if ($err) {
                return "cURL Error #:" . $err;
            }
            else {
                Business::create(['business_id'=>$response['id'],'entity_id'=>$response['entity']['id'],'name'=>$data->business_name,'type'=>$data->type,'destination_id'=> $response['destination_id']]);
                return response()->json(['business_id'=>$response['id'], 'entity_id'=>$response['entity']['id'], 'destination_id']);
            }
        }

        /*public function createDestination($data){
            $requestData = new DestinationParam();
            $requestData->display_name = $data->name;
            $requestData->business_id = $data->business_id;
            $requestData->business_entity_id = $data->business_entity_id;
            $requestData->bank_account = new \stdClass();
            $requestData->bank_account->iban = $data->iban;
            $result = $this->postCurl("https://api.tap.company/v2/business",$data = json_encode($requestData),env('TAP_API_KEY'));
            $err=$result->err;

            $response=$result->response;
            $response = json_decode($response, true);

            if ($err) {
                return "cURL Error #:" . $err;
            }
            else {
                $business = Business::create(['business_id'=>$response['id'],'entity_id'=>$response['entity']['id'],'name'=>$data->business_name,'type'=>$data->type]);
                return $response->id;
            }

        }*/

        public function getBusiness($business_id){
            return Business::where('business_id',$business_id)->first();
        }

        public function getTapBusiness($business_id){
            $result = $this->getCurl("https://api.tap.company/v2/business/$business_id",env('TAP_API_KEY'));
            $err=$result->err;

            $response=$result->response;
            $response = json_decode($response, true);

            if ($err) {
                return "cURL Error #:" . $err;
            }
            else {
                return $response;
            }
        }

        /*public function getDestination($destination_id){
            return Destination::where('destination_id',$destination_id)->first();
        }*/

        /*public function getBusinessDestinations($business_id){
            return Destination::where('business_id',$business_id);
        }*/

        /*public function getDestinationBusiness($destination_id){
            $destination = Destination::where('destination_id',$destination_id)->first();
            return Business::where('business_id',$destination->business_id);
        }*/

        public function getDestinationPaidTransactions($destination_id){
            return Tap::where('destination_id',$destination_id)->get();
        }

        /*public function getBusinessPaidTransactions($business_id){
            $destination_ids = Destination::where('business_id',$business_id)->pluck('destination_id')->toArray();
            return Tap::whereIn('destination_id',$destination_ids)->get();
        }*/

}


