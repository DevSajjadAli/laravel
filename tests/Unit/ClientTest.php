<?php

namespace Genvoris\Laravel\Tests\Unit;

use Genvoris\Laravel\Exceptions\ApiException;
use Genvoris\Laravel\Exceptions\AuthException;
use Genvoris\Laravel\Http\Client;
use Genvoris\Laravel\Tests\TestCase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

class ClientTest extends TestCase
{
    private Client $client;

    protected function setUp(): void
    {
        parent::setUp();
        $this->client = new Client(
            apiKey: 'gvk_test_placeholder',
            baseUrl: 'https://genvoris.org/api/v1',
            timeout: 30,
            retryTimes: 0,          // no retries in unit tests
            retrySleepMs: [200, 800, 3200],
        );
    }

    public function test_get_sends_authorization_header(): void
    {
        Http::fake(['*' => Http::response(['data' => ['id' => 'cus_1']])]);

        $this->client->get('customers/cus_1');

        Http::assertSent(fn (Request $req) => $req->header('Authorization')[0] === 'Bearer gvk_test_placeholder'
        );
    }

    public function test_response_data_is_unwrapped(): void
    {
        Http::fake(['*' => Http::response(['data' => ['id' => 'cus_1', 'status' => 'active']])]);

        $result = $this->client->get('customers/cus_1');

        $this->assertSame('cus_1', $result['id']);
        $this->assertArrayNotHasKey('data', $result);
    }

    public function test_401_throws_auth_exception(): void
    {
        Http::fake(['*' => Http::response(['error' => 'Unauthorized'], 401)]);

        $this->expectException(AuthException::class);
        $this->client->get('customers');
    }

    public function test_404_throws_api_exception(): void
    {
        Http::fake(['*' => Http::response(['error' => 'Not found'], 404)]);

        $this->expectException(ApiException::class);
        $this->client->get('customers/nonexistent');
    }

    public function test_204_returns_empty_array(): void
    {
        Http::fake(['*' => Http::response(null, 204)]);

        $result = $this->client->delete('customers/cus_1');

        $this->assertSame([], $result);
    }

    public function test_api_key_not_in_exception_message(): void
    {
        Http::fake(['*' => Http::response(['error' => 'Internal Server Error'], 500)]);

        try {
            $this->client->get('customers');
        } catch (ApiException $e) {
            $this->assertStringNotContainsString('gvk_test_placeholder', $e->getMessage());

            return;
        }

        $this->fail('Expected ApiException was not thrown.');
    }
}
