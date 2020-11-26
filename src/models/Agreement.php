<?php


namespace beinmedia\payment\models;

use Illuminate\Database\Eloquent\Model;

class Agreement extends Model
{
    protected $table='bn_agreements';
    protected $guarded = ['id'];

    public function ourPlan()
    {
        return $this->belongsTo('beinmedia\payment\models\OurPlan', 'plan_id', 'plan_id');
    }
}
