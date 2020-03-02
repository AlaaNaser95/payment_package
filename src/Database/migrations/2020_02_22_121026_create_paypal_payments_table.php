<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePaypalPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('paypal_payments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('payment_id');
            $table->string('payer_id')->nullable(true);
            $table->string('state');
            $table->string('currency');
            $table->float('amount');
            $table->string('type');
            $table->string('approval_link');
            $table->string('create_time');
            $table->string('update_time')->nullable(true);
            $table->json('json')->nullable();
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
        Schema::dropIfExists('paypal_payments');
    }
}
