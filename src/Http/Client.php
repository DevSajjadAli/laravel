<?php

namespace Genvoris\Laravel\Http;

use Genvoris\Laravel\Exceptions\ApiException;
use Genvoris\Laravel\Exceptions\AuthException;
use Genvoris\Laravel\Exceptions\GenvorisException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;

/**
 * Thin HTTP wrapper over Laravel's Http facade.
 *
 * - Injects Authorization: Bearer header on every request.
 * - Unwraps { "data": ... } envelope from all API responses.
 * - Retries on HTTP 429 or 5xx with exponential backoff + ±30% jitter,
 *   matching the Node SDK schedule: 200ms * 4^attempt (200 / 800 / 3200 ms).
 * - Never includes the raw API key in any exception message or log entry.
 */
final class Client
{
    public function __construct(
        private readonly string $apiKey,
        private readonly string $baseUrl,
        private readonly int $timeout,
        private readonly int $retryTimes,
        /** @var int[] $retrySleepMs delay per attempt (ms), index = attempt number */
        private readonly array $retrySleepMs,
    ) {}

    // ------------------------------------------------------------------
    // Public interface
    // ------------------------------------------------------------------

    public function get(string $path, array $query = []): array
    {
        return $this->request('GET', $path, [], $query);
    }

    public function post(string $path, array $body = []): array
    {
        return $this->request('POST', $path, $body);
    }

    public function patch(string $path, array $body = []): array
    {
        return $this->request('PATCH', $path, $body);
    }

    public function delete(string $path): array
    {
        return $this->request('DELETE', $path);
    }

    // ------------------------------------------------------------------
    // Internal execution
    // ------------------------------------------------------------------

    private function request(string $method, string $path, array $body = [], array $query = []): array
    {
        $url = $this->baseUrl.'/'.ltrim($path, '/');
        $attempt = 0;

        while (true) {
            try {
                $response = $this->send($method, $url, $body, $query);
            } catch (ConnectionException $e) {
                throw new GenvorisException('Genvoris API connection failed: '.$e->getMessage(), 0, $e);
            }

            $status = $response->status();

            if ($this->shouldRetry($status) && $attempt < $this->retryTimes) {
                usleep($this->jitteredSleepUs($attempt));
                $attempt++;

                continue;
            }

            return $this->handleResponse($response, $status);
        }
    }

    private function send(string $method, string $url, array $body, array $query): Response
    {
        $pending = Http::withHeaders([
            'Authorization' => 'Bearer '.$this->apiKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->timeout($this->timeout);

        return match ($method) {
            'GET' => $pending->get($url, $query),
            'POST' => $pending->post($url, $body),
            'PATCH' => $pending->patch($url, $body),
            'DELETE' => $pending->delete($url),
            default => throw new GenvorisException("Unsupported HTTP method: {$method}"),
        };
    }

    /**
     * @return array<string, mixed>
     *
     * @throws AuthException
     * @throws ApiException
     */
    private function handleResponse(Response $response, int $status): array
    {
        if ($status === 401 || $status === 403) {
            $message = $response->json('message') ?? 'Authentication failed.';
            throw new AuthException($message, $status);
        }

        if ($status >= 400) {
            $message = $response->json('message') ?? "HTTP {$status} from Genvoris API.";
            $errorCode = $response->json('code');
            $requestId = $response->header('X-Request-Id');
            throw new ApiException($message, $status, $errorCode, $requestId ?: null);
        }

        if ($status === 204) {
            return [];
        }

        $json = $response->json();

        // Unwrap { "data": ... } envelope
        if (is_array($json) && array_key_exists('data', $json)) {
            return is_array($json['data']) ? $json['data'] : [];
        }

        return is_array($json) ? $json : [];
    }

    private function shouldRetry(int $status): bool
    {
        return $status === 429 || $status >= 500;
    }

    /**
     * Returns sleep duration in microseconds: base ± 30% jitter.
     * Base values from config retry.sleep (ms): [200, 800, 3200]
     * matching Node SDK's 200ms * 4^attempt schedule.
     */
    private function jitteredSleepUs(int $attempt): int
    {
        $baseMs = $this->retrySleepMs[$attempt] ?? 3200;
        // ±30% jitter: multiply base by (1 + random in [-0.3, +0.3])
        $jitter = mt_rand(-300, 300) / 1000; // float in [-0.3, +0.3]
        $sleepMs = (int) round($baseMs * (1 + $jitter));

        return max(0, $sleepMs) * 1000; // convert ms → µs
    }
}
