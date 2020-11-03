<?php


namespace beinmedia\payment\Parameters;


class SubscriptionCharge
{
    public $amount;
    public $currency;
    public $description;
    public $metadata;
    public $reciept;
    public $customer_id;
    public $source_id;
    public $post_url;

    public function  __construct()
    {
        $this->metadata = new \stdClass();
        $this->post = new \stdClass();
        $this->customer = new \stdClass();
        $this->source = new \stdClass();
        $this->reciept = new \stdClass();
    }

}
