<?php

namespace beinmedia\payment\Http\Controllers;

use beinmedia\payment\Services\PaypalRecurring;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class WebhookController extends Controller
{
    public function webhookResponse(Request $request){
        $s=new PaypalRecurring();
        return $s->handleWebHook();
    }
}
