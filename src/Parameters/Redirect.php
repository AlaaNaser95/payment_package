<?php


namespace beinmedia\payment\Parameters;


class Redirect
{
    public $url;
    public function __construct(String $url)
    {
        $this->url=($url);
    }

}
