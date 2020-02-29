<?php


namespace beinmedia\payment\Facades;


use Illuminate\Support\Facades\Facade;

class MyFatoorah extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return 'myFatoorah';
    }
}
