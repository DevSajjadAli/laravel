<?php

namespace Genvoris\Laravel\Tests\Feature;

use Genvoris\Laravel\Tests\TestCase;
use Genvoris\Laravel\Webhooks\Events\CustomerCreated;
use Genvoris\Laravel\Webhooks\Events\GenvorisWebhookReceived;
use Genvoris\Laravel\Webhooks\Events\TryOnCompleted;
use Illuminate\Support\Facades\Event;

class WebhookControllerTest extends TestCase
{
    private function signedHeader(string $body, string $secret): string
    {
        $ts = (string) time();
        $hmac = hash_hmac('sha256', $ts.'.'.$body, $secret);

        return "t={$ts},v1={$hmac}";
    }

    public function test_valid_webhook_returns_200(): void
    {
        $secret = config('genvoris.webhook.secret');
        $payload = json_encode(['type' => 'end_customer.created', 'id' => 'cus_1', 'data' => []]);
        $header = $this->signedHeader($payload, $secret);

        $response = $this->withHeaders(['X-Genvoris-Signature' => $header])
            ->postJson(config('genvoris.webhook.path', 'webhooks/genvoris'), json_decode($payload, true));

        $response->assertStatus(200)->assertJson(['received' => true]);
    }

    public function test_invalid_signature_returns_401(): void
    {
        $response = $this->withHeaders(['X-Genvoris-Signature' => 't=123,v1=badhex'])
            ->postJson(config('genvoris.webhook.path', 'webhooks/genvoris'), ['type' => 'end_customer.created']);

        $response->assertStatus(401);
    }

    public function test_valid_webhook_dispatches_generic_and_typed_events(): void
    {
        Event::fake();

        $secret = config('genvoris.webhook.secret');
        $payload = json_encode(['type' => 'end_customer.created', 'id' => 'cus_1', 'data' => []]);
        $header = $this->signedHeader($payload, $secret);

        $this->withHeaders(['X-Genvoris-Signature' => $header])
            ->postJson(config('genvoris.webhook.path', 'webhooks/genvoris'), json_decode($payload, true));

        Event::assertDispatched(GenvorisWebhookReceived::class);
        Event::assertDispatched(CustomerCreated::class);
    }

    public function test_new_tryon_event_dispatches_typed_event(): void
    {
        Event::fake();

        $secret = config('genvoris.webhook.secret');
        $payload = json_encode(['type' => 'tryon.completed', 'id' => 'evt_1', 'data' => []]);
        $header = $this->signedHeader($payload, $secret);

        $this->withHeaders(['X-Genvoris-Signature' => $header])
            ->postJson(config('genvoris.webhook.path', 'webhooks/genvoris'), json_decode($payload, true));

        Event::assertDispatched(GenvorisWebhookReceived::class);
        Event::assertDispatched(TryOnCompleted::class);
    }

    public function test_unknown_event_type_only_dispatches_generic_event(): void
    {
        Event::fake();

        $secret = config('genvoris.webhook.secret');
        $payload = json_encode(['type' => 'future.unknown_type', 'id' => 'evt_1', 'data' => []]);
        $header = $this->signedHeader($payload, $secret);

        $this->withHeaders(['X-Genvoris-Signature' => $header])
            ->postJson(config('genvoris.webhook.path', 'webhooks/genvoris'), json_decode($payload, true));

        Event::assertDispatched(GenvorisWebhookReceived::class);
        // No typed event for unknown type — that's fine
    }
}
