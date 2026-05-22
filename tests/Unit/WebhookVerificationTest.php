<?php

namespace Genvoris\Laravel\Tests\Unit;

use Genvoris\Laravel\Tests\TestCase;
use Genvoris\Laravel\Webhooks\WebhookVerifier;

class WebhookVerificationTest extends TestCase
{
    private WebhookVerifier $verifier;

    private string $secret = 'test_webhook_secret';

    protected function setUp(): void
    {
        parent::setUp();
        $this->verifier = new WebhookVerifier;
    }

    public function test_valid_signature_returns_true(): void
    {
        $body = '{"type":"end_customer.created","id":"cus_1"}';
        $ts = (string) time();
        $hmac = hash_hmac('sha256', $ts.'.'.$body, $this->secret);
        $header = "t={$ts},v1={$hmac}";

        $this->assertTrue($this->verifier->verify($body, $header, $this->secret));
    }

    public function test_wrong_secret_returns_false(): void
    {
        $body = '{"type":"end_customer.created"}';
        $ts = (string) time();
        $hmac = hash_hmac('sha256', $ts.'.'.$body, 'wrong_secret');
        $header = "t={$ts},v1={$hmac}";

        $this->assertFalse($this->verifier->verify($body, $header, $this->secret));
    }

    public function test_tampered_body_returns_false(): void
    {
        $original = '{"type":"end_customer.created"}';
        $tampered = '{"type":"plan.disabled"}';
        $ts = (string) time();
        $hmac = hash_hmac('sha256', $ts.'.'.$original, $this->secret);
        $header = "t={$ts},v1={$hmac}";

        $this->assertFalse($this->verifier->verify($tampered, $header, $this->secret));
    }

    public function test_expired_timestamp_returns_false(): void
    {
        $body = '{"type":"end_customer.created"}';
        $ts = (string) (time() - 400); // outside 300 s tolerance
        $hmac = hash_hmac('sha256', $ts.'.'.$body, $this->secret);
        $header = "t={$ts},v1={$hmac}";

        $this->assertFalse($this->verifier->verify($body, $header, $this->secret));
    }

    public function test_missing_signature_header_returns_false(): void
    {
        $this->assertFalse($this->verifier->verify('body', '', $this->secret));
    }

    public function test_missing_v1_component_returns_false(): void
    {
        $ts = (string) time();
        $header = "t={$ts}";

        $this->assertFalse($this->verifier->verify('body', $header, $this->secret));
    }

    public function test_non_hex_v1_returns_false(): void
    {
        $ts = (string) time();
        $header = "t={$ts},v1=not_a_hex_string";

        $this->assertFalse($this->verifier->verify('body', $header, $this->secret));
    }
}
