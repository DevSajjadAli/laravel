<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API Key
    |--------------------------------------------------------------------------
    | Your Genvoris API key (starts with gvk_live_ or gvk_test_).
    | Never commit this value. Set GENVORIS_API_KEY in your .env file.
    */
    'api_key' => env('GENVORIS_API_KEY', ''),

    /*
    |--------------------------------------------------------------------------
    | API Base URL
    |--------------------------------------------------------------------------
    | Override for staging / self-hosted deployments.
    | Test environment: https://test.genvoris.org/api/v1
    */
    'api_base_url' => env('GENVORIS_API_URL', env('GENVORIS_API_BASE_URL', 'https://genvoris.org/api/v1')),

    /*
    |--------------------------------------------------------------------------
    | Request Timeout
    |--------------------------------------------------------------------------
    | Per-request timeout in seconds.
    */
    'timeout' => (int) env('GENVORIS_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Retry Configuration
    |--------------------------------------------------------------------------
    | Matches Node SDK backoff schedule: 200ms * 4^attempt ± 30% jitter.
    | Only retries on HTTP 429 or 5xx. Never retries other 4xx errors.
    */
    'retry' => [
        'times' => 3,
        'sleep' => [200, 800, 3200], // ms per attempt (attempt 0, 1, 2)
    ],

    /*
    |--------------------------------------------------------------------------
    | Webhook Configuration
    |--------------------------------------------------------------------------
    | secret: HMAC-SHA256 secret issued by the portal. Never commit.
    | auto_register: load routes/webhook.php automatically from the service provider.
    | path: URL prefix for the webhook endpoint.
    | middleware: array of middleware to apply to the webhook route.
    | listeners: map of event type strings to listener class names.
    |   Example: ['end_customer.created' => App\Listeners\OnCustomerCreated::class]
    */
    'webhook' => [
        'secret' => env('GENVORIS_WEBHOOK_SECRET', ''),
        'auto_register' => true,
        'path' => env('GENVORIS_WEBHOOK_PATH', 'webhooks/genvoris'),
        'middleware' => [],
        'listeners' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Proxy Configuration
    |--------------------------------------------------------------------------
    | auto_register: load routes/proxy.php automatically from the service provider.
    | path: URL prefix for the proxy endpoint.
    | middleware: middleware applied to proxy routes (throttle is strongly recommended).
    | allowed_paths: paths that the proxy will forward. Requests to any other path
    |   are rejected with 400. Do not remove entries without consulting the docs.
    | enforce_origin: when true, reject requests where the Origin/Referer host
    |   does not match APP_URL. Disable only in development.
    */
    'proxy' => [
        'auto_register' => true,
        'path' => env('GENVORIS_PROXY_PATH', 'genvoris-proxy'),
        'upstream' => env(
            'GENVORIS_PROXY_UPSTREAM',
            env('GENVORIS_TRYON_UPSTREAM', env('TRYON_BACKEND_URL', 'https://api.genvoris.org'))
        ),
        'middleware' => ['throttle:60,1'],
        'allowed_paths' => [
            'api/analyze',    // 60 req/min upstream rate limit
            'api/tryon',      // 10 req/min upstream rate limit
            'api/config',     // 120 req/min upstream rate limit
            'api/status',     // 120 req/min upstream rate limit
            'api/v1/events',  // widget analytics; API key injected server-side
        ],
        'events_path' => 'api/v1/events',
        'enforce_origin' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | External ID Prefix
    |--------------------------------------------------------------------------
    | Prepended to your local user ID when upserting Genvoris customers.
    | Must be unique per integration type. Do not change after first deploy.
    | WooCommerce uses "wp_", Shopify uses "shopify_".
    */
    'external_id_prefix' => env('GENVORIS_EXTERNAL_ID_PREFIX', 'laravel_'),

    /*
    |--------------------------------------------------------------------------
    | Widget JavaScript URL
    |--------------------------------------------------------------------------
    | URL of the Genvoris widget loader script. Do not change unless instructed
    | by Genvoris support. Must always point to api.genvoris.org, never cdn.genvoris.org.
    */
    'widget_url' => env('GENVORIS_WIDGET_URL', 'https://api.genvoris.org/widget.js'),

    /*
    |--------------------------------------------------------------------------
    | Session Caching
    |--------------------------------------------------------------------------
    | When enabled, minted session tokens are cached until ~60 seconds before
    | expiry, avoiding one API call per page render for the same customer.
    | store: null = use the default Laravel cache driver.
    | ttl: seconds to keep in cache (default 840 = 900 - 60s safety margin).
    */
    'cache' => [
        'sessions' => true,
        'store' => null,
        'ttl' => 840,
    ],

];
