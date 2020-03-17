<?php


namespace beinmedia\payment\Services;

use Illuminate\Support\Facades\DB;

class PaymentsService
{
    public function getMyFatoorahPayments(){
        return DB::table('bn_myfatoorah_payments')->select('payment_id','invoice_value as amount','currency','invoice_status as status', 'created_at','payment_method')->where('invoice_status','Paid')->addSelect(DB::raw("'my_fatoorah' as service"));
    }
    public function getTapPayments(){
        return DB::table('bn_tap_payments')->select('charge_id as payment_id', 'amount','currency','status','created_at','payment_method')->where('status','CAPTURED')->addSelect(DB::raw("'tap' as service"));
    }
    public function getPaypalPayments(){
        return DB::table('bn_paypal_payments')->select('payment_id','amount','currency','state as status','created_at','type as payment_method')->where('state','approved')->addSelect(DB::raw("'paypal' as service"));
    }
    public function getRecurringPayments(){

        return DB::table('bn_recurring_payments')->select('pay_id as payment_id','amount','currency','state as status','created_at')->where('state','completed')->addSelect(DB::raw("'paypal' as payment_method"))->addSelect(DB::raw("'paypal_recurring' as service"));
    }
    public function getAllPayments(){
        return $this->getMyFatoorahPayments()
            ->union($this->getTapPayments())->union($this->getPaypalPayments())->union($this->getRecurringPayments())->get();

    }
}
