<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAgreementsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('agreements', function (Blueprint $table) {
            $table->increments('id');
            $table->string('agreement_id')->nullable(true);
            $table->string('gateway');
            $table->string('description');
            $table->string('state')->default('active');
            $table->string('plan_id');
            $table->string('approval_link');
            $table->string('cycles_completed');
            $table->string('cycles_remaining');
            $table->string('next_billing_date')->nullable(true);
            $table->string('last_payment_date')->nullable(true);
            $table->foreign('plan_id')->references('plan_id')->on('OurPlans');
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
        Schema::dropIfExists('agreements');
    }
}
