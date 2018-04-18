<?php

namespace Butler\Graphql;

use Illuminate\Foundation\Application as LaravelApplication;
use Laravel\Lumen\Application as LumenApplication;
use Illuminate\Support\ServiceProvider as BaseServiceProvider;

class ServiceProvider extends BaseServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->make(GraphqlController::class);

        $this->app->bind(Lexer::class, function () {
            return new Lexer(
                config('graphql.namespaces', [])
            );
        });
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
        $source = realpath(__DIR__ . '/../config/graphql.php');

        if ($app instanceof LaravelApplication && $app->runningInConsole()) {
            $this->publishes([$source => config_path('graphql.php')]);
        } elseif ($app instanceof LumenApplication) {
            $app->configure('graphql');
        }

        $this->mergeConfigFrom($source, 'graphql');
    }
}
