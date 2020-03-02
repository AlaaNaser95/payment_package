<?php


namespace beinmedia\payment\Services;


use beinmedia\payment\models\MyFatoorah;
use Illuminate\Support\Facades\DB;

class PaymentsService
{
    public function getMyFatoorahPayments(){
        return DB::table('myfatoorah_payments')->select('payment_id','invoice_value as amount','currency','invoice_status as status', 'created_at','payment_method')->addSelect(DB::raw("'my_fatoorah' as service"));
    }
    public function getTapPayments(){
        return DB::table('tap_payments')->select('charge_id as payment_id', 'amount','currency','status','created_at','source_id as payment_method')->addSelect(DB::raw("'tap' as service"));
    }
    public function getPaypalPayments(){
        return DB::table('paypal_payments')->select('payment_id','amount','currency','state as status','created_at','type as payment_method')->addSelect(DB::raw("'paypal' as service"));
    }
    public function getRecurringPayments(){

        return DB::table('recurring_payments')->select('pay_id as payment_id','amount','currency','state as status','created_at')->addSelect(DB::raw("'paypal' as payment_method"))->addSelect(DB::raw("'paypal_recurring' as service"));
    }
    public function getAllPayments(){
        return $this->getMyFatoorahPayments()
            ->union($this->getTapPayments())->union($this->getPaypalPayments())->union($this->getRecurringPayments())->get();

    }
}
