<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /*
         * CREATE TABLE dbo.sessions (
        id varchar(510) PRIMARY KEY NOT NULL,
        user_id INT NULL,
        ip_address varchar(45) NULL,
        user_agent TEXT NULL,
        payload nvarchar(MAX) NOT NULL,
        last_activity INT NOT NULL
    );
     GRANT SELECT on dbo.sessions to CAS_User;
     GRANT INSERT on dbo.sessions to CAS_User;
     GRANT UPDATE on dbo.sessions to CAS_User;
     GRANT DELETE on dbo.sessions to CAS_User;
         */
        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->unique();
            $table->integer('user_id')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('payload');
            $table->integer('last_activity');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::drop('sessions');
    }
}
