<?php

namespace Genvoris\Laravel\Http\Controllers;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

/**
 * Forwards widget-layer requests to api.genvoris.org, injecting the
 * server-side API key so it never reaches the browser.
 *
 * Security properties:
 *   - Hard allowlist of forwarding paths — no arbitrary proxy.
 *   - Optional origin check against APP_URL.
 *   - API key injected server-side and never included in responses.
 *   - Request body is NOT logged (may contain multi-MB base64 images).
 *   - Rate-limited per IP and per path (configurable).
 *   - Forwards the original HTTP method (not just POST).
 */
class ProxyController extends Controller
{
    /**
     * The upstream API host. All allowed paths are relative to this.
     */
    private const UPSTREAM_HOST = 'https://api.genvoris.org';

    /**
     * Default rate limit: requests per minute per (IP, path) bucket.
     */
    private const DEFAULT_RATE_LIMIT = 60;

    /**
     * Rate limit windows in seconds.
     */
    private const RATE_LIMIT_WINDOW = 60;

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

        // Rate limit check (per IP per path)
        $rateLimitPerMinute = config('genvoris.proxy.rate_limit', self::DEFAULT_RATE_LIMIT);
        $ip = $request->ip() ?? '0.0.0.0';
        $bucketKey = 'genvoris_proxy_' . md5($ip . '|' . $path);
        $hitCount = (int) Cache::get($bucketKey, 0);
        if ($hitCount >= $rateLimitPerMinute) {
            return response()->json(['error' => 'Too many requests.'], 429);
        }
        Cache::add($bucketKey, 0, self::RATE_LIMIT_WINDOW);
        Cache::increment($bucketKey);

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

                    // RFC 3986 §3.2.2: hostnames are case-insensitive
                    if (strcasecmp($sourceHost, $appHost) !== 0) {
                        return response()->json(['error' => 'Origin not allowed.'], 403);
                    }
                }
            }
        }

        $upstreamUrl = self::UPSTREAM_HOST.'/'.$path;
        $apiKey = config('genvoris.api_key', '');
        $timeout = config('genvoris.timeout', 30);
        $method = strtoupper($request->method());

        // Only accept standard request methods
        if (! in_array($method, ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'], true)) {
            return response()->json(['error' => 'Method not allowed.'], 405);
        }

        // Forward query string parameters
        $queryString = $request->getQueryString();
        if ($queryString) {
            $upstreamUrl .= '?' . $queryString;
        }

        try {
            // Build the HTTP client with the API key header
            $client = Http::withHeaders([
                'X-API-Key' => $apiKey,
                'Accept' => 'application/json',
            ])
                ->timeout($timeout)
                ->withOptions([
                    'allow_redirects' => false,
                ]);

            // Pass through the original Content-Type when present
            $contentType = $request->header('Content-Type', '');
            if ($contentType !== '') {
                $client = $client->withHeader('Content-Type', $contentType);
            }

            // Determine request body based on method
            $body = in_array($method, ['POST', 'PUT', 'PATCH'], true)
                ? $request->getContent()
                : null;

            // Forward the request preserving the original HTTP method
            $upstreamResponse = $client->withBody(
                $body ?? '',
                $contentType ?: 'application/json'
            )->send($method, $upstreamUrl);

        } catch (ConnectionException $e) {
            Log::error('Genvoris proxy upstream connection failed', [
                'path' => $path,
                'method' => $method,
            ]);

            return response()->json(['error' => 'Upstream unavailable.'], 502);
        }

        $status = $upstreamResponse->status();
        $responseBody = $upstreamResponse->json();

        if ($status >= 500) {
            Log::warning('Genvoris proxy upstream error', [
                'path' => $path,
                'method' => $method,
                'status' => $status,
            ]);

            return response()->json(['error' => 'Upstream error.'], 502);
        }

        return response()->json($responseBody, $status);
    }
}
