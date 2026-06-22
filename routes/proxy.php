<?php

use Genvoris\Laravel\Http\Controllers\ProxyController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Genvoris Proxy Routes
|--------------------------------------------------------------------------
| Registered automatically by GenvorisServiceProvider when
| config('genvoris.proxy.auto_register') is true.
|
| Default URL: /genvoris-proxy/{path}
|
| The wildcard {path} is validated against the allowed_paths allowlist
| inside ProxyController — requests to unlisted paths receive 400.
*/

Route::match(['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], '{path}', [ProxyController::class, 'handle'])
    ->where('path', '.*')
    ->name('genvoris.proxy');
