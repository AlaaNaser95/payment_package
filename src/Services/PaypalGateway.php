<?php


namespace beinmedia\payment\Services;
use PayPal\Api\Amount;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Transaction;
use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Api\PaymentExecution;
use beinmedia\payment\models\Paypal;

class PaypalGateway implements PaymentInterface
{
    private $apiContext;

    public function __construct(){
        $this->apiContext = new ApiContext(
            new OAuthTokenCredential(config('paypal.client_id'), config('paypal.secret')));
        $this->apiContext->setConfig(config('paypal.settings'));
    }

    public function generatePaymentURL($data)
    {
        $currency=$data->currency;
        //$total=($data->amount).'';
        $returnURL=$data->returnURL;
        $cancelURL=$data->cancelURL;

        $payer = new Payer();
        $payer->setPaymentMethod('paypal');

        $amount = new Amount();
        $amount->setTotal($data->amount);
        $amount->setCurrency($currency);

        $transaction = new Transaction();
        $transaction->setAmount($amount);

        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl($returnURL)
            ->setCancelUrl($cancelURL);

        $payment = new Payment();
        $payment->setIntent('sale')
            ->setPayer($payer)
            ->setTransactions(array($transaction))
            ->setRedirectUrls($redirectUrls);
        try {

            $payment->create($this->apiContext);

            $approvalURL=$payment->getApprovalLink();

            //create payment entry in database;
            $paypalPayment=new Paypal();
            $paypalPayment->payment_id=$payment->getId();
            $paypalPayment->state=$payment->getState();
            $paypalPayment->amount=$data->amount;
            $paypalPayment->type="paypal";
            $paypalPayment->currency=$currency;
            $paypalPayment->create_time=$payment->getCreateTime();
            $paypalPayment->approval_link=$payment->getApprovalLink();
            $paypalPayment->track_id=$data->trackId;
            $paypalPayment->save();

            return $approvalURL;
        }
        catch ( PayPalConnectionException $ex) {
            \Log::error("Error while generating paypal payment url.\nData:\n".json_encode($data)."\nPaypal Errors:\n" . json_encode($ex));
            abort(500, 'Something went wrong while processing payment');
        } catch (Exception $ex) {
            \Log::error("Error while generating paypal payment url.\nData:\n".json_encode($data)."\nErrors:\n" . json_encode($ex));
            abort(500, 'Something went wrong while processing payment');
        }

    }

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function isPaymentExecuted($paymentId=null){

        $checkPayment=is_null($paymentId);
        $paymentId=$paymentId ?? request('paymentId');

        //retrieve payment from database
        $internalPayment=$this->getPayment($paymentId);

        //retrieve payment from paypal
        $payment = Payment::get($paymentId, $this->apiContext);

        if($checkPayment){
            //add payer id to database
            $payerId = request('PayerID');
            $internalPayment->payer_id=$payerId;
            $internalPayment->save();

            // Execute payment with payer ID
            $execution = new PaymentExecution();
            $execution->setPayerId($payerId);
        }

        $returnResponse = new \stdClass();
        $returnResponse->track_id=$internalPayment->track_id;

        try {
            if($checkPayment){
                // Execute payment
                $result = $payment->execute($execution, $this->apiContext);
                $internalPayment->json=$result;
            }
            else{
                $result=$payment;
            }
            $internalPayment->state=$result->getState();
            $internalPayment->update_time=$result->getUpdateTime();
            $internalPayment->save();

            if ($result->getState() == 'approved')
                $returnResponse->status=true;
            else
                $returnResponse->status=false;
            return $returnResponse;
        } catch (PayPal\Exception\PayPalConnectionException $ex) {
            \Log::error("Error while verifying paypal payment url.\nData:\n".json_encode(request()->all())."\nPaypal Errors:\n" . json_encode($ex));
            abort(500, 'Something went wrong while processing payment');
        } catch (Exception $ex) {
            \Log::error("Error while generating paypal payment url.\nData:\n".json_encode(request()->all())."\nErrors:\n" . json_encode($ex));
            abort(500, 'Something went wrong while processing payment');
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////

    public function getPayment($payment_id){
        return Paypal::where('payment_id',$payment_id)->first();
    }
}
