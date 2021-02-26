<?php

namespace Butler\Graphql;

use Illuminate\Foundation\Application as LaravelApplication;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;
use Laravel\Lumen\Application as LumenApplication;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(
            \GraphQL\Executor\Promise\PromiseAdapter::class,
            \GraphQL\Executor\Promise\Adapter\AmpPromiseAdapter::class
        );
    }

    /**
     * Boot the service provider.
     *
     * @return void
     */
    public function boot()
    {
        $this->setupConfig($this->app);
    }

    private function setupConfig($app)
    {
        $source = realpath(__DIR__ . '/../config/butler.php');

        if ($app instanceof LaravelApplication && $app->runningInConsole()) {
            $this->publishes([$source => config_path('butler.php')]);
        } elseif ($app instanceof LumenApplication) {
            $app->configure('butler');
        }

        $this->mergeConfigFrom($source, 'butler');
    }
}
