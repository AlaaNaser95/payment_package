<?php

namespace beinmedia\payment\Http\Controllers;

use App\Http\Controllers\Controller;
use beinmedia\payment\Services\PaypalRecurring;
use Illuminate\Http\Request;

class WebhookController extends Controller
{
    public function webhookResponse(Request $request){
        $s=new PaypalRecurring();
        return $s->handleWebHook();
    }
}
