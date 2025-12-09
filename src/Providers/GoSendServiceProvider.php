<?php

namespace ESolution\GoSend\Providers;

use Illuminate\Support\ServiceProvider;
use ESolution\GoSend\Contracts\GoSendClientInterface;
use ESolution\GoSend\Services\GoSendHttpClient;
use Illuminate\Http\Client\Factory as HttpFactory;

class GoSendServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/../../config/gosend.php', 'gosend');

        $this->app->singleton(GoSendClientInterface::class, function ($app) {
            return new GoSendHttpClient(
                httpClient: $app->make(HttpFactory::class),
                config: $app['config']->get('gosend', [])
            );
        });

        $this->app->alias(GoSendClientInterface::class, 'gosend.client');
    }

    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../../config/gosend.php' => config_path('gosend.php'),
        ], 'gosend-config');

        if (! class_exists('CreateGosendDeliveriesTable')) {
            $this->publishes([
                __DIR__ . '/../../database/migrations/2025_01_01_000000_create_gosend_deliveries_table.php' =>
                database_path('migrations/' . date('Y_m_d_His') . '_create_gosend_deliveries_table.php'),
            ], 'gosend-migrations');
        }

        $this->loadMigrationsFrom(__DIR__ . '/../../database/migrations');

        if (config('gosend.routes.enabled', true)) {
            $this->loadRoutesFrom(__DIR__ . '/../../routes/api.php');
        }
    }
}
