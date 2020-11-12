<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBnTapSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bn_tap_subscriptions', function (Blueprint $table) {
            $table->increments('id');
            $table->string('subscription_id');
            $table->string('card_id');
            $table->string('customer_id');
            $table->string('interval');
            $table->timestamp('from');
            $table->string('timezone');
            $table->string('currency');
            $table->string('description')->nullable();
            $table->string('track_id');
            $table->float('amount');
            $table->boolean('status');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('bn_tap_subscriptions');
    }
}
