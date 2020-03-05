<?php


namespace beinmedia\payment\Services;

use beinmedia\payment\models\OurPlan;
use beinmedia\payment\models\Agreement;
use beinmedia\payment\models\Recurring;
use Carbon\Carbon;
use PayPal\Api\Currency;
use PayPal\Api\MerchantPreferences;
use PayPal\Api\PaymentDefinition;
use PayPal\Api\Plan;
use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\Patch;
use PayPal\Api\PatchRequest;
use PayPal\Common\PayPalModel;
use PayPal\Api\Payer;
use PayPal\Api\AgreementStateDescriptor;
use beinmedia\payment\Services\Curl;

class PaypalRecurring extends Curl
{
    private $apiContext;

    public function __construct()
    {
        $this->apiContext = new ApiContext(new OAuthTokenCredential(
            config('paypal.client_id'),             // ClientID
            config('paypal.secret')         // ClientSecret
        ));
        $this->apiContext->setConfig(config('paypal.settings'));

    }

////////////////////////////////////////////////////////////Plan////////////////////////////////////////////////////////


    public function createPlan($planParam)
    {
        /**
         * Create plan
         */
        $plan = new Plan();
        $plan->setName($planParam->planName)
            ->setDescription($planParam->description)
            ->setType('FIXED');//fixed

        $paymentDefinition = new PaymentDefinition();

        $paymentDefinition->setName('Regular Payments')//fixed
        ->setType('REGULAR')//fixed
        ->setFrequency('Month')//fixed
        ->setFrequencyInterval($planParam->interval)
            ->setCycles("100")//fixed
            ->setAmount(new Currency(array('value' => $planParam->amount, 'currency' => $planParam->currency)));

        $merchantPreferences = new MerchantPreferences();

        $merchantPreferences->setReturnUrl($planParam->returnURL . "?success=true")
            ->setCancelUrl($planParam->cancelURL . "?success=false")
            ->setMaxFailAttempts("0")
            ->setAutoBillAmount("yes"); //fixed

        $plan->setPaymentDefinitions(array($paymentDefinition));
        $plan->setMerchantPreferences($merchantPreferences);

        try {
            $createdPlan = $plan->create($this->apiContext);

            $activatedPlan = $this->updatePlan($createdPlan);

            OurPlan::create([
                'gateway' => 'paypal',
                'plan_id' => $createdPlan->getId(),
                'description' => $createdPlan->getDescription(),
                'name' => $createdPlan->getName(),
                'interval' => $paymentDefinition->getFrequencyInterval(),
                'json' => $createdPlan
            ]);

            return $activatedPlan;

        } catch (Exception $ex) {
            exit(1);
        }

    }


    protected function updatePlan($createdPlan)
    {
        try {
            $patch = new Patch();

            $value = new PayPalModel('{
	       "state":"ACTIVE"
	     }');

            $patch->setOp('replace')
                ->setPath('/')
                ->setValue($value);
            $patchRequest = new PatchRequest();
            $patchRequest->addPatch($patch);

            $createdPlan->update($patchRequest, $this->apiContext);

            $plan = Plan::get($createdPlan->getId(), $this->apiContext);
        } catch (Exception $ex) {
            exit(1);
        }
        return $plan;
    }


    public function listAllPlans()
    {
        return OurPlan::all();
    }

    public function getPlan($plan_id)
    {
        return Plan::get('plan_id', $plan_id)->first();
    }

////////////////////////////////////////////////////////////Agreement///////////////////////////////////////////////////


    /**
     * @param $plan_id
     * @return Agreement
     */

    public function createAgreement($plan_id, $name, $description)
    {
        session(['our_plan_id' => $plan_id]);

        $ourPlan = OurPlan::where('plan_id', $plan_id)->first();
        $ds = Carbon::now()->addMinutes(10)->toIso8601String();
        $ds = substr($ds, 0, 19);
        $ds = $ds . "Z";

        $agreement = new \PayPal\Api\Agreement();
        $agreement->setName($name)
            ->setDescription($description)
            ->setStartDate($ds);

        $plan = new Plan();
        $plan->setId($ourPlan->plan_id);

        $agreement->setPlan($plan);

        //Add Payer
        $payer = new Payer();
        $payer->setPaymentMethod('paypal');
        $agreement->setPayer($payer);

        try {
            $createdAgreement = $agreement->create($this->apiContext);
            $approvalUrl = $createdAgreement->getApprovalLink();

            session(['url' => $approvalUrl]);

            return $approvalUrl;
        } catch (Exception $ex) {
            exit(1);
        }
    }


    //this will be added to the redirectURL
    public function executeAgreement()
    {
        if (isset($_GET['success']) && $_GET['success'] == 'true') {
            $token = $_GET['token'];
            $agreement = new \PayPal\Api\Agreement();

            try {
                // Execute agreement
                $agreement->execute($token, $this->apiContext);
                $created_agreement = $this->getAgreement($agreement->getId());
                $agreementId = $created_agreement->getId();

                $agreementDetails = $created_agreement->getAgreementDetails();

                Agreement::create([
                    'gateway' => 'paypal',
                    'agreement_id' => $agreementId,
                    'approval_link' => session('url'),
                    'description' => $created_agreement->getDescription(),
                    'plan_id' => session('our_plan_id'),
                    'cycles_remaining' => $agreementDetails->getCyclesRemaining(),
                    'cycles_completed' => $agreementDetails->getCyclesCompleted(),
                    'next_billing_date' => $agreementDetails->getNextBillingDate(),
                    'last_payment_date' => $agreementDetails->getLastPaymentDate()
                ]);
                return true;
            } catch (PayPalConnectionException $ex) {
                \Log::info(json_encode($ex));
                echo $ex->getCode();
                echo $ex->getData();
                die($ex);
            } catch (Exception $ex) {
                \Log::info(json_encode($ex));
                die($ex);
            }
        } else {
            return false;
        }
    }


