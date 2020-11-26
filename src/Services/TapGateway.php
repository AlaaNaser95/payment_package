<?php


namespace beinmedia\payment\Services;

use beinmedia\payment\models\Subscription;
use beinmedia\payment\models\Business;
use beinmedia\payment\models\File;
use beinmedia\payment\models\Tap;
use beinmedia\payment\Parameters\BusinessParam;
use beinmedia\payment\Parameters\BusinessParameters;
use beinmedia\payment\Parameters\ChargeParam;
use beinmedia\payment\Parameters\FileParameters;
use beinmedia\payment\Parameters\Post;
use beinmedia\payment\Parameters\Source;
use beinmedia\payment\Parameters\Redirect;
use beinmedia\payment\Parameters\Phone;
use beinmedia\payment\Parameters\Client;

class TapGateway extends Curl implements PaymentInterface
{
    public function ChargeCard($data)
    {
        try {
            $returned = $this->processCharge($data);
            $result = new \stdClass();

            if (isset($returned->errors)) {
                $result->errors = $returned->errors;
            } else {
                $result->url = array_key_exists('url', $returned->response['transaction']) ? $returned->response['transaction']['url'] : null;
                $result->customer_id = array_key_exists('id', $customer = $returned->response['customer']) ? $customer['id'] : null;
                $result->card_id = array_key_exists('card', $returned->response)  && array_key_exists('id', $returned->response['card'])? $returned->response['card']['id'] : null;
                $result->charge_id = $returned->newCharge->charge_id;
                $result->status = $returned->response['status'] == 'CAPTURED' || $returned->response['status'] == 'APPROVED';
            }
            return $result;
        } catch (\Exception $e) {
            return "Something went wrong";
        }
    }

    public function processCharge($data)
    {
        //create object to be converted to request body
        $chargeParam = new ChargeParam();
        $chargeParam->customer = new Client();
        $chargeParam->customer->phone = new Phone($data->countryCode, $data->phoneNumber);
        $chargeParam->customer->email = $data->email;
        $chargeParam->customer->first_name = $data->name;
        $chargeParam->description = $data->description;
        $chargeParam->amount = $data->amount;
        $chargeParam->save_card = true;
        $chargeParam->source = new Source($data->paymentMethodId);
        $chargeParam->currency = $data->currency;

        if (($data->trackId) <> null) {
            $meta = new \stdClass();
            $meta->track_id = $data->trackId;
            $chargeParam->metadata = $meta;
        }

        if (!is_null($data->destination_id)) {
            $chargeParam->destinations = new \stdClass();
            $destination = new \stdClass();
            $destination->id = $data->destination_id;
            $destination->amount = $data->transfer_amount;
            $destination->currency = $data->currency;
            $chargeParam->destinations->destination = [$destination];
        }

        $url = $data->postURL;

        if ($data->paymentMethodId == 'src_eg.fawry') {

            //will set the post url to the pacakage route to process the payment once payed.
            //the package will send post curl request to the user defiened postURl
            //$chargeParam->post = new Post($data->postURL);
            if (env('FAWRY_TESTING_MODE'))
                $chargeParam->post = new Post(env('FAWRY_TESTING_PUBLISHED_BASE_URL') . '/fawry-check');
            else
                //$chargeParam->post = new Post(url('/fawry-check'));
                $chargeParam->post = new Post($url);

            //this is only for make tap return for me a payment url
            $chargeParam->redirect = new Redirect("https://beinmedia.com");
        } else {

            if ($data->postURL <> null) {
                $chargeParam->post = new Post($data->postURL);;
            } else {
                $chargeParam->redirect = new Redirect($data->returnURL);
            }

        }

        $data = json_encode($chargeParam);

        //use curl method to generate post request
        $result = $this->postCurl("https://api.tap.company/v2/charges", $data, env('TAP_API_KEY'));
        $err = $result->err;

        $response = $result->response;
        $response = json_decode($response, true);
        $returned = new \stdClass();
        if ($err || array_key_exists('errors', $response)) {
            $returned->errors = empty($err) ? $response['errors'] : $err;
            return $returned;
        } else {

            //create new charge in the database
            $newCharge = new Tap();

            $newCharge->charge_id = $response['id'];
            $newCharge->amount = $response['amount'];
            $newCharge->currency = $response['currency'];
            $newCharge->status = $response['status'];
            //$newCharge->description = $response['description'];
            $newCharge->source_id = $response['source']['id'];
            $newCharge->track_id = $response['metadata']['track_id'];
            $newCharge->transaction_url = array_key_exists('url', $response['transaction']) ? $response['transaction']['url'] : 'token method';
            $newCharge->transaction_created = $response['transaction']['created'];
            if (array_key_exists('destinations', $response)) {
                $newCharge->destination_id = $response['destinations']['destination'][0]['id'];
                $newCharge->transfer_amount = $response['destinations']['destination'][0]['amount'];
            }
            if (array_key_exists('id', $response['customer']))
                $newCharge->customer_id = $response['customer']['id'];
            if (array_key_exists('card', $response) && array_key_exists('id', $response['card']))
                $newCharge->card_id = $response['card']['id'];
            if ($response['source']['id'] == "src_eg.fawry") {
                $newCharge->order_reference = $response['transaction']['order']['reference'];
                $newCharge->post_url = $url;

            }
            $newCharge->save();

            $returned->newCharge = $newCharge;
            $returned->response = $response;
            return $returned;
        }
    }

