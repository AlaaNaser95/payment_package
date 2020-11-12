<?php


namespace beinmedia\payment\Parameters;


class SubscriptionParameters
{
    public $term; //subscription term
    public $charge; //subscription charge

    public function __construct($term = null, $charge = null)
    {
        $this->term =$term;
        $this->charge = $charge;
    }
}
