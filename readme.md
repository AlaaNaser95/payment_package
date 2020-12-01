# payment_package

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![Build Status][ico-travis]][link-travis]

laravel package, that implements MyFatoorah, Tap , PayPal payment gatways and paypal recurring billing.

## Features
* Laravel 5.7.* | 6.x | 7.x are supported.

## Requirements
* laravel/framework 5.7.*
* PHP 7.2.5

## Installation

Via Composer

``` bash
$ composer require beinmedia/payment
```
**Database Configuration**

Update the .env configurations below:
``` bash
    DB_CONNECTION=mysql
    DB_HOST=add your host
    DB_PORT= add the port
    DB_DATABASE= database name
    DB_USERNAME=database username
    DB_PASSWORD=database password
```

**Migrate:**
``` bash
 $ php artisan migrate
```

**Autoload:**
``` bash
 $ composer dump-autoload
```

## Pyment Gatways

In order to make online payment gatway you need the following:
1. Create the payment gatway link.
2. Redirect the customer to the pyment link.
3. User will pay.
2. Recieve and validate the payment in the redirect URL you specified in the created payment gatway.

### Paypal payment Gateway
> **Configuration**

In the .evn file add Paypal credentials as the following example:

 
``` bash
PAYPAL_CLIENT_ID = Ack3DFGTYZSJ_6ypls3tTJQdt64Hp74b5rvykR71XHj0FqvKI4OspcV4dktE2xL-IPBiNoDGko03Mk62

PAYPAL_SECRET = EFZ-UDzdEe-V1QRn15K5TaGgM2Ttb5Z-Rk6dw0WCjrbS4E_6ZkCas4qWqHi5i9SuLvriO_p3KvRAzeSC

PAYPAL_MODE = (sandbox or live)
```

> **Usage**

**Methods:**

* *generatePaymentURL($paymentParameters)*

This method will return the payment gateway url where the user should be redirected to in order to complete payment process.

You need to specify the amount , currency, where the user should be redirected after successful payment and the cancel url where the user shoul be redirected after failed or canceled payment.
``` bash
<?php

use beinmedia\payment\Parameters\PaymentParameters;
use PaypalPayment;

// Create the paymentParameters object
$data=new PaymentParameters();

$data->amount=10;  // Float Amount
$data->currency='USD';  // ISO currency code
$data->trackId= "track id"; //optional - user defined field
$data->returnURL=url('/paypal-check'); // Fully qualified url where the user will be redirected after successful payment.
$data->cancelURL='https://www.example.com'; // Fully qualified url where the user will be redirected after failed payment.

//Getting the payment link where the user should be redirected to
$paymentLink= PaypalPayment::generatePaymentURL($data); 

```

* *isPayementExecuted()*
This method should be called in the redirect url to validate the payment. 

``` bash
<?php

use PaypalPayment;

public function checkPaypalPayment (Request $request){
    if(PaypalPayment::isPaymentExecuted()->status)
        return 'success';
    else
        return 'fail';
}
```

* *isPayementExecuted($paymentId)*
This method validate the payment on specific payment_id. 

``` bash
<?php

if(PaypalPayment::isPaymentExecuted("PAYID-L2OH7LA6TM617940X333145E")->status)
    return 'success';
else
    return 'fail';

```

* *getPayment($payment_id)*
This method returns the related paypal payment details according to the given payment_id.
``` bash
use PaypalPayment;

$paypal_payment = PaypalPayment::getPayment($payment_id);
```
---



### MyFatoorah payment Gateway
> **Configuration**

In the .evn file add MyFatoorah api key as the following:

