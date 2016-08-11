<?php

namespace Loren138\CASServerTests;

use Carbon\Carbon;

class TestCase extends \Orchestra\Testbench\TestCase
{
    /**
     * The base URL to use while testing the application.
     *
     * @var string
     */
    protected $baseUrl = 'http://test.org';

    public function testNothing()
    {
        $this->assertSame(true, true);
    }

    public function setUp()
    {
        $uses = array_flip(class_uses_recursive(static::class));

        parent::setUp();
        if (isset($uses[DatabaseMigrations::class])) {
            $this->runDatabaseMigrations();
        }
    }

    protected function getPackageAliases($app)
    {
        return [
            'Form' => 'Collective\Html\FormFacade',
            //'YourPackage' => 'YourProject\YourPackage\Facades\YourPackage',
        ];
    }

    protected function getPackageProviders($app)
    {
        return ['Loren138\CASServer\CASServerServiceProvider', 'Collective\Html\HtmlServiceProvider'];
    }

    /**
     * Define environment setup.
     *
     * @param  \Illuminate\Foundation\Application  $app
     * @return void
     */
    protected function getEnvironmentSetUp($app)
    {
        parent::getEnvironmentSetUp($app);
        $app->make('Illuminate\Contracts\Http\Kernel')->pushMiddleware('Illuminate\Session\Middleware\StartSession');
        // Setup default database to use sqlite :memory:
        $app['config']->set('database.default', 'test');
        $app['config']->set('database.connections.test', [
            'driver'   => 'sqlite',
            'database' => ':memory:',
            'prefix'   => '',
        ]);
    }


    public function tearDown()
    {
        parent::tearDown();
        Carbon::setTestNow();
    }
}
