<?php

namespace Loren138\CASServer;

use Illuminate\Routing\Router;
use Illuminate\Support\ServiceProvider;
use Loren138\CASServer\Console\Cleanup;

class CASServerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @param Router $router
     */
    public function boot(Router $router)
    {
        if (!$this->app->routesAreCached()) {
            $l = app();
            $version = explode('.', $l::VERSION);
            if (intval($version[0], 10) === 5 && intval($version[1], 10) > 1) {
                $router->group([
                    'middleware' => 'web',
                ], function () {
                    require __DIR__.'/../resources/routes.php';
                });
            } else {
                require __DIR__.'/../resources/routes.php';
            }
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
            __DIR__.'/../public/vendor/casserver' => public_path('vendor/casserver'),
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
