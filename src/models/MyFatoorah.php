<?php

namespace beinmedia\payment\models;;

use Illuminate\Database\Eloquent\Model;

class MyFatoorah extends Model
{
    protected $table="bn_myfatoorah_payments";

    public function refunds(){
        return $this->hasMany(MyFatoorahRefund::class, 'invoice_id', 'invoice_id');
    }

}
