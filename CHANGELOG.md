# Changelog

All notable changes to `genvoris/laravel` will be documented in this file.

## [1.1.0] - 2026-06-23

### Added
- Same-origin proxy support for hosted-widget analytics at `api/v1/events`.
- Configurable proxy upstream and documented environment aliases for relaunch/staging setups.
- Latest hosted-widget Blade attributes for proxy URLs, event URLs, platform, no-FAB mode, and customer tokens.
- Current portal webhook event classes for try-on, customer, and credit lifecycle events.

### Changed
- Proxy routes now support GET, POST, PUT, PATCH, DELETE, and OPTIONS while preserving the hard allowlist.
- Proxy responses preserve upstream body/content type and avoid attaching non-portable request bodies to GET/OPTIONS.
- Browser-rendered Laravel helpers continue to strip sensitive API key and webhook secret fields.

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
