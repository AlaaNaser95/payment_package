<?php


namespace beinmedia\payment\Http\Controllers;


use App\Http\Controllers\Controller;
use beinmedia\payment\Services\TapGateway;
use Illuminate\Http\Request;

class FawryController extends Controller
{
    public function fawryCheck(Request $request){
        $s= new TapGateway();
        return $s->isPaymentExecuted();
    }
}
