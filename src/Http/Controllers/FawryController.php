<?php


namespace beinmedia\payment\Http\Controllers;


use beinmedia\payment\Services\TapGateway;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class FawryController extends Controller
{
    public function fawryCheck(Request $request){
        $s= new TapGateway();
        return $s->isPaymentExecuted();
    }
}
