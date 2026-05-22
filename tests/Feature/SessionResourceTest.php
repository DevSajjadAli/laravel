<?php

namespace Genvoris\Laravel\Tests\Feature;

use Genvoris\Laravel\Genvoris;
use Genvoris\Laravel\Tests\TestCase;
use Illuminate\Support\Facades\Http;

class SessionResourceTest extends TestCase
{
    public function test_mint_posts_to_customers_sessions_endpoint(): void
    {
        Http::fake([
            '*/customers/cus_1/sessions' => Http::response(['data' => [
                'token' => 'tok_abc',
                'tokenType' => 'Bearer',
                'expiresIn' => 900,
            ]]),
        ]);

        $session = app(Genvoris::class)->mintSession('cus_1', 900);

        $this->assertSame('tok_abc', $session->token);

        Http::assertSent(fn ($req) => str_contains((string) $req->url(), '/customers/cus_1/sessions'));
    }

    public function test_expires_in_is_clamped_to_minimum(): void
    {
        Http::fake([
            '*/customers/*/sessions' => Http::response(['data' => ['token' => 'tok_x', 'expiresIn' => 60]]),
        ]);

        app(Genvoris::class)->mintSession('cus_1', 0);

        Http::assertSent(fn ($req) => ($req->data()['expiresIn'] ?? null) === 60);
    }

    public function test_expires_in_is_clamped_to_maximum(): void
    {
        Http::fake([
            '*/customers/*/sessions' => Http::response(['data' => ['token' => 'tok_x', 'expiresIn' => 3600]]),
        ]);

        app(Genvoris::class)->mintSession('cus_1', 99999);

        Http::assertSent(fn ($req) => ($req->data()['expiresIn'] ?? null) === 3600);
    }
}
