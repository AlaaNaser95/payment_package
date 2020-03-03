# payment_package

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]
[![Build Status][ico-travis]][link-travis]
[![StyleCI][ico-styleci]][link-styleci]

laravel package, that implements MyFatoorah, Tap , PayPal payment gatways and paypal recurring billing.

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
    if(PaypalPayment::isPaymentExecuted())
        return 'success';
    else
        return 'fail';
}
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

``` bash
MYFATOORAH_API_KEY = 7Fs7eBv21F5xAocdPvvJ-sCqEyNHq4cygJrQUFvFiWEexBUPs4AkeLQxH4pzsUrY3Rays7GVA6SojFCz2DMLXSJVqk8NG-plK-cZJetwWjgwLPub_9tQQohWLgJ0q2invJ5C5Imt2ket_-JAlBYLLcnqp_WmOfZkBEWuURsBVirpNQecvpedgeCx4VaFae4qWDI_uKRV1829KCBEH84u6LYUxh8W_BYqkzXJYt99OlHTXHegd91PLT-tawBwuIly46nwbAs5Nt7HFOozxkyPp8BW9URlQW1fE4R_40BXzEuVkzK3WAOdpR92IkV94K_rDZCPltGSvWXtqJbnCpUB6iUIn1V-Ki15FAwh_nsfSmt_NQZ3rQuvyQ9B3yLCQ1ZO_MGSYDYVO26dyXbElspKxQwuNRot9hi3FIbXylV3iN40-nCPH4YQzKjo5p_fuaKhvRh7H8oFjRXtPtLQQUIDxk-jMbOp7gXIsdz02DrCfQIihT4evZuWA6YShl6g8fnAqCy8qRBf_eLDnA9w-nBh4Bq53b1kdhnExz0CMyUjQ43UO3uhMkBomJTXbmfAAHP8dZZao6W8a34OktNQmPTbOHXrtxf6DS-oKOu3l79uX_ihbL8ELT40VjIW3MJeZ_-auCPOjpE3Ax4dzUkSDLCljitmzMagH2X8jN8-AYLl46KcfkBV
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
$data->PaymentMethodId=1; // Check the available methods from the table
$data->amount=10; // Amount
$data->currency="KWD"; // optional- default the same as api country currency
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

    if(MyFatoorahPayment::isPaymentExecuted())
        return ‘success’;
    else
        return ‘fail’;

}
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
You need to set postURL where you will get notified once the payment is completed.
No need to set redirectURL.

**Methods**

* *generatePaymentURL($paymentParameters)*

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
$data->amount=10; // float amount
$data->description="dfghjk";
$data->paymentMethodId="src_eg.fawry";//fawry is not giving url any more
$data->currency="EGP"; // Iso currency

// For fawry only - To get notification once the payment is completed (Asyncronous payment)
$data->postURL=url('/api/fawry-check'); // Fully qualified url (Only Post method routs are allowed).

// For other methods only, not for fawry
$data->returnURL=url("tap-check"); // Fully qualified url where the user will be redirected after successful payment.

//Getting the payment link where the user should be redirected to
$paymentLink = TapPayment::generatePaymentURL($data);
```

* *isPayementExecuted()*

This method should be called in the redirect url to validate the payment. 

``` bash
<?php

use TapPayment;

public function checkTapPayment (Request $request){
    if( TapPayment::isPaymentExecuted())
        return ‘success’;
    else
        return ‘fail’;
}
```
---
## Paypal recurring payments

> **Configuration**

At .env file set the following:

(For testing mode)
- If you are still testing and not live set the testing mode to true.
``` bash
PAYPAL_RECURRING_TESTING_MODE = true
```
- Add your base url for the published version of your project (you can use ngrok for testing. 
``` bash
PAYPAL_RECURRING_TESTING_WEBHOOK_URL=https://b2015d91.ngrok.io
```

(For testing & live mode)
- Specify the webhook url where you will receive post notifications whenever user cancels the agreement or a payment is done for the agreement.(Fully qualified **published** url should be added).
``` bash
RECURRING_NOTIFICATION_URL=https://b2015d91.ngrok.io/webhookresponse
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

* *createAgreement($plan_id, $agreement_name, $agreement_description))*
``` bash
<?php

use PaypalRecurring;

// Generate agreement url where the user should be redirected to
$agreementLink = PaypalRecurring::createAgreement('P-3D407875MD555251WQYZQOJA','Alaa','MyAgreement');

```
* *executeAgreement()*

``` bash
<?php

use PaypalRecurring;

public function executeAgreement(Request $request){

    return (PaypalRecurring::executeAgreement())?'Success' : 'Fail');
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

## Samples for Post notification body
* Fawry
``` bash
    {
        "charge_id" : "chg_Nj545620201224Zq450303159" ,
        "status" : "CAPTURED"
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