* Add the api key from myFatoorah.
``` bash
MYFATOORAH_API_KEY = 7Fs7eBv21F5xAocdPvvJ-sCqEyNHq4cygJrQUFvFiWEexBUPs4AkeLQxH4pzsUrY3Rays7GVA6SojFCz2DMLXSJVqk8NG-plK-cZJetwWjgwLPub_9tQQohWLgJ0q2invJ5C5Imt2ket_-JAlBYLLcnqp_WmOfZkBEWuURsBVirpNQecvpedgeCx4VaFae4qWDI_uKRV1829KCBEH84u6LYUxh8W_BYqkzXJYt99OlHTXHegd91PLT-tawBwuIly46nwbAs5Nt7HFOozxkyPp8BW9URlQW1fE4R_40BXzEuVkzK3WAOdpR92IkV94K_rDZCPltGSvWXtqJbnCpUB6iUIn1V-Ki15FAwh_nsfSmt_NQZ3rQuvyQ9B3yLCQ1ZO_MGSYDYVO26dyXbElspKxQwuNRot9hi3FIbXylV3iN40-nCPH4YQzKjo5p_fuaKhvRh7H8oFjRXtPtLQQUIDxk-jMbOp7gXIsdz02DrCfQIihT4evZuWA6YShl6g8fnAqCy8qRBf_eLDnA9w-nBh4Bq53b1kdhnExz0CMyUjQ43UO3uhMkBomJTXbmfAAHP8dZZao6W8a34OktNQmPTbOHXrtxf6DS-oKOu3l79uX_ihbL8ELT40VjIW3MJeZ_-auCPOjpE3Ax4dzUkSDLCljitmzMagH2X8jN8-AYLl46KcfkBV
```
* If you are still testing and not live set the testing mode to true, else set it to false.
``` bash
MYFATOORAH_TEST_MODE = true
```
> **Usage**

* *getMyFatoorahPaymentMethods($currency,$iso_Code)*

Returns all payment methods for your account with the related fees for each method according to the amount.

``` bash
    use MyFatoorahPayment;

    $paymentMethods= MyFatoorahPayment::getMyFatoorahPaymentMethods(10,"KWD");
```

* *generatePaymentURL($paymentParameters)*

This method will return the payment gateway url where the user should be redirected to in order to complete payment process.``` bash
``` bash
<?php

use beinmedia\payment\Parameters\PaymentParameters;
use MyFatoorahPayment;

// Create the paymentParameters object
$data=new PaymentParameters();
$data->paymentMethodId=1; // Check the available methods from the table
$data->amount=10; // Amount
$data->trackId= "track id"; //optional - user defined field
$data->currency="KWD"; // optional- default the same as api country currency
$data->name="Alaa"; // optional- Customer name
$data->email="alaa@test.com"; // optional- Customer email
$data->returnURL=url('/fatoorah-check'); // Fully qualified url where the user will be redirected after successful payment.
$data->cancelURL="https://www.beinmedia.com/"; // Fully qualified url where the user will be redirected after failed payment.

//Getting the payment link where the user should be redirected to
$paymentLink = MyFatoorahPayment::generatePaymentURL($data);
```

* *isPayementExecuted()*

This method should be called in the redirect url to validate the payment. 

``` bash
<?php

use MyFatoorahPayment;

public function checkMyFatoorahPayment(Request $request){

    if(MyFatoorahPayment::isPaymentExecuted()->status)
        return ‘success’;
    else
        return ‘fail’;

}
```

* *isPayementExecuted($invoiceId)*

This method validates the payment on specific invoice_id. 

``` bash
<?php

use MyFatoorahPayment;

if(MyFatoorahPayment::isPaymentExecuted(5513941)->status)
    return ‘success’;
else
    return ‘fail’;

```


* *getPayment($invoice_id)*

This method returns the related MyFatoorah payment details according to the given incoice id.

``` bash
use MyFatoorahPayment;

$paypal_payment = MyFatoorahPayment::getPayment($payment_id);
```

---
### Tap payment Gateway
> **Configuration**

In the .evn file add Tap api key as the following example:

``` bash
TAP_API_KEY = sk_test_XKokBfNWv6FIYuTMg5sLPjhJ
```
**Fawry**
(For testing mode)
- If you are still testing and not live set the testing mode to true.

``` bash
FAWRY_TESTING_MODE=true
```
- Add your base url for the published version of your project (you can use ngrok for testing).
 
``` bash
FAWRY_TESTING_PUBLISHED_BASE_URL=https://789fbf71.ngrok.io
```

> **Usage**

**Fawry Gateway**

Fawry has different structure, the payment is validated asynchronously.
You need to set postURL where you will get notified once the payment is completed. No need to set redirectURL.
Once the payment is executed, the post url provided will be requested by Tap, You need to call *isPayementExecuted()* method to validate payment and check the payment status.


**Methods**

* *generatePaymentURL($paymentParameters)*

This Method returns the url where the user should be redirected to proceed the payment. It can be used for both credit and debit cards but not for direct payment. 
For fawry, this method return the order number not the payment URL, user will pay using this number in any fawry center.

``` bash
<?php

use beinmedia\payment\Parameters\PaymentParameters;
use TapPayment;

