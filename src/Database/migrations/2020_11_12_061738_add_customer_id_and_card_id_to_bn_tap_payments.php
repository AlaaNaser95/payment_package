<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddCustomerIdAndCardIdToBnTapPayments extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bn_tap_payments', function (Blueprint $table) {
            $table->string('customer_id')->nullable();
            $table->string('card_id')->nullable();
            $table->string('subscription_id')->nullable();
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
            $table->dropColumn('customer_id');
            $table->dropColumn('card_id');
            $table->dropColumn('subscription_id');
        });
    }
}
