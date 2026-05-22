# Changelog

All notable changes to `genvoris/laravel` will be documented in this file.

## [1.0.0] - 2024-01-01

### Added
- Initial release
- `HasGenvorisAccess` trait for Eloquent models
- HTTP client with exponential backoff retry (matches Node SDK schedule)
- `CustomerResource`, `PlanResource`, `SessionResource` wrapping Genvoris API v1
- Webhook HMAC-SHA256 verifier + `WebhookController` dispatching 9 typed Laravel events
- Proxy controller forwarding widget requests with server-side API key injection
- Blade directives: `@genvorisScripts`, `@genvorisConfig`, `@genvorisWidget`, `@genvorisTryOnButton`
- Artisan commands: `genvoris:install`, `genvoris:test-connection`, `genvoris:list-plans`, `genvoris:list-customers`, `genvoris:webhook-test`
- Optional `genvoris_customer_sessions` migration
- Laravel auto-discovery for service provider and `Genvoris` facade
