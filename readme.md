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
## Paypal payment Gateway
> **Configuration**

In the .evn file add Paypal credentials as the following example:

 
``` bash
PAYPAL_CLIENT_ID = Ack3DFGTYZSJ_6ypls3tTJQdt64Hp74b5rvykR71XHj0FqvKI4OspcV4dktE2xL-IPBiNoDGko03Mk62

PAYPAL_SECRET = EFZ-UDzdEe-V1QRn15K5TaGgM2Ttb5Z-Rk6dw0WCjrbS4E_6ZkCas4qWqHi5i9SuLvriO_p3KvRAzeSC

PAYPAL_MODE = (sandbox or live)
```

> **Usage**

* *generatePaymentURL($paymentParameters)*

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
---

##MyFatoorah payment Gateway
> **Configuration**

In the .evn file add MyFatoorah api key as the following:

``` bash
MYFATOORAH_API_KEY = 7Fs7eBv21F5xAocdPvvJ-sCqEyNHq4cygJrQUFvFiWEexBUPs4AkeLQxH4pzsUrY3Rays7GVA6SojFCz2DMLXSJVqk8NG-plK-cZJetwWjgwLPub_9tQQohWLgJ0q2invJ5C5Imt2ket_-JAlBYLLcnqp_WmOfZkBEWuURsBVirpNQecvpedgeCx4VaFae4qWDI_uKRV1829KCBEH84u6LYUxh8W_BYqkzXJYt99OlHTXHegd91PLT-tawBwuIly46nwbAs5Nt7HFOozxkyPp8BW9URlQW1fE4R_40BXzEuVkzK3WAOdpR92IkV94K_rDZCPltGSvWXtqJbnCpUB6iUIn1V-Ki15FAwh_nsfSmt_NQZ3rQuvyQ9B3yLCQ1ZO_MGSYDYVO26dyXbElspKxQwuNRot9hi3FIbXylV3iN40-nCPH4YQzKjo5p_fuaKhvRh7H8oFjRXtPtLQQUIDxk-jMbOp7gXIsdz02DrCfQIihT4evZuWA6YShl6g8fnAqCy8qRBf_eLDnA9w-nBh4Bq53b1kdhnExz0CMyUjQ43UO3uhMkBomJTXbmfAAHP8dZZao6W8a34OktNQmPTbOHXrtxf6DS-oKOu3l79uX_ihbL8ELT40VjIW3MJeZ_-auCPOjpE3Ax4dzUkSDLCljitmzMagH2X8jN8-AYLl46KcfkBV
```
> **Usage**

* *generatePaymentURL($paymentParameters)*

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
---
# Tap payment Gateway
> **Configuration**

In the .evn file add Tap api key as the following example:

``` bash
FAWRY_API_KEY = sk_test_XKokBfNWv6FIYuTMg5sLPjhJ
```

> **Usage**

* *generatePaymentURL($paymentParameters)*
``` bash
<?php

use beinmedia\payment\Parameters\PaymentParameters;
use TapPayment;

//for fawry add postURL only
//for other methods add redirectURL only

$data=new PaymentParameters();

$data->email="alaanaser95.95@gmail.com"; //customer email
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
## Change log

Please see the [changelog](changelog.md) for more information on what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [contributing.md](contributing.md) for details and a todolist.

## Security

If you discover any security related issues, please email author email instead of using the issue tracker.

## Credits

- [author name][link-author]
- [All Contributors][link-contributors]

## License

license. Please see the [license file](license.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/beinmedia/payment.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/beinmedia/payment.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/beinmedia/payment/master.svg?style=flat-square
[ico-styleci]: https://styleci.io/repos/12345678/shield

[link-packagist]: https://packagist.org/packages/beinmedia/payment
[link-downloads]: https://packagist.org/packages/beinmedia/payment
[link-travis]: https://travis-ci.org/beinmedia/payment
[link-styleci]: https://styleci.io/repos/12345678
[link-author]: https://github.com/beinmedia
[link-contributors]: ../../contributors
