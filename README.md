# Genvoris Laravel

[![Latest Version on Packagist](https://img.shields.io/packagist/v/genvoris/laravel.svg)](https://packagist.org/packages/genvoris/laravel)
[![Tests](https://github.com/DevSajjadAli/laravel/actions/workflows/tests.yml/badge.svg)](https://github.com/DevSajjadAli/laravel/actions/workflows/tests.yml)
[![PHP Version Require](https://img.shields.io/packagist/php-v/genvoris/laravel.svg)](https://packagist.org/packages/genvoris/laravel)
[![License](https://img.shields.io/github/license/DevSajjadAli/laravel.svg)](LICENSE)

Official Laravel integration for the [Genvoris Virtual Try-On](https://genvoris.org) platform.

Add virtual try-on experiences to your Laravel application in minutes: upsert customers, mint session tokens, proxy widget requests server-side, and handle webhooks — all with zero client-side API key exposure.

---

## Requirements

| Dependency | Version |
|---|---|
| PHP | ^8.1 |
| Laravel | ^10.0 \| ^11.0 \| ^12.0 |

---

## Installation

```bash
composer require genvoris/laravel
```

Run the install command to publish config and add `.env` keys:

```bash
php artisan genvoris:install
```

Add your keys to `.env`:

```dotenv
GENVORIS_API_KEY=gvk_live_your_key_here
GENVORIS_WEBHOOK_SECRET=your_webhook_secret_here
```

Verify the connection:

```bash
php artisan genvoris:test-connection
```

---

## Configuration

The published `config/genvoris.php` file exposes all options. The most important:

| Key | Env var | Default | Description |
|---|---|---|---|
| `api_key` | `GENVORIS_API_KEY` | `""` | Your platform API key |
| `api_base_url` | `GENVORIS_API_URL` or `GENVORIS_API_BASE_URL` | `https://genvoris.org/api/v1` | Portal API base for server SDK calls |
| `timeout` | `GENVORIS_TIMEOUT` | `30` | HTTP timeout (seconds) |
| `retry.times` | — | `3` | Max retries on 429 / 5xx |
| `webhook.secret` | `GENVORIS_WEBHOOK_SECRET` | `""` | HMAC secret for signatures |
| `webhook.path` | `GENVORIS_WEBHOOK_PATH` | `webhooks/genvoris` | Route prefix |
| `webhook.auto_register` | — | `true` | Auto-register webhook route |
| `proxy.path` | `GENVORIS_PROXY_PATH` | `genvoris-proxy` | Route prefix |
| `proxy.upstream` | `GENVORIS_PROXY_UPSTREAM`, `GENVORIS_TRYON_UPSTREAM`, or `TRYON_BACKEND_URL` | `https://api.genvoris.org` | Try-on/widget upstream for proxied browser calls |
| `proxy.auto_register` | — | `true` | Auto-register proxy route |
| `proxy.allowed_paths` | — | `[api/analyze, api/tryon, api/config, api/status, api/v1/events]` | Strict forwarding allowlist |
| `proxy.events_path` | — | `api/v1/events` | Widget analytics path forwarded with server-side API key |
| `external_id_prefix` | `GENVORIS_EXTERNAL_ID_PREFIX` | `laravel_` | Prefix on external customer IDs |
| `widget_url` | `GENVORIS_WIDGET_URL` | `https://api.genvoris.org/widget.js` | Widget script URL |
| `cache.sessions` | — | `true` | Cache minted session tokens |
| `cache.ttl` | — | `840` | Session cache TTL (seconds) |

---

## Basic Usage

### Facade

```php
use Genvoris\Laravel\Facades\Genvoris;

// Upsert a customer (auto-prefixes the external ID → "laravel_42")
$customer = Genvoris::upsertCustomer('42', ['email' => 'alice@example.com']);

// Mint a session token for the widget
$session = Genvoris::mintSession($customer->id);

// List your plans
$plans = Genvoris::listPlans();

// Get a customer's usage
$usage = Genvoris::customerUsage($customer->id);
if ($usage->canTryOn()) { /* ... */ }
```

### Dependency injection

```php
use Genvoris\Laravel\Genvoris;

class TryOnController extends Controller
{
    public function __construct(private readonly Genvoris $genvoris) {}

    public function session(Request $request): JsonResponse
    {
        $session = $this->genvoris->mintSession($request->user()->genvorisCustomerId());
        return response()->json(['token' => $session->token]);
    }
}
```

---

## HasGenvorisAccess Trait

Add the trait to your `User` model (or any Eloquent model) to get Genvoris helpers:

```php
use Genvoris\Laravel\Concerns\HasGenvorisAccess;

class User extends Authenticatable
{
    use HasGenvorisAccess;
}
```

Available methods:

```php
// Returns "laravel_{id}"
$user->genvorisExternalId();

// Upsert the user in the Genvoris platform (auto-syncs email if present)
$customer = $user->syncToGenvoris(['planId' => 'plan_basic']);

// Mint a session token (syncs first if needed)
$session = $user->genvorisSession(expiresIn: 900);

// Usage & entitlement
$usage = $user->genvorisUsage();
$user->canTryOn(); // bool — returns false gracefully on API errors

// Full portal Customer object
$customer = $user->genvorisPortalCustomer();
```

### Optional local cache table

Run the optional migration to cache customer IDs and avoid repeated API calls:

```bash
php artisan vendor:publish --tag=genvoris-migrations
php artisan migrate
```

---

## Blade Directives

```blade
{{-- Render a contextual button. It uses data-genvoris-trigger for the hosted widget. --}}
@genvorisTryOnButton([
    'productId' => $product->id,
    'productTitle' => $product->name,
    'productImage' => $product->image_url,
    'productCategory' => $product->category?->name,
    'price' => $product->price,
    'currency' => 'USD',
    'label' => 'Try On',
])

{{-- Emit window.genvorisConfig (never exposes api_key) --}}
@genvorisConfig(['productId' => $product->id, 'token' => $session->token])

{{-- Load the hosted widget with same-origin proxy + events URLs. --}}
@genvorisScripts(['token' => $session->token, 'noFab' => true])

{{-- Combined shorthand: config + script with no floating FAB by default. --}}
@genvorisWidget(['productId' => $product->id, 'token' => $session->token])
```

Or use the Blade views directly:

```blade
@include('genvoris::widget', [
    'productId' => $product->id,
    'productTitle' => $product->name,
    'productImage' => $product->image_url,
    'token' => $session->token,
])
@include('genvoris::components.try-on-button', ['productId' => $product->id])
```

The rendered script includes `data-api-url`, `data-events-url`, `data-platform="laravel"`, and customer token attributes for the hosted widget. Your `GENVORIS_API_KEY` is never printed into HTML.

---

## Webhooks

Register your endpoint in the Genvoris dashboard:

```
POST https://yourapp.com/webhooks/genvoris
```

The package auto-registers this route and verifies the HMAC-SHA256 signature on every request.

### Listening to events

```php
// In a service provider or EventServiceProvider
use Genvoris\Laravel\Webhooks\Events\CustomerCreated;
use Genvoris\Laravel\Webhooks\Events\GenvorisWebhookReceived;

Event::listen(CustomerCreated::class, function (CustomerCreated $event) {
    $payload = $event->payload;
    // create local user, send welcome email, etc.
});

// Catch all events
Event::listen(GenvorisWebhookReceived::class, function (GenvorisWebhookReceived $event) {
    Log::info("Genvoris webhook: {$event->type} ({$event->id})");
});
```

Or declare listeners in `config/genvoris.php`:

```php
'webhook' => [
    'listeners' => [
        \Genvoris\Laravel\Webhooks\Events\CustomerCreated::class => [
            \App\Listeners\HandleGenvorisCustomerCreated::class,
        ],
    ],
],
```

### Supported event types

| Event type | PHP class |
|---|---|
| `tryon.completed` | `TryOnCompleted` |
| `tryon.failed` | `TryOnFailed` |
| `customer.plan_changed` | `CustomerPlanChanged` |
| `customer.quota_exhausted` | `CustomerQuotaExhausted` |
| `credit.low_balance` | `CreditLowBalance` |
| `credit.balance_added` | `CreditBalanceAdded` |
| `end_customer.created` | `CustomerCreated` |
| `end_customer.updated` | `CustomerUpdated` |
| `end_customer.cancelled` | `CustomerCancelled` |
| `end_customer.quota_warning` | `CustomerQuotaWarning` |
| `end_customer.quota_exhausted` | `CustomerQuotaExhausted` |
| `end_customer.period_rolled` | `CustomerPeriodRolled` |
| `plan.created` | `PlanCreated` |
| `plan.updated` | `PlanUpdated` |
| `plan.disabled` | `PlanDisabled` |

All event classes are in the `Genvoris\Laravel\Webhooks\Events` namespace.

### Manual verification

```php
use Genvoris\Laravel\Webhooks\WebhookVerifier;

$ok = (new WebhookVerifier())->verify(
    $request->getContent(),
    $request->header('X-Genvoris-Signature'),
    config('genvoris.webhook.secret'),
);
```

---

## Proxy

The package registers `/genvoris-proxy/{path}` for `GET`, `POST`, `PUT`, `PATCH`, `DELETE`, and `OPTIONS`. It injects your API key server-side before forwarding to `api.genvoris.org`. Only paths in the `proxy.allowed_paths` allowlist are forwarded, including `api/v1/events` for hosted widget analytics.

The hosted widget should call the same-origin proxy, not the Genvoris upstream directly from the browser:

```html
<script
  src="https://api.genvoris.org/widget.js?no_fab=1"
  data-api-url="/genvoris-proxy/"
  data-events-url="/genvoris-proxy/api/v1/events"
  data-platform="laravel"
  data-token="{{ $session->token }}"
  data-no-fab="true"
  defer
></script>
```

`@genvorisScripts` and `@genvorisWidget` emit this automatically. The browser receives only the same-origin proxy URL and short-lived customer token; the merchant API key stays in `.env`.

---

## Artisan Commands

| Command | Description |
|---|---|
| `genvoris:install` | Publish config, views, migration; add `.env` keys |
| `genvoris:test-connection` | Verify API key by listing plans |
| `genvoris:list-plans` | Display plan table |
| `genvoris:list-customers` | Display customer table (`--limit`, `--page`) |
| `genvoris:webhook-test` | Send a signed test webhook to your endpoint |

---

## Testing

Install dev dependencies and run the suite:

```bash
composer install
composer test
```

For a consuming Laravel app, clear cached config/routes after upgrading:

```bash
php artisan config:clear
php artisan route:clear
php artisan config:cache
php artisan genvoris:test-connection
php artisan route:list | grep genvoris
```

Run code style checks:

```bash
composer lint:check   # check only
composer lint         # auto-fix
```

---

## Changelog

See [CHANGELOG.md](CHANGELOG.md) for a list of changes.

---

## Contributing

Bug reports and pull requests are welcome at the project's GitHub repository.  
Before submitting a PR, please run `composer test` and `composer lint:check`.

---

## License

MIT — see [LICENSE](LICENSE).

---

## Support

- Docs: [https://docs.genvoris.org](https://docs.genvoris.org)
- Email: [support@genvoris.org](mailto:support@genvoris.org)
