<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBnMyfatoorahRefundsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bn_myfatoorah_payments', function(Blueprint $table){
            $table->integer('invoice_id')->unique()->change();
        });

        Schema::create('bn_myfatoorah_refunds', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('invoice_id');
            $table->foreign('invoice_id')->references('invoice_id')->on('bn_myfatoorah_payments')->onUpdate('cascade')->onDelete('cascade');
            $table->float('amount');
            $table->integer('refund_id');
            $table->string('refund_reference');
            $table->string('comment')->nullable();
            $table->boolean('refund_on_customer');
            $table->boolean('service_on_customer');
            $table->string('customer_reference')->nullable();
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
        Schema::dropIfExists('bn_myfatoorah_refunds');
    }
}
