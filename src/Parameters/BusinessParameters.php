<?php


namespace beinmedia\payment\Parameters;


class BusinessParameters
{
    public $business_name;
    public $type; //ind or corp
    public $business_legal_name;
    public $business_country; //iso country code
    public $iban;
    public $swift_code;
    public $account_number;
    public $contact_person;// contact person object
    public $sector;// sector id from sector api.
    public $website;
    public $documents; //required_documents according country

}