    public function getAgreement($id)
    {
        try {
            $agreement = \PayPal\Api\Agreement::get($id, $this->apiContext);
        } catch (Exception $ex) {
            exit(1);
        }
        return $agreement;
    }

    public function checkAgreementPayed($agreement_id)
    {

        $agreement = Agreement::where('agreement_id', $agreement_id)->first();
        $last_payment_date = $agreement->next_payment_date;

        if ($last_payment_date == null) {
            return false;
        }

        $last_payment_date = Carbon::createFromTimeString($agreement->next_subscription);

        if ($last_payment_date > Carbon::now()->subMonth(1)->toDateTimeString()) {
            return true;
        }
        return false;
    }

    public function cancelAgreement($agreementId)
    {

        $agreementStateDescriptor = new AgreementStateDescriptor();
        $agreementStateDescriptor->setNote("Suspending the agreement");

        $createdAgreement = $this->getAgreement($agreementId);
        /** @var Agreement $createdAgreement */

        try {

            $createdAgreement->suspend($agreementStateDescriptor, $this->apiContext);

            $agreement = $this->getAgreement($agreementId);

            $ourAgreement = Agreement::where('agreement_id', $agreementId)->first();
            $ourAgreement->state = $agreement->getState();

        } catch (Exception $ex) {
            exit(1);
        }

        return $agreement;
    }

    //not tested
    public function getActiveAgreements()
    {
        return Agreement::where('state', 'active');
    }

    //not tested
    public function getSuspendedAgreements()
    {
        return Agreement::where('state', 'Suspended');
    }


////////////////////////////////////////////////////////webhooks////////////////////////////////////////////////////////

    public function createWebhook()
    {

        $webhook = new \PayPal\Api\Webhook();
        if (env('PAYPAL_RECURRING_TESTING_MODE')) {
            $webhook->setUrl(env('PAYPAL_RECURRING_TESTING_WEBHOOK_URL') . '/webhookresponse');
        } else {
            $webhook->setUrl(url('/webhookresponse'));
        }
        $webhookEventTypes = array();

        //will get notified once the agreement is cancelled
        $webhookEventTypes[] = new \PayPal\Api\WebhookEventType(
            '{
                "name":"BILLING.SUBSCRIPTION.SUSPENDED"
            }'
        );

        //will get notified once the agreement is cancelled
        $webhookEventTypes[] = new \PayPal\Api\WebhookEventType(
            '{
                "name":"BILLING.SUBSCRIPTION.CANCELLED"
            }'
        );

        //will get notified once the billing payment is done
        $webhookEventTypes[] = new \PayPal\Api\WebhookEventType(
            '{
                "name":"PAYMENT.SALE.COMPLETED"
            }'
        );

        $webhook->setEventTypes($webhookEventTypes);
        try {
            $output = $webhook->create($this->apiContext);
            return $output;
        } catch (Exception $ex) {
            exit(1);
        }

    }


    public function handleWebHook()
    {

        $data = request('resource');

        $event_type=request('event_type');

        if ($event_type == 'PAYMENT.SALE.COMPLETED') {

            $agreementId = $data['billing_agreement_id'];
            $agreement = $this->getAgreement($agreementId);

            $agreementDetails = $agreement->getAgreementDetails();



            //save the payment in database
            $recurring = new Recurring();
            $recurring->state = $data['state'];
            $recurring->pay_id = $data['id'];
            $recurring->agreement_id = $data['billing_agreement_id'];
            $recurring->payment_date = $data['create_time'];
            $recurring->amount = $data['amount']['total'];
            $recurring->currency= $data['amount']['currency'];
            $recurring->save();

            //update agreement in database
            $ourAgreement = Agreement::where('agreement_id', $agreementId)->first();
            $ourAgreement->next_billing_date = $agreementDetails->getNextBillingDate();
            $ourAgreement->last_payment_date = $agreementDetails->getLastPaymentDate();
            $ourAgreement->cycles_remaining = $agreementDetails->getCyclesRemaining();
            $ourAgreement->cycles_completed = $agreementDetails->getCyclesCompleted();
            $ourAgreement->save();

            $data='{"event_type" : '.$event_type.' ,"agreement_id" : '.$agreementId.', "payment_id" : '.$data['id'].'}';


        }

        if ( ( $event_type == 'BILLING.SUBSCRIPTION.CANCELLED' ) || ( $event_type == 'BILLING.SUBSCRIPTION.SUSPENDED' ) ) {

            $agreementId = $data['id'];
            $agreement = $this->getAgreement($agreementId);

            //update agreement state in databse
            $ourAgreement = Agreement::where('agreement_id', $agreementId)->first();
            $ourAgreement->state = $agreement->getState();
            $ourAgreement->save();

            $data='{"event_type" : '.$event_type.' ,"agreement_id" : '.$agreementId.'}';

        }

        $url=env('RECURRING_NOTIFICATION_URL','');

        try {
            $this->notifyUser($data,$url);
        }
        catch(Exception $ex){

            die($ex);

        }
    }


}
