<?php


namespace beinmedia\payment\Parameters;


class PlanParam
{
    public $planName;
    public $description;
    public $interval;
    public $amount;
    public $currency;
    public $returnURL;
    public $cancelURL;
    public function __construct(){
        $this->currency='USD';
        $this->interval=1;
    }
}
