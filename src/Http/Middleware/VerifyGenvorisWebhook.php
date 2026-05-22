<?php

namespace Genvoris\Laravel\Http\Middleware;

use Closure;
use Genvoris\Laravel\Webhooks\WebhookVerifier;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reads the raw request body BEFORE any JSON decoding and verifies the
 * Genvoris HMAC-SHA256 signature. Aborts with 401 on failure.
 *
 * IMPORTANT: This middleware must be placed before any middleware that
 * reads/parses the request body, or the raw bytes used for HMAC will be
 * corrupted. When auto-registered by the service provider, this is handled
 * automatically.
 */
class VerifyGenvorisWebhook
{
    public function __construct(private readonly WebhookVerifier $verifier) {}

    public function handle(Request $request, Closure $next): Response
    {
        $rawBody = $request->getContent();
        $header = $request->header('X-Genvoris-Signature', '');
        $secret = config('genvoris.webhook.secret', '');

        if (! $this->verifier->verify($rawBody, $header, $secret)) {
            abort(401, 'Webhook signature verification failed.');
        }

        return $next($request);
    }
}
