<?php

namespace Genvoris\Laravel\Resources;

use Genvoris\Laravel\DataObjects\Plan;
use Genvoris\Laravel\Http\Client;

class PlanResource
{
    public function __construct(private readonly Client $client) {}

    /**
     * @param  array<string, mixed>  $attributes  name, monthlyTryOns, externalPriceId, etc.
     */
    public function create(array $attributes): Plan
    {
        return Plan::fromArray($this->client->post('plans', $attributes));
    }

    /**
     * @return Plan[]
     */
    public function list(array $query = []): array
    {
        $data = $this->client->get('plans', $query);
        $items = $data['items'] ?? $data;

        return array_map(
            static fn (array $row) => Plan::fromArray($row),
            is_array($items) ? $items : [],
        );
    }

    public function find(string $planId): Plan
    {
        return Plan::fromArray($this->client->get("plans/{$planId}"));
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(string $planId, array $attributes): Plan
    {
        return Plan::fromArray($this->client->patch("plans/{$planId}", $attributes));
    }

    public function disable(string $planId): void
    {
        $this->client->delete("plans/{$planId}");
    }
}
