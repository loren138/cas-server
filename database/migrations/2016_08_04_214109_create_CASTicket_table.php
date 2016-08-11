<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCASTicketTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('CASTicket', function (Blueprint $table) {
            $table->string('id', 32);
            $table->primary('id');
            $table->bigInteger('authenticationId');
            $table->string('service');
            $table->boolean('renew');
            $table->boolean('used');
            $table->dateTime('createdAt');
            $table->foreign('authenticationId')->references('id')->on('CASAuthentication');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('CASTicket');
    }
}