    public function generatePaymentURL($data)
    {
        $result = $this->processCharge($data);
        //return payment url
        if (isset($result->errors)) {
            return $result->errors;
        }
        return (is_null($result->newCharge->order_reference) ? $result->response['transaction']['url'] : $result->response['transaction']['order']['reference']);

    }

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


    public function isPaymentExecuted()
    {
        //check request method
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        $chargeId = request('tap_id');
        if (is_null($chargeId)) {
            $chargeId = request('charge.id');
        }

        //if redirectURl is given in the request body
        if ($requestMethod == 'GET' || !empty(request('charge.id'))) {

            //retrieve charge
            //result gives error
            $result = $this->getCurl("https://api.tap.company/v2/charges/$chargeId", env('TAP_API_KEY'));
            $jsonResponse = $result->response;
            $err = $result->err;
            $response = json_decode($jsonResponse, true);

            if ($err) {
                return "cURL Error #:" . $err;
            } else {

                $charge = $this->getPayment($chargeId);
                $returnResponse = new \stdClass();
                $returnResponse->track_id = $charge->track_id;
                $returnResponse->card_id = $charge->card_id;
                $returnResponse->customer_id = $charge->customer_id;
                if ($response['status'] == 'CAPTURED' or $response['status'] == 'APPROVED') {

                    //update charge entry in database
                    $charge->status = "CAPTURED";
                    $charge->json = $jsonResponse;
                    $charge->payment_method = $response['source']['payment_method'];
                    $charge->card_id = (array_key_exists('card',$response) && array_key_exists('id',$response['card']))?$response['card']['id']: null;
                    $charge->customer_id = array_key_exists('id', $customer = $response['customer']) ? $customer['id'] : null;
                    $charge->save();
                    $returnResponse->card_id = $charge->card_id;
                    $returnResponse->customer_id = $charge->customer_id;

                    $returnResponse->status = true;
                    return $returnResponse;
                } else
                    $charge->status = $response['status'];
                $charge->json = $jsonResponse;
                $charge->payment_method = $response['source']['payment_method'];
                $charge->save();

                $returnResponse->status = false;
                return $returnResponse;
            }
        } //if PostURl is given in the request body
        else {

            $hashString = $_SERVER['HTTP_HASHSTRING'];
            $id = request('id');
            $amount = request('amount');
            $amount = number_format($amount, 2);
            $currency = request('currency');
            $gateway_reference = request('reference.gateway');
            $payment_reference = request('reference.payment');
            $status = request('status');
            $created = request('transaction.created');
            $SecretAPIKey = env('TAP_API_KEY', '');
            $toBeHashedString = 'x_id' . $id . 'x_amount' . $amount . 'x_currency' . $currency . 'x_gateway_reference' . $gateway_reference . 'x_payment_reference' . $payment_reference . 'x_status' . $status . 'x_created' . $created . '';
            $myHashString = hash_hmac('sha256', $toBeHashedString, $SecretAPIKey);

            $savedCharge = $this->getPayment($id);

            $returnResponse = new \stdClass();
            $returnResponse->track_id = $savedCharge->track_id;
            $returnResponse->tap_id = $id;

            if ($myHashString == $hashString) {
                echo "Secure Post";

                $savedCharge->status = $status;
                $savedCharge->save();
                if ($savedCharge->status == "CAPTURED")
                    $returnResponse->status = true;
                else
                    $returnResponse->status = false;
                //$data='{"charge_id" : '.$id.' ,"status" : '.$status.'}';

            } else {
                $returnResponse->status = false;
            }
            return $returnResponse;


        }
    }

////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function getPayment($charge_id)
    {
        return Tap::where('charge_id', $charge_id)->first();
    }


/////////////////////////////////////Business && Destinations for multi-vendor/////////////////////////////////////////////////////////////////////

