<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddDestinationIdToBnTapPayments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bn_tap_payments', function (Blueprint $table) {
            $table->string('destination_id')->nullable();
            $table->float('transfer_amount')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bn_tap_payments', function (Blueprint $table) {
            $table->dropColumn('destination_id');
            $table->dropColumn('transfer_amount');
        });
    }
}
