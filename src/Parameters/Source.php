<?php


namespace beinmedia\payment\Parameters;


class Source
{
    public $id;
    public function __construct(String $source_id)
    {
        $this->id=($source_id);
    }
}
