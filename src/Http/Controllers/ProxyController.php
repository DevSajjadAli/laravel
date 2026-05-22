<?php

namespace Genvoris\Laravel\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Forwards widget-layer requests to api.genvoris.org, injecting the
 * server-side API key so it never reaches the browser.
 *
 * Security properties:
 *   - Hard allowlist of forwarding paths — no arbitrary proxy.
 *   - Optional origin check against APP_URL.
 *   - API key injected server-side and never included in responses.
 *   - Request body is NOT logged (may contain multi-MB base64 images).
 */
class ProxyController extends Controller
{
    /**
     * The upstream API host. All allowed paths are relative to this.
     */
    private const UPSTREAM_HOST = 'https://api.genvoris.org';

    public function handle(Request $request, string $path): JsonResponse
    {
        // Normalize and sanitize path
        $path = ltrim($path, '/');

        // Reject path traversal
        if (str_contains($path, '..') || str_contains($path, "\0")) {
            return response()->json(['error' => 'Invalid path.'], 400);
        }

        // Allowlist check
        $allowedPaths = config('genvoris.proxy.allowed_paths', []);
        if (! in_array($path, $allowedPaths, true)) {
            return response()->json(['error' => 'Path not allowed.'], 400);
        }

        // Origin / Referer check (protects against cross-site request forgery
        // on the proxy endpoint itself — different from CSRF token verification)
        if (config('genvoris.proxy.enforce_origin', true)) {
            $appHost = parse_url(config('app.url', ''), PHP_URL_HOST) ?? '';

            if ($appHost !== '') {
                $origin = $request->header('Origin', '');
                $referer = $request->header('Referer', '');
                $source = $origin ?: $referer;

                if ($source !== '') {
                    $sourceHost = parse_url($source, PHP_URL_HOST) ?? '';
                    // Strip port for comparison
                    $sourceHost = explode(':', $sourceHost)[0];
                    $appHost = explode(':', $appHost)[0];

                    if ($sourceHost !== $appHost) {
                        return response()->json(['error' => 'Origin not allowed.'], 403);
                    }
                }
            }
        }

        $upstreamUrl = self::UPSTREAM_HOST.'/'.$path;
        $apiKey = config('genvoris.api_key', '');
        $timeout = config('genvoris.timeout', 30);

        try {
            $upstreamResponse = Http::withHeaders([
                'X-API-Key' => $apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ])
                ->timeout($timeout)
                ->withBody($request->getContent(), 'application/json')
                ->post($upstreamUrl);
        } catch (ConnectionException $e) {
            // Log path + status only — no request body (may contain images)
            Log::error('Genvoris proxy upstream connection failed', ['path' => $path]);

            return response()->json(['error' => 'Upstream unavailable.'], 502);
        }

        $status = $upstreamResponse->status();

        if ($status >= 500) {
            Log::warning('Genvoris proxy upstream error', ['path' => $path, 'status' => $status]);

            return response()->json(['error' => 'Upstream error.'], 502);
        }

        return response()->json($upstreamResponse->json(), $status);
    }
}
