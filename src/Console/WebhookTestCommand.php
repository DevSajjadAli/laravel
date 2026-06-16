<?php

namespace Genvoris\Laravel\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class WebhookTestCommand extends Command
{
    protected $signature = 'genvoris:webhook-test
                            {--url= : Override the webhook URL (default: route genvoris.webhook)}
                            {--event=end_customer.created : Event type to simulate}';

    protected $description = 'Send a synthetic signed webhook to your endpoint and report pass/fail.';

    public function handle(): int
    {
        $secret = config('genvoris.webhook.secret', '');

        if ($secret === '') {
            $this->error('GENVORIS_WEBHOOK_SECRET is not set. Cannot sign a test webhook.');

            return self::FAILURE;
        }

        // Build target URL
        $webhookUrl = $this->option('url');
        if (! $webhookUrl) {
            try {
                $webhookUrl = route('genvoris.webhook');
            } catch (\Throwable) {
                $this->error('Could not resolve genvoris.webhook route. Pass --url= explicitly.');

                return self::FAILURE;
            }
        }

        $eventType = $this->option('event');
        $ts = (string) time();
        $payload = json_encode([
            'id' => 'evt_test_'.bin2hex(random_bytes(8)),
            'type' => $eventType,
            'data' => [
                'id' => 'cus_test_'.bin2hex(random_bytes(8)),
                'externalId' => 'laravel_1',
                'status' => 'active',
            ],
        ]);

        // Sign exactly as the portal does
        $hmac = hash_hmac('sha256', $ts.'.'.$payload, $secret);
        $signature = "t={$ts},v1={$hmac}";

        $this->line("Sending <comment>{$eventType}</comment> to: {$webhookUrl}");

        try {
            $response = Http::withHeaders([
                'X-Genvoris-Signature' => $signature,
            ])->withBody($payload, 'application/json')->send('POST', $webhookUrl);
        } catch (\Throwable $e) {
            $this->error('HTTP error: '.$e->getMessage());

            return self::FAILURE;
        }

        $status = $response->status();

        if ($status >= 200 && $status < 300) {
            $this->info("Webhook test passed. Response: HTTP {$status}");

            return self::SUCCESS;
        }

        $this->error("Webhook test failed. Response: HTTP {$status}");
        $this->line('Body: '.$response->body());

        return self::FAILURE;
    }
}
