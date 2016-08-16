<?php

namespace Loren138\CASServerTests;

trait DatabaseMigrations
{
    /**
     * Define hooks to migrate the database before and after each test.
     *
     * @return void
     */
    public function runDatabaseMigrations()
    {
        $this->artisan('migrate', [
            '--realpath' => realpath(__DIR__.'/../database/migrations'),
        ]);

        $this->beforeApplicationDestroyed(function () {
            $this->artisan('migrate:rollback');
        });
    }
}
