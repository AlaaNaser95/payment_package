<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBnMyfatoorahPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bn_myfatoorah_payments', function (Blueprint $table) {
            $table->increments('id');
            $table->string("payment_id")->nullable(true);
            $table->integer("payment_method_id");
            $table->string("payment_method")->nullable(true);
            $table->string("currency")->nullable(true);
            $table->string("payment_url");
            $table->string("customer_reference")->nullable(true);
            $table->string("invoice_status")->nullable(true);
            $table->integer("invoice_id");
            $table->float("invoice_value")->nullable(true);
            $table->json("json")->nullable(true);
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
        Schema::dropIfExists('bn_myfatoorah_payments');
    }
}
