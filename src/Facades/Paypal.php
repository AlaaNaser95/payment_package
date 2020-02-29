<?php
namespace beinmedia\payment\Facades;


use Illuminate\Support\Facades\Facade;

class Paypal extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {

        return 'paypal';
    }
}
