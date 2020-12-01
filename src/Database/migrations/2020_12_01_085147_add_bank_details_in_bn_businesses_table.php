<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class AddBankDetailsInBnBusinessesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('bn_businesses', function (Blueprint $table) {
            $table->string('account_number')->nullable();
            $table->string('swift_code')->nullable();
            $table->string('bank_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('bn_businesses', function (Blueprint $table) {
            $table->dropColumn('account_number');
            $table->dropColumn('swift_code');
            $table->dropColumn('bank_id');
        });
    }
}
