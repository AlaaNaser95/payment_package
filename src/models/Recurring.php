<?php


namespace beinmedia\payment\models;

use Illuminate\Database\Eloquent\Model;
class Recurring extends Model
{
    protected $table='bn_recurring_payments';
    protected $guarded = ['id'];


}
