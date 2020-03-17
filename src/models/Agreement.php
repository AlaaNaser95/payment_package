<?php


namespace beinmedia\payment\models;

use Illuminate\Database\Eloquent\Model;

class Agreement extends Model
{
    protected $table='bn_agreements';
    protected $guarded = ['id'];

    public function OurPlan()
    {
        return $this->belongsTo('beinmedia\payment\models\OurPlan');
    }
}
