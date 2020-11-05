<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateBnFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('bn_files', function (Blueprint $table) {
            $table->increments('id');
            $table->string('file_id');
            $table->string('url');
            $table->string('internal_url');
            $table->string('filename');
            $table->string('purpose');
            $table->string('type');
            $table->integer('size');
            $table->integer('link_expires_at'); //don't know yet if integer or string or date
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
        Schema::dropIfExists('bn_files');
    }
}
