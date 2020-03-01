<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMyfatoorahPaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('myfatoorah_payments', function (Blueprint $table) {
            $table->increments('id');
            $table->integer("payment_method_id");
            $table->string("payment_method");
            $table->string("currency");
            $table->string("payment_url");
            $table->string("customer_reference")->nullable(true);
            $table->string("invoice_status")->nullable(true);
            $table->integer("invoice_id");
            $table->float("invoice_value");
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
        Schema::dropIfExists('myfatoorah_payments');
    }
}
