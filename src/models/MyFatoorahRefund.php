<?php


namespace beinmedia\payment\models;



use Illuminate\Database\Eloquent\Model;

class MyFatoorahRefund extends Model
{
    protected $table="bn_myfatoorah_refunds";
    protected $guarded = ['id'];

    public function invoice(){
        return $this->belongsTo(MyFatoorah::class, 'invoice_id', 'invoice_id');
    }

}
