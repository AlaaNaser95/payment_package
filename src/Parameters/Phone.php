<?php


namespace beinmedia\payment\Parameters;


class Phone
{
    public $country_code;
    public $number;
    public function __construct(String $country_code, String $number){
        $this->number=$number;
        $this->country_code=$country_code;
    }
}
