<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBnRecurringPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bn_recurring_payments', function (Blueprint $table) {
            $table->increments('id');
            $table->string('pay_id');
            $table->float('amount');
            $table->string('currency');
            $table->string('agreement_id');
            $table->string('state');
            $table->string('payment_date');
            $table->foreign('agreement_id')->references('agreement_id')->on('bn_agreements');
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
        Schema::dropIfExists('bn_recurring_payments');
    }
}
