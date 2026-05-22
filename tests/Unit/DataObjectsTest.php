<?php

namespace Genvoris\Laravel\Tests\Unit;

use Genvoris\Laravel\DataObjects\Customer;
use Genvoris\Laravel\DataObjects\CustomerUsage;
use Genvoris\Laravel\DataObjects\Plan;
use Genvoris\Laravel\DataObjects\Session;
use Genvoris\Laravel\Tests\TestCase;

class DataObjectsTest extends TestCase
{
    public function test_customer_from_array(): void
    {
        $data = [
            'id' => 'cus_1',
            'externalId' => 'laravel_42',
            'email' => 'user@example.com',
            'planId' => 'plan_basic',
            'status' => 'active',
            'createdAt' => '2024-01-01T00:00:00Z',
            'updatedAt' => '2024-01-02T00:00:00Z',
        ];

        $c = Customer::fromArray($data);

        $this->assertSame('cus_1', $c->id);
        $this->assertSame('laravel_42', $c->externalId);
        $this->assertSame('user@example.com', $c->email);
        $this->assertSame('active', $c->status);
    }

    public function test_customer_from_array_with_missing_optional_fields(): void
    {
        $c = Customer::fromArray(['id' => 'cus_2', 'externalId' => 'laravel_2']);

        $this->assertNull($c->email);
        $this->assertNull($c->planId);
    }

    public function test_plan_from_array(): void
    {
        $p = Plan::fromArray(['id' => 'plan_1', 'name' => 'Basic', 'active' => true]);

        $this->assertTrue($p->active);
        $this->assertSame('plan_1', $p->id);
    }

    public function test_session_from_array(): void
    {
        $s = Session::fromArray(['token' => 'tok_abc', 'tokenType' => 'Bearer', 'expiresIn' => 900]);

        $this->assertSame('tok_abc', $s->token);
        $this->assertSame(900, $s->expiresIn);
    }

    public function test_customer_usage_can_try_on(): void
    {
        $u = CustomerUsage::fromArray([
            'customerId' => 'cus_1',
            'current' => [['tryOnsUsed' => 2, 'tryOnsLimit' => 10]],
            'history' => [],
        ]);

        $this->assertTrue($u->canTryOn());
    }

    public function test_customer_usage_cannot_try_on_when_exhausted(): void
    {
        $u = CustomerUsage::fromArray([
            'customerId' => 'cus_1',
            'status' => 'quota_exhausted',
            'current' => [['tryOnsUsed' => 10, 'tryOnsLimit' => 10]],
            'history' => [],
        ]);

        $this->assertFalse($u->canTryOn());
    }
}