    public function getSectors()
    {
        return json_decode(file_get_contents(__DIR__ . '/../sectors.json'), true);
    }

    public function createFile(FileParameters $data)
    {
        try {
            $response = $this->filePostCurl($data, env('TAP_MARKETPLACE_API_KEY'));
            $file = File::create(['file_id' => $response['id'], 'url' => 'https://api.tap.company/v2' . $response['url'], 'internal_url' => $data->file, 'filename' => $response['filename'], 'purpose' => $response['purpose'], 'type' => $response['type'], 'size' => $response['size'], 'link_expires_at' => $data->expires_at]);
            return $file->file_id;
        } catch (\Exception $e) {
            return $e;
        }
    }

    public function getFile($file_id)
    {
        $file = File::where('file_id', $file_id)->first();
        return empty($file) ? $file : $file->makeHidden(['id', 'purpose', 'link_expires_at']);
    }

    public function createBusiness(BusinessParameters $data)
    {
        $requestData = new BusinessParam();
        $name = new \stdClass();
        $name->en = $data->business_name;
        $requestData->name = $name;
        $requestData->type = $data->type ?? 'ind';
        $entity = new \stdClass();
        $entity->legal_name = new \stdClass();
        $entity->legal_name->en = $data->business_legal_name;
        $entity->country = $data->business_country;
        if (!empty($data->documents))
            $entity->documents = $data->documents;
        $entity->bank_account = new \stdClass();
        $entity->bank_account->iban = $data->iban;
        $requestData->entity = $entity;
        $requestData->contact_person = $data->contact_person;
        $brand = new \stdClass();
        $brand->name = new \stdClass();
        $brand->name->en = $data->business_name;
        $brand->sector = $data->sector;
        $brand->website = $data->website;
        $requestData->brands = [$brand];
        $result = $this->postCurl("https://api.tap.company/v2/business", json_encode($requestData), env('TAP_MARKETPLACE_API_KEY'));
        $err = $result->err;

        $response = $result->response;
        $response = json_decode($response, true);


        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            Business::create(['business_id' => $response['id'], 'entity_id' => $response['entity']['id'], 'name' => $data->business_name, 'type' => $data->type, 'destination_id' => $response['destination_id'], 'iban' => $data->iban]);
            return ['business_id' => $response['id'], 'entity_id' => $response['entity']['id'], 'destination_id' => $response['destination_id']];
        }
    }

    public function getBusiness($business_id)
    {
        //getBusiness object from internal database
        return Business::where('business_id', $business_id)->first();
    }

    public function getTapBusiness($business_id)
    {
        //get business object from tap
        $result = $this->getCurl("https://api.tap.company/v2/business/$business_id", env('TAP_MARKETPLACE_API_KEY'));
        $err = $result->err;

        $response = $result->response;
        $response = json_decode($response, true);

        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            return $response;
        }
    }

    public function getDestinationPaidTransactions($destination_id)
    {
        return Tap::where('destination_id', $destination_id)->where('status', 'CAPTURED')->get();
    }

/////////////////////////////////////////Customr and Card///////////////

    public function createCustomer(Client $data)
    {
        $data = json_encode($data);

        //use curl method to generate post request
        $result = $this->postCurl("https://api.tap.company/v2/customers", $data, env('TAP_API_KEY'));
        $err = $result->err;
        $response = $result->response;
        $response = json_decode($response, true);
        $returned = new \stdClass();
        if ($err || array_key_exists('errors', $response)) {
            $returned->errors = empty($err) ? $response['errors'] : $err;
            return $returned;
        } else {
            $returned->custumer_id = $response['id'];
            return $returned;
        }
    }

    public function createCard($token_id, $customer_id)
    {
        $data = new \stdClass();
        $data->source = $token_id;
        $data = json_encode($data);
        $result = $this->postCurl("https://api.tap.company/v2/card/$customer_id", $data, env('TAP_API_KEY'));
        $err = $result->err;

        $response = $result->response;
        $response = json_decode($response, true);

        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            return $response['id'];
        }
    }

    public function getCustomer($customer_id)
    {
        $result = $this->getCurl("https://api.tap.company/v2/customers/$customer_id", env('TAP_API_KEY'));
        $jsonResponse = $result->response;
        $err = $result->err;
        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            return json_decode($jsonResponse, true);
        }
    }

    public function getCard($customer_id, $card_id)
    {
        $result = $this->getCurl("https://api.tap.company/v2/card/$customer_id/$card_id", env('TAP_API_KEY'));
        $jsonResponse = $result->response;
        $err = $result->err;
        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            return json_decode($jsonResponse, true);
        }
    }

