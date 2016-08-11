<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCASLoginTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('CASLogin', function (Blueprint $table) {
            $table->string('throttleBy', 510);
            $table->primary('throttleBy');
            $table->unsignedInteger('attempts');
            $table->dateTime('lastAttempt');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('CASLogin');
    }
}
