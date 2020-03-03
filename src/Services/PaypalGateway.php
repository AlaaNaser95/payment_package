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
        $total=($data->amount).'';
        $returnURL=$data->returnURL;
        $cancelURL=$data->cancelURL;

        $payer = new Payer();
        $payer->setPaymentMethod('paypal');

        $amount = new Amount();
        $amount->setTotal(10);
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
            $paypalPayment->amount=$total;
            $paypalPayment->type="paypal";
            $paypalPayment->currency=$currency;
            $paypalPayment->create_time=$payment->getCreateTime();
            $paypalPayment->approval_link=$payment->getApprovalLink();
            $paypalPayment->save();

            return $approvalURL;
        }
        catch ( PayPalConnectionException $ex) {
            echo $ex->getCode(); // Prints the Error Code
            echo $ex->getData(); // Prints the detailed error message
            die($ex);
        } catch (Exception $ex) {
        }

    }

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

    public function isPaymentExecuted(){

        $paymentId=request('paymentId');

        //retrieve payment from database
        $internalPayment=$this->getPayment($paymentId);
        $payerId = request('PayerID');

        //retrieve payment from paypal
        $payment = Payment::get($paymentId, $this->apiContext);

        //add payer id to database
        $internalPayment->payer_id=$payerId;
        $internalPayment->save();

        // Execute payment with payer ID
        $execution = new PaymentExecution();
        $execution->setPayerId($payerId);

        try {
            // Execute payment
            $result = $payment->execute($execution, $this->apiContext);
            $internalPayment->state=$result->getState();
            $internalPayment->update_time=$result->getUpdateTime();
            $internalPayment->json=$result;
            $internalPayment->save();

            if ($result->getState() == 'approved') {
                return true;
                }
            return false;
        } catch (PayPal\Exception\PayPalConnectionException $ex) {
            echo $ex->getCode();
            echo $ex->getData();
            die($ex);
        } catch (Exception $ex) {
            die($ex);
        }
    }

    ///////////////////////////////////////////////////////////////////////////////////////////////

    public function getPayment($payment_id){
        return Paypal::where('payment_id',$payment_id)->first();
    }
}
