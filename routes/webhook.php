<?php

use Genvoris\Laravel\Http\Controllers\WebhookController;
use Genvoris\Laravel\Http\Middleware\VerifyGenvorisWebhook;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Genvoris Webhook Route
|--------------------------------------------------------------------------
| Registered automatically by GenvorisServiceProvider when
| config('genvoris.webhook.auto_register') is true.
|
| Default URL: POST /webhooks/genvoris
|
| CSRF note: this route is loaded outside the 'web' middleware group
| so CSRF validation does not apply. If your application applies CSRF
| globally via a custom route service provider, add 'webhooks/genvoris'
| to the $except array on your VerifyCsrfToken middleware.
*/

Route::post('/', [WebhookController::class, 'handle'])
    ->middleware(VerifyGenvorisWebhook::class)
    ->name('genvoris.webhook');
