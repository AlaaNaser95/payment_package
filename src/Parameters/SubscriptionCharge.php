<?php


namespace beinmedia\payment\Parameters;


class SubscriptionCharge
{
    public $amount;
    public $currency;
    public $description;
    public $metadata;
    public $reciept;
    public $customer;
    public $source;
    public $post;

    public function  __construct()
    {
        $this->metadata = new \stdClass();// track_id
        $this->post = new \stdClass(); //url
        $this->customer = new \stdClass(); //id
        $this->source = new \stdClass(); //card id
        $this->reciept = new \stdClass(); // sms/email
    }

}
