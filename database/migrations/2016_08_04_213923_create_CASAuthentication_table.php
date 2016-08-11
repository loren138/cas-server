<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateCASAuthenticationTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('CASAuthentication', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('username');
            $table->string('attributeJson')->nullable();
            $table->boolean('sso');
            $table->dateTime('lastUsed');
            $table->dateTime('createdAt');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('CASAuthentication');
    }
}
