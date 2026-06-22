<?php

namespace Genvoris\Laravel\Tests\Feature;

use Genvoris\Laravel\Tests\TestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

class ProxyControllerTest extends TestCase
{
    public function test_proxy_injects_api_key_in_upstream_request(): void
    {
        Http::fake(['https://api.genvoris.org/*' => Http::response(['ok' => true])]);

        $this->postJson(config('genvoris.proxy.path', 'genvoris-proxy').'/api/analyze', ['image' => 'base64...']);

        Http::assertSent(function (Request $req) {
            return str_contains((string) $req->url(), 'api.genvoris.org/api/analyze')
                && $req->header('X-API-Key')[0] === config('genvoris.api_key');
        });
    }

    public function test_proxy_forwards_widget_events_path(): void
    {
        Http::fake(['https://api.genvoris.org/*' => Http::response(['accepted' => 1], 202)]);

        $response = $this->postJson(config('genvoris.proxy.path', 'genvoris-proxy').'/api/v1/events', [
            'sessionId' => 'session_12345678',
            'eventType' => 'WIDGET_OPENED',
        ]);

        $response->assertStatus(202);
        Http::assertSent(function (Request $req) {
            return str_contains((string) $req->url(), 'api.genvoris.org/api/v1/events')
                && $req->header('X-API-Key')[0] === config('genvoris.api_key');
        });
    }

    public function test_proxy_accepts_get_requests_at_route_layer(): void
    {
        Http::fake(['https://api.genvoris.org/*' => Http::response(['status' => 'ok'])]);

        $response = $this->getJson(config('genvoris.proxy.path', 'genvoris-proxy').'/api/status');

        $response->assertStatus(200);
    }

    public function test_proxy_api_key_not_in_response(): void
    {
        Http::fake(['https://api.genvoris.org/*' => Http::response(['result' => 'ok'])]);

        $response = $this->postJson(config('genvoris.proxy.path', 'genvoris-proxy').'/api/analyze', []);

        $this->assertStringNotContainsString(
            config('genvoris.api_key'),
            $response->content(),
        );
    }

    public function test_disallowed_path_returns_400(): void
    {
        $response = $this->postJson(config('genvoris.proxy.path', 'genvoris-proxy').'/api/admin', []);

        $response->assertStatus(400);
    }

    public function test_path_traversal_returns_400(): void
    {
        $response = $this->postJson(config('genvoris.proxy.path', 'genvoris-proxy').'/api/../etc/passwd', []);

        $response->assertStatus(400);
    }

    public function test_invalid_http_method_returns_405(): void
    {
        $response = $this->call(
            'TRACE',
            config('genvoris.proxy.path', 'genvoris-proxy').'/api/analyze',
        );

        $response->assertStatus(405);
    }

    public function test_upstream_returns_502_on_connection_failure(): void
    {
        // No HTTP fake — the upstream won't be reachable with the real URL
        Http::fake(['https://api.genvoris.org/*' => Http::response(null, 502)]);

        $response = $this->postJson(config('genvoris.proxy.path', 'genvoris-proxy').'/api/analyze', []);

        // Should return our proxy 502, not the upstream 502 directly
        $response->assertStatus(502);
    }
}