//for fawry add postURL only
//for other methods add redirectURL only

$data=new PaymentParameters();

$data->email="alaanaser95.95@gmail.com"; // Customer email
$data->name="Alaa"; //Customer email
$data->countryCode="965";
$data->phoneNumber="65080631";
$data->trackId= "track id"; //optional - user defined field
$data->amount=10; // float amount
$data->description="dfghjk";
$data->paymentMethodId="src_eg.fawry";
$data->currency="EGP"; // Iso currency
$data->destination_id = '1235'; //the destination_id returned from create business (used for multivendor)
$data->transfer_amount = 9; // the amount that will be transfered to the business in case destination_id is specified; 

// For fawry only - To get notification once the payment is completed (Asyncronous payment)
$data->postURL=url('/api/fawry-check'); // Fully qualified url (Only Post method routs are allowed).

// For other methods only, not for fawry
$data->returnURL=url("tap-check"); // Fully qualified url where the user will be redirected after successful payment.

//Getting the payment link where the user should be redirected to
$paymentLink = TapPayment::generatePaymentURL($data);
```

* *ChargeCard()*

This Method returns the payment status, card id charge id and customer id. It can be used to charge credit cards with direct payment using tap card.js library. 

``` bash
<?php

use beinmedia\payment\Parameters\PaymentParameters;
use TapPayment;

        //for fawry add postURL only
        //for other method add redirectURL only
        $data=new PaymentParameters();
        $data->email="alaanaser95.95@gmail.com";
        $data->name="Alaa";
        $data->returnURL=url("tap-check");
        $data->countryCode="965";
        $data->phoneNumber="65080631";
        $data->amount=10;
        $data->description="dfghjk";
        $data->paymentMethodId="tok_Ck8tJ1311012ntXJ527473";// the one time token created with tap card.js library
        $data->currency="KWD";
        $data->trackId="1234";
        $data->destination_id = '1234';
        $data->transfer_amount = 9;

        //Charge the credit card and get the response
        $paymentLink = TapPayment::ChargeCard($data);

/*
Response Eample
{
"card_id" : "card_576789",
"customer_id" : "cus_43465789",
"charge_id" : "ch_5476769",
"status" : true 
}
*/
```

* *isPayementExecuted()*

This method should be called in the redirect url to validate the payment. 

``` bash
<?php

use TapPayment;

public function checkTapPayment (Request $request){
    if( TapPayment::isPaymentExecuted()->status)
        return ‘success’;
    else
        return ‘fail’;
}
```

## Tap Recurring Payments

If you want to create subscription to periodically charge customer card with specific amount you need to create a subscription. Subscriptions are available only for credit cards ('visa', 'master', 'amex').

You can get the card_id and customer_id from chargeCard() method.

**Methods**

* *createSubscription()*

```bash
use beinmedia\payment\Services\TapGateway;
use beinmedia\payment\Parameters\SubscriptionCharge;
use beinmedia\payment\Parameters\SubscriptionParameters;
use beinmedia\payment\Parameters\SubscriptionTerm;

public function createSubscription(){
        $term = new SubscriptionTerm();
        $term->interval = "DAILY"; //("DAILY","YEARLY","MONTHLY",...etc)
        $term->period = 10; //How many times you want to charge the customer card
        $term->from = "2020-11-12 16:08:00"; "the start time for the charge
        $term->due = 0; 
        $term->auto_renew = true; //true if you want to renew the subscription automatically
        $term->timezone = "Asia/Kuwait"; //the timezone for which the start time for ythe charge is specified

        $charge = new SubscriptionCharge();
        $charge->amount = 10; //thre amount to be charged
        $charge->currency = "KWD"; //the currency of charge amount
        $charge->description = "This is a test subscription";
        $charge->metadata->track_id = "123456789910"; //A custom reference_id 
        $charge->reciept->email = true; //optional
        $charge->reciept->sms = true; //optional
        $charge->customer->id = 'cus_TS024820201200n5X50811060'; //customer_id returned from ChargeCard() method
        $charge->source->id = 'card_CFlTu1311012JanD527931'; //card_id returned from ChargeCard() method
        $charge->post->url = 'https://3b2429fb7e8b.ngrok.io/api/handle'; // post url where you want to be notified once a periodic payment is done.

        //Create subscription and get the subscription_id and status
        $data = new SubscriptionParameters($term,$charge);
        $response = app(TapGateway::class)->createSubscription($data);
        return response()->json(['response'=>$response]);
    }

