<?php

namespace Loren138\CASServer;

use Illuminate\Support\ServiceProvider;
use Loren138\CASServer\Console\Cleanup;

class CASServerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
        if (!$this->app->routesAreCached()) {
            require __DIR__.'/../resources/routes.php';
        }
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'casserver');
        $this->loadViewsFrom(__DIR__.'/../resources/xml', 'casserverxml');

        $this->publishes([
            __DIR__.'/../config/casserver.php' => config_path('casserver.php')
        ], 'config');

        $this->publishes([
            __DIR__.'/../database/migrations/' => database_path('migrations')
        ], 'migrations');

        $this->publishes([
            __DIR__.'/public/vendor/casserver' => public_path('vendor/casserver'),
        ], 'public');

        $this->publishes([
            __DIR__.'/../resources/views' => base_path('resources/views/vendor/casserver'),
        ], 'views');
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/casserver.php',
            'casserver'
        );
        $this->app['command.cas-server.cleanup'] = $this->app->share(
            function () {
                return new Cleanup();
            }
        );
        $this->commands('command.cas-server.cleanup');
    }
}
