<?php

namespace IODigital\ABlockLaravel\Providers;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use IODigital\ABlockPHP\ABlockClient;
use IODigital\ABlockLaravel\AWallet;

class ABlockServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ABlockClient::class, function (Application $app) {
            return new ABlockClient(
                computeHost: config('a-block.compute_host'),
                intercomHost: config('a-block.intercom_host'),
            );
        });

        $this->app->bind('a-wallet', function () {
            return new AWallet(
                client: $this->app->make(ABlockClient::class)
            );
        });
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__.'/../../config/a-block.php' => config_path('a-block.php'),
        ], 'a-block-config');

        $this->loadMigrationsFrom(
            __DIR__.'/../../database/migrations'
        );

        if ($this->app->runningInConsole()) {
            $this->commands([
                // InstallCommand::class,
            ]);
        }
    }
}
