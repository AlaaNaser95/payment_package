<?php


namespace beinmedia\payment\models;
use Illuminate\Database\Eloquent\Model;


class Subscription extends Model
{
    protected $table ='bn_tap_subscriptions';
    protected $guarded=['id'];

}