/*
Response Eample
{
"status" : "active",
"id" : "sub_43465789",
}
*/
```

* *verifySubscriptionPayment()*

Call this method on the post url you specified in the createSubscription to verify the payment of the subscription.

```bash
use beinmedia\payment\Services\TapGateway;

public function handleRecurring(){
        $response = app(TapGateway::class)->verifySubscriptionPayment();

        //you can call this for more verificattion:
        //$response = app(TapGateway::class)->isPaymentExecuted();

        if($response->status == true){
            return 'success';
        }
        return 'failed';
    }
```

* *cancelSubscription()*

```bash
    public function cancelSubscription(){
        return app(TapGateway::class)->cancelSubscription('sub_Xr8s3820200900r5L51211982')]);
    }
```

## TAP Multi Vendors (Subscriptions)
> **Configuration**

At .env file set the following:

```bash
TAP_MARKETPLACE_API_KEY = sk_test_fEZYI3X1P7865rtsoGpbvw4qBm
```

**Available Methods:**

* *getSectors()*

Get a list of all sectors that tap supports in order to add one of the sector id when creating the business.

```bash
    public function getSectors(){
        return app(TapGateway::class)->getSectors($fileParameters);
    }
```

* *createFile()*

```bash
    public function createFile(){
        $filename = time().'.'.request('file')->getClientOriginalExtension();
        request('file')->move('storage', $filename);
        $filePath = "storage/$filename";
        $purpose = 'identifcation_document';
        $fileParameters = new FileParameters($filePath, $filename, $purpose);
        return app(TapGateway::class)->createFile($fileParameters);
    }
```

* *createBusiness()*

To add new business details so that any charge created with the business destination_id will be transfered to the business bank account directly.

```bash

