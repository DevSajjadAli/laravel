<?php

namespace Genvoris\Laravel;

use Genvoris\Laravel\Blade\GenvorisBladeDirectives;
use Genvoris\Laravel\Console\InstallCommand;
use Genvoris\Laravel\Console\ListCustomersCommand;
use Genvoris\Laravel\Console\ListPlansCommand;
use Genvoris\Laravel\Console\TestConnectionCommand;
use Genvoris\Laravel\Console\WebhookTestCommand;
use Genvoris\Laravel\Http\Client;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class GenvorisServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/genvoris.php', 'genvoris');

        $this->app->singleton(Client::class, function ($app) {
            $config = $app['config']['genvoris'];

            return new Client(
                apiKey: $config['api_key'],
                baseUrl: rtrim($config['api_base_url'], '/'),
                timeout: $config['timeout'],
                retryTimes: $config['retry']['times'],
                retrySleepMs: $config['retry']['sleep'],
            );
        });

        $this->app->singleton(Genvoris::class, function ($app) {
            return new Genvoris($app->make(Client::class));
        });

        $this->app->bind('genvoris', function ($app) {
            return $app->make(Genvoris::class);
        });
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/genvoris.php' => config_path('genvoris.php'),
            ], 'genvoris-config');

            $this->publishes([
                __DIR__.'/../resources/views' => resource_path('views/vendor/genvoris'),
            ], 'genvoris-views');

            $this->publishes([
                __DIR__.'/../database/migrations/create_genvoris_customer_sessions_table.php.stub' => database_path(
                    'migrations/'.date('Y_m_d_His').'_create_genvoris_customer_sessions_table.php'
                ),
            ], 'genvoris-migrations');

            $this->commands([
                InstallCommand::class,
                TestConnectionCommand::class,
                ListPlansCommand::class,
                ListCustomersCommand::class,
                WebhookTestCommand::class,
            ]);
        }

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'genvoris');

        GenvorisBladeDirectives::register();

        // Register event listeners from config
        foreach (config('genvoris.webhook.listeners', []) as $eventType => $listenerClass) {
            Event::listen($eventType, $listenerClass);
        }

        // Auto-register webhook route
        if (config('genvoris.webhook.auto_register', true)) {
            Route::middleware(config('genvoris.webhook.middleware', []))
                ->prefix(config('genvoris.webhook.path', 'webhooks/genvoris'))
                ->group(__DIR__.'/../routes/webhook.php');
        }

        // Auto-register proxy routes
        if (config('genvoris.proxy.auto_register', true)) {
            Route::middleware(config('genvoris.proxy.middleware', []))
                ->prefix(config('genvoris.proxy.path', 'genvoris-proxy'))
                ->group(__DIR__.'/../routes/proxy.php');
        }
    }
}