////////////////////////////////////Subscriptions///////////////////////////////////

    public function createSubscription($data)
    {
        $data = json_encode($data);
        $result = $this->postCurl("https://api.tap.company/v2/subscription/v1/", $data, env('TAP_API_KEY'));
        $err = $result->err;

        $response = $result->response;
        $response = json_decode($response, true);
        $returned = new \stdClass();
        if ($err || !array_key_exists('id', $response)) {
            $returned->errors = empty($err) ? $response : $err;
        } else {
            Subscription::create(['subscription_id' => $response['id'], 'card_id' => $response['charge']['source']['id'], 'customer_id' => $response['charge']['customer']['id'], 'interval' => $response['term']['interval'], 'from' => $response['term']['from'], 'timezone' => $response['term']['timezone'], 'amount' => $response['charge']['amount'], 'currency' => $response['charge']['currency'], 'description' => $response['charge']['description'], 'track_id' => $response['charge']['metadata']['track_id'], 'status' => ($response['status'] == 'ACTIVE')]);
            $returned->id = $response['id'];
            $returned->status = $response['status'];
        }
        return $returned;

    }

    public function cancelSubscription($subscription_id)
    {
        $result = $this->deleteCurl("https://api.tap.company/v2/subscription/v1/" . $subscription_id, env('TAP_API_KEY'));
        $err = $result->err;

        $response = $result->response;
        $response = json_decode($response, true);
        $returned = new \stdClass();

        if ($err || !array_key_exists('id', $response)) {
            $returned->errors = empty($err) ? (reset($response)[0]) : $err;
        } else {
            Subscription::where('subscription_id', $subscription_id)->update(['status' => false]);
            $returned->id = $response['id'];
            $returned->status = $response['status'];
        }
        return $returned;
    }

    public function getSubscription($subscription_id)
    {
        return Subscription::where('subscription_id', $subscription_id)->first();
    }

    public function getTapSubscription($subscription_id)
    {
        $result = $this->getCurl("https://api.tap.company/v2/subscription/v1/$subscription_id", env('TAP_API_KEY'));
        $jsonResponse = $result->response;
        $err = $result->err;
        if ($err) {
            return "cURL Error #:" . $err;
        } else {
            return json_decode($jsonResponse, true);
        }
    }

    public function getActiveSubscriptions()
    {
        return Subscription::where('status', true)->get();
    }

    public function getInActiveSubscriptions()
    {
        return Subscription::where('status', false)->get();
    }

    public function getAllSubscriptions()
    {
        return Subscription::all();
    }


    public function verifySubscriptionPayment()
    {

        $payment = Tap::create(['charge_id' => request('charge.id'), 'amount' => request('charge.amount'), 'currency' => request('charge.currency'), 'status' => request('charge.status'), 'track_id' => request('metadata.track_id'), 'source_id' => request('charge.source.id'), 'transaction_created' => request('charge.transaction.created'), 'customer_id' => request('charge.customer.id'), 'card_id' => request('charge.source.id'), 'subscription_id' => request('id')]);
        $result = new \stdClass();
        $result->customer_id = $payment->customer_id;
        $result->card_id = $payment->card_id;
        $result->status = $payment->status;
        $result->subscription_id = $payment->subscription_id;
        return $result;
    }

    public function getSubscriptionPayments($subscription_id)
    {
        return Tap::where('subscription_id', $subscription_id)->get();
    }

    public function getFailedSubscriptionPayments($subscription_id)
    {
        return Tap::where('subscription_id', $subscription_id)->where('status', '!=', 'CAPTURED')->get();
    }

    public function getCapturedSubscriptionPayments($subscription_id)
    {
        return Tap::where('subscription_id', $subscription_id)->where('status', 'CAPTURED')->get();
    }
}