use beinmedia\payment\Services\TapGateway;

    public function createBusiness(){
        $civil_id = new \stdClass();
        $civil_id->type = 'civil id';
        $civil_id->issuing_country = 'KW';
        $civil_id->issuing_date = '2020-01-01';
        $civil_id->expiry_date = '2021-01-01';
        $civil_id->images = ['file_773153834221826048']; //the file_id returned from createFile method as array 
        $civil_id->number = '295102500437'; //civil_id number
        $contact_person = new ContactPerson('Alaa','Naser',new Phone('965','65080631'),'alaanser95.95@gmail.com', [$civil_id]);

        $authorization = new \stdClass();
        $authorization->type = 'authorized_signature';
        $authorization->issuing_country = 'KW';
        $authorization->issuing_date = '2020-01-01';
        $authorization->expiry_date = '2021-01-01';
        $authorization->images = ['file_773150399938293760']; //the file_id returned from createFile method as array
        $authorization->number = '295102500437'; //authorized signature number

        $license = new \stdClass();
        $license->type = 'license';
        $license->issuing_country = 'KW';
        $license->issuing_date = '2020-01-01';
        $license->expiry_date = '2021-01-01';
        $license->images = ['file_773155798586355712']; //the file_id returned from createFile method as array 
        $license->number = '295102500437'; //commercial license number

        $parameters = new BusinessParameters();
        $parameters->business_name = 'test12121';
        $parameters->type = 'corp';
        $parameters->business_legal_name = 'test company for testing21212';
        $parameters->business_country = 'KW';
        $parameters->iban = 'erj54r73658647246928724';
        $parameters->contact_person = $contact_person;
        $parameters->sector = ['sector_Vi2Dy828EgUeDVJ']; //returned from getSetors() method
        $parameters->website = 'https://oktabletmenu1.com';
        $parameters->documents = [$authorization, $license];

        return app(TapGateway::class)->createBusiness($fileParameters);

```




---
## Paypal recurring payments

> **Configuration**

At .env file set the following:
```
- Add your webhook URL where you will get notified once a subscrbtion is canceled or recurring payment is completed (you can use ngrok for testing. 
``` bash
PAYPAL_RECURRING_WEBHOOK_URL=https://b2015d91.ngrok.io/webhook
```

**Create webhook**

This webhook needs to be created only once while live to allow the package recieve notifications from paypal.

``` bash
 $ php artisan create:webhook
```

If you are using ngrok for testing you need to reset the related data at env file and recreate the webhook every 7 hours.

> **Usage**

In order to create agreement for recurring payment you need the followuing:

1. Create a plan to be assigned to the agreement. (Multible agreements can be assigned to the same plan)
2. Create agreement and get the approval link where the user should be redirectred to accept that agreement.
3. Execute agreement to process the agreement acceptance and validate agreement acceptance.
4. Get the post notification whenever payment is completed for any agreement or whenever any agreement is cancelled, so you can give or remove licence to the payer.

**Available Methods:**
* *createPlan()*
``` bash
<?php

use beinmedia\payment\Parameters\PlanParam;
use PaypalRecurring;

//create planParam object
$planParam=new PlanParam();

$planParam->planName='Premium Package';
$planParam->description='Get Full access to all our features';
$planParam->amount=10;
$planParam->returnURL=url("/recurring-execute"); // Fully qualified url where the user will be redirected after successful payment.
$planParam->cancelURL="https://www.tapcom.com/"; // // Fully qualified url where the user will be redirected after failed payment.

//create A plan
$createdPlanObject= PaypalRecurring::createPlan($planParam);
```

* *createAgreement($plan_id, $agreement_name, $agreement_description, $payer_info, $reference_id))*
``` bash
<?php

use PaypalRecurring;

 $payer_info = new PayerInfoParameters();
        $payer_info->email='alaanaser95.95@gmail.com';
        $payer_info->first_name = 'Alaa';
        $payer_info->last_name = 'Naser';
        $payer_info->payer_id = '987654321';
// Generate agreement url where the user should be redirected to
$agreementLink = PaypalRecurring::createAgreement('P-3D407875MD555251WQYZQOJA','Alaa','MyAgreement', $payer_info, "123456789");

```
* *executeAgreement()*

``` bash
<?php

use PaypalRecurring;

public function executeAgreement(Request $request){

    if(PaypalRecurring::executeAgreement() instanceof Agreement)
            return 'yes';
    return 'No';
}

```

* *cancelAgreement($agreement_id)*
``` bash
<?php

use PaypalRecurring;

public function cancelAgreement(Request $request){

    return PaypalRecurring::cancelAgreement('I-975S8RWXLGMU');
}
```

* *cancelAgreement($agreement_id)*
``` bash
<?php

use PaypalRecurring;

public function cancelAgreement(Request $request){

    return PaypalRecurring::cancelAgreement('I-975S8RWXLGMU');
}
```

* *checkAgreementPayed($agreement_id)*

``` bash
<?php

use PaypalRecurring;

public function cancelAgreement(Request $request){

    return PaypalRecurring::checkAgreementPayed('I-975S8RWXLGMU');
}
```

## Getting Payment lists
At some point you may need to get all completed payments for the sake of statistics. The package offers this feature by `getAllPayments()` method.
>**Usage**
``` bash
use Payments;

//returns all completed payments through Tap, Myfatoorah, paypal and paypal recurring payments.

$all_payments = Payments::getAllPayments();
```

## Samples
isPaymentExecuted();
```bash
    {
        "status" : true, //true => payment approved, false => payment declined
        "track_id": "track id"
    }
```
### Fawry 
isPaymentExecuted();
``` bash
    {
        "tap_id" : "chg_Nj545620201224Zq450303159" ,
        "status" : true, //true => payment approved, false => payment declined
        "track_id": "track id"
    }
```

* Paypal Recurring payment

Once Recurring payment is completed:
``` bash
    {
        "event_type" : "PAYMENT.SALE.COMPLETED" ,
        "agreement_id" : "I-PE7JWXKGVN0R",
        "payment_id" : "80021663DE681814L"
    }
```
Once Recurring payment is cancelled:
``` bash
    {
        "event_type" : "BILLING.SUBSCRIPTION.CANCELLED" ,
        "agreement_id" : "I-PE7JWXKGVN0R"
    }
```

## Credits

- [Alaa Naser][link-author]


[ico-version]: https://img.shields.io/packagist/v/beinmedia/payment.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/beinmedia/payment.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/beinmedia/payment/master.svg?style=flat-square
[ico-styleci]: https://styleci.io/repos/12345678/shield

[link-packagist]: https://packagist.org/packages/beinmedia/payment
[link-downloads]: https://packagist.org/packages/beinmedia/payment
[link-travis]: https://travis-ci.org/beinmedia/payment
[link-styleci]: https://styleci.io/repos/12345678
[link-author]: https://github.com/AlaaNaser95
[link-contributors]: ../../contributors
