# Genvoris Laravel Package — Verification

This document records how to verify the package end-to-end. Run these steps when publishing a new release or after making structural changes.

---

## 1. composer validate

```bash
cd genvoris-laravel
composer validate --strict
```

Expected output: `./composer.json is valid`

---

## 2. Install dev dependencies

```bash
composer install
```

---

## 3. Run the test suite

```bash
composer test
# or directly:
./vendor/bin/phpunit --testdox
```

Expected: all tests passing. Current test files:

| Test file | Suite | What it covers |
|---|---|---|
| `tests/Unit/WebhookVerificationTest.php` | Unit | HMAC verification — valid, wrong secret, tampered body, expired ts, missing header |
| `tests/Unit/ClientTest.php` | Unit | HTTP client — auth header, data unwrapping, 401/404/204, API key not in exceptions |
| `tests/Unit/DataObjectsTest.php` | Unit | All 4 data objects, `canTryOn()` logic |
| `tests/Unit/BladeDirectivesTest.php` | Unit | `api_key` never in output, correct widget URL, XSS escaping |
| `tests/Feature/CustomerResourceTest.php` | Feature | Upsert with prefix, no double-prefix, find |
| `tests/Feature/SessionResourceTest.php` | Feature | Correct endpoint, expiresIn clamping |
| `tests/Feature/WebhookControllerTest.php` | Feature | 200 on valid sig, 401 on invalid, event dispatch |
| `tests/Feature/ProxyControllerTest.php` | Feature | API key injected, not in response, 400 on disallowed/traversal |
| `tests/Feature/ServiceProviderTest.php` | Feature | Facade resolves, config loaded, `widget_url` correct |

---

## 4. Code style

```bash
composer lint:check
# Auto-fix:
composer lint
```

---

## 5. Security checklist

- [ ] `api_key` never appears in exception messages → `ClientTest::test_api_key_not_in_exception_message`
- [ ] `api_key` never rendered by `@genvorisConfig` → `BladeDirectivesTest::test_render_config_never_includes_api_key`
- [ ] Webhook signature uses `hash_equals()`, not `===` → `WebhookVerifier::verify()`
- [ ] Proxy rejects `..` path traversal → `ProxyControllerTest::test_path_traversal_returns_400`
- [ ] Proxy uses hard allowlist → `ProxyControllerTest::test_disallowed_path_returns_400`
- [ ] Widget URL is `api.genvoris.org`, NOT `cdn.genvoris.org` → `BladeDirectivesTest::test_widget_url_is_correct_host`
- [ ] Proxy controller does NOT log request body (manual review — no logging of `$request->getContent()`)

---

## 6. Route spot-check

After installing in a test Laravel app:

```bash
php artisan route:list | grep genvoris
```

Expected output:
```
POST webhooks/genvoris  genvoris.webhook
POST genvoris-proxy/{path}  genvoris.proxy
```

---

## 7. End-to-end smoke test

```bash
# 1. Set keys
GENVORIS_API_KEY=gvk_live_xxx

# 2. Verify API connection
php artisan genvoris:test-connection

# 3. List plans
php artisan genvoris:list-plans

# 4. Test webhook delivery
php artisan genvoris:webhook-test
```

---

## 8. Package structure

```
genvoris-laravel/
├── composer.json               ✅
├── phpunit.xml                 ✅
├── README.md                   ✅
├── CHANGELOG.md                ✅
├── LICENSE                     ✅
├── config/
│   └── genvoris.php            ✅
├── database/migrations/
│   └── create_genvoris_customer_sessions_table.php.stub  ✅
├── resources/views/
│   ├── widget.blade.php        ✅
│   └── components/
│       ├── try-on-button.blade.php  ✅
│       └── try-on-script.blade.php  ✅
├── routes/
│   ├── webhook.php             ✅
│   └── proxy.php               ✅
├── src/
│   ├── Genvoris.php            ✅
│   ├── GenvorisServiceProvider.php  ✅
│   ├── Blade/
│   │   └── GenvorisBladeDirectives.php  ✅
│   ├── Concerns/
│   │   └── HasGenvorisAccess.php  ✅
│   ├── Console/
│   │   ├── InstallCommand.php  ✅
│   │   ├── TestConnectionCommand.php  ✅
│   │   ├── ListPlansCommand.php  ✅
│   │   ├── ListCustomersCommand.php  ✅
│   │   └── WebhookTestCommand.php  ✅
│   ├── DataObjects/
│   │   ├── Customer.php        ✅
│   │   ├── CustomerUsage.php   ✅
│   │   ├── Plan.php            ✅
│   │   └── Session.php         ✅
│   ├── Exceptions/
│   │   ├── ApiException.php    ✅
│   │   ├── AuthException.php   ✅
│   │   ├── GenvorisException.php  ✅
│   │   └── WebhookException.php  ✅
│   ├── Facades/
│   │   └── Genvoris.php        ✅
│   ├── Http/
│   │   ├── Client.php          ✅
│   │   ├── Controllers/
│   │   │   ├── ProxyController.php  ✅
│   │   │   └── WebhookController.php  ✅
│   │   └── Middleware/
│   │       └── VerifyGenvorisWebhook.php  ✅
│   ├── Resources/
│   │   ├── CustomerResource.php  ✅
│   │   ├── PlanResource.php    ✅
│   │   └── SessionResource.php  ✅
│   └── Webhooks/
│       ├── WebhookVerifier.php  ✅
│       └── Events/
│           ├── GenvorisWebhookReceived.php  ✅
│           ├── CustomerCreated.php  ✅
│           ├── CustomerUpdated.php  ✅
│           ├── CustomerCancelled.php  ✅
│           ├── CustomerQuotaWarning.php  ✅
│           ├── CustomerQuotaExhausted.php  ✅
│           ├── CustomerPeriodRolled.php  ✅
│           ├── PlanCreated.php  ✅
│           ├── PlanUpdated.php  ✅
│           └── PlanDisabled.php  ✅
├── tests/
│   ├── TestCase.php            ✅
│   ├── Unit/
│   │   ├── WebhookVerificationTest.php  ✅
│   │   ├── ClientTest.php      ✅
│   │   ├── DataObjectsTest.php  ✅
│   │   └── BladeDirectivesTest.php  ✅
│   └── Feature/
│       ├── CustomerResourceTest.php  ✅
│       ├── SessionResourceTest.php  ✅
│       ├── WebhookControllerTest.php  ✅
│       ├── ProxyControllerTest.php  ✅
│       └── ServiceProviderTest.php  ✅
└── .github/workflows/
    ├── tests.yml               ✅
    └── publish.yml             ✅
```

---

## Known limitations / next steps

- `HasGenvorisAccess::hasLocalTable()` uses a static local variable — in long-running processes (Octane, queue workers), the cached result persists across requests. This is intentional (table presence doesn't change at runtime).
- The `cancel()` method in `CustomerResource` returns a partially-constructed `Customer` with only `id` populated (the DELETE returns HTTP 204 with no body). Callers should not rely on other fields after cancellation.
- `WebhookTestCommand` sends to the webhook URL via HTTP — ensure the app is running locally when using this command in development.
