<?php

namespace Genvoris\Laravel;

use Genvoris\Laravel\Blade\GenvorisBladeDirectives;
use Genvoris\Laravel\Console\InstallCommand;
use Genvoris\Laravel\Console\ListCustomersCommand;
use Genvoris\Laravel\Console\ListPlansCommand;
use Genvoris\Laravel\Console\TestConnectionCommand;
use Genvoris\Laravel\Console\WebhookTestCommand;
use Genvoris\Laravel\Http\Client;
use Genvoris\Laravel\Http\Middleware\VerifyGenvorisWebhook;
use Illuminate\Routing\Router;
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

            // SEC LARAVEL-07: fail fast with a *useful* error instead of
            // letting an unconfigured client ship empty Bearer tokens to
            // the Genvoris API (and get back opaque 401s). Validation runs
            // at *resolution* time, not register/boot, so `vendor:publish`
            // and `genvoris:install` still work on a fresh install before
            // the user has set GENVORIS_API_KEY.
            $this->assertConfig($config);

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

        // Phase 5: register a short middleware alias so consumer apps can
        // reference VerifyGenvorisWebhook as 'genvoris.webhook' in their
        // own route definitions (instead of importing and writing the
        // class FQN). The package's own auto-registered webhook route
        // doesn't need this -- it already uses the FQN via config -- but
        // userland routes that want to defend custom webhook endpoints
        // with the same HMAC check now have a clean idiom:
        //
        //   Route::post('/my-webhook', ...)->middleware('genvoris.webhook');
        //
        // Aliasing requires the HTTP kernel's router, which isn't
        // available in console-only runs (e.g. `php artisan package:discover`)
        // before the kernel boots -- guard with method_exists so install
        // doesn't blow up on fresh consumers.
        $router = $this->app->make(Router::class);
        if (method_exists($router, 'aliasMiddleware')) {
            $router->aliasMiddleware('genvoris.webhook', VerifyGenvorisWebhook::class);
        }

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

    /**
     * Validate the resolved genvoris config. Throws a descriptive
     * RuntimeException when required values are missing or malformed so
     * the developer sees the real problem in their logs instead of an
     * HTTP 401 from Genvoris.
     *
     * @param  array<string, mixed>  $config
     *
     * @throws \RuntimeException
     */
    protected function assertConfig(array $config): void
    {
        if (empty($config['api_key']) || ! is_string($config['api_key'])) {
            throw new \RuntimeException(
                'Genvoris API key not configured. Set GENVORIS_API_KEY in your .env file '
                .'or publish + edit config/genvoris.php.'
            );
        }

        if (empty($config['api_base_url']) || ! is_string($config['api_base_url'])
            || ! preg_match('#^https?://#i', $config['api_base_url'])) {
            throw new \RuntimeException(
                'Genvoris api_base_url must be an absolute http(s) URL. '
                .'Got: '.var_export($config['api_base_url'] ?? null, true)
            );
        }

        if (! isset($config['timeout']) || ! is_int($config['timeout']) || $config['timeout'] <= 0) {
            throw new \RuntimeException(
                'Genvoris timeout must be a positive integer (seconds). '
                .'Got: '.var_export($config['timeout'] ?? null, true)
            );
        }

        $retry = $config['retry'] ?? null;
        if (! is_array($retry)
            || ! isset($retry['times']) || ! is_int($retry['times']) || $retry['times'] < 0
            || ! isset($retry['sleep']) || ! is_array($retry['sleep'])) {
            throw new \RuntimeException(
                'Genvoris retry config must be ["times" => int >= 0, "sleep" => array<int>].'
            );
        }
    }
}
