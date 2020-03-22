<?php


namespace beinmedia\payment\Parameters;

class ChargeParam
{
    public $amount;
    public $currency="EGP";
    public $source;
    public $customer;
    public $description;
    public $metadata;
    public $post;
    public $redirect;
    public function __construct(){
    }

}
