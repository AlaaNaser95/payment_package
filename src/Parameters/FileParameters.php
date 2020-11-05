<?php


namespace beinmedia\payment\Parameters;


class FileParameters
{
    public $file; //base path for file
    public $purpose; //one of the options in tap
    public $title; //any string
    public $file_link_create; //boolean
    public $expires_at; //epoch time integer

    public function __construct($file_path, $file_title, $purpose, $file_link_create=true,$expires_at=null)
    {
        if($file_link_create){
                $this->expires_at = empty($expires_at) ? strtotime("+5 years", time()): $expires_at;
        }
        $this->file = $file_path;
        $this->title = $file_title;
        $this->purpose = $purpose;
        $this->file_link_create = $file_link_create;
    }

}
