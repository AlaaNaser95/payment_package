<?php


namespace beinmedia\payment\models;
use Illuminate\Database\Eloquent\Model;

class OurPlan extends Model
{
    protected $table='bn_our_plans';
    protected $guarded = ['id'];
    public function agreements()
    {
        return $this->hasMany('beinmedia\payment\models\Agreement');
    }
}
