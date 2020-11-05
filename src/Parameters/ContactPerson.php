<?php


namespace beinmedia\payment\Parameters;


class ContactPerson
{
    public $name; //first, last in en
    public $contact_info; //email, phone obj
    public $identification; //document

    public function __construct($first_name,$last_name, $phone, $email, $identification=null){
        $this->name = new \stdClass();
        $this->name->first = $first_name;
        $this->name->last = $last_name;
        $this->contact_info = new \stdClass();
        $this->contact_info->primary = new \stdClass();
        $this->contact_info->primary->phone = $phone;
        $this->contact_info->primary->email = $email;
        $this->identification = $identification;
    }

}
