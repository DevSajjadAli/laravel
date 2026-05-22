<?php

namespace Genvoris\Laravel\Tests\Feature;

use Genvoris\Laravel\Genvoris;
use Genvoris\Laravel\Tests\TestCase;
use Illuminate\Support\Facades\Http;

class CustomerResourceTest extends TestCase
{
    public function test_upsert_sends_post_with_prefixed_external_id(): void
    {
        Http::fake([
            '*/customers' => Http::response(['data' => [
                'id' => 'cus_1',
                'externalId' => 'laravel_42',
                'status' => 'active',
            ]]),
        ]);

        $customer = app(Genvoris::class)->upsertCustomer('42', ['email' => 'user@example.com']);

        $this->assertSame('cus_1', $customer->id);
        $this->assertSame('laravel_42', $customer->externalId);

        Http::assertSent(fn ($req) => str_contains((string) $req->url(), '/customers')
            && $req->method() === 'POST'
        );
    }

    public function test_upsert_does_not_double_prefix(): void
    {
        Http::fake([
            '*/customers' => Http::response(['data' => [
                'id' => 'cus_1',
                'externalId' => 'laravel_42',
            ]]),
        ]);

        $customer = app(Genvoris::class)->upsertCustomer('laravel_42', []);

        Http::assertSent(fn ($req) => $req['externalId'] === 'laravel_42');
    }

    public function test_find_customer_returns_customer_object(): void
    {
        Http::fake([
            '*/customers/cus_1' => Http::response(['data' => [
                'id' => 'cus_1',
                'externalId' => 'laravel_1',
                'email' => 'a@b.com',
            ]]),
        ]);

        $c = app(Genvoris::class)->customers()->find('cus_1');

        $this->assertSame('cus_1', $c->id);
    }
}
