<?php
namespace beinmedia\payment\models;
use Illuminate\Database\Eloquent\Model;

class Tap extends Model
{
protected $table = 'bn_tap_payments';
protected $guarded=['id'];
}
