<?php

namespace Genvoris\Laravel\Resources;

use Genvoris\Laravel\DataObjects\Customer;
use Genvoris\Laravel\DataObjects\CustomerUsage;
use Genvoris\Laravel\Http\Client;

class CustomerResource
{
    public function __construct(private readonly Client $client) {}

    /**
     * Create or update a customer by externalId.
     * Automatically prepends the configured external_id_prefix to $externalId.
     *
     * @param  array<string, mixed>  $attributes  email, planId, metadata, etc.
     */
    public function upsert(string $externalId, array $attributes = []): Customer
    {
        $prefix = config('genvoris.external_id_prefix', 'laravel_');
        // Do not double-prefix if caller already included it
        $prefixedId = str_starts_with($externalId, $prefix) ? $externalId : $prefix.$externalId;

        $data = $this->client->post('customers', array_merge(
            ['externalId' => $prefixedId],
            $attributes,
        ));

        return Customer::fromArray($data);
    }

    public function find(string $customerId): Customer
    {
        return Customer::fromArray($this->client->get("customers/{$customerId}"));
    }

    public function findByExternalId(string $externalId): ?Customer
    {
        $prefix = config('genvoris.external_id_prefix', 'laravel_');
        $prefixedId = str_starts_with($externalId, $prefix) ? $externalId : $prefix.$externalId;

        $rows = $this->client->get('customers', ['externalId' => $prefixedId]);

        // API returns array list or a single object
        if (isset($rows['id'])) {
            return Customer::fromArray($rows);
        }

        $list = $rows['items'] ?? $rows;

        if (! is_array($list) || empty($list)) {
            return null;
        }

        return Customer::fromArray($list[0]);
    }

    /**
     * @return Customer[]
     */
    public function list(array $query = []): array
    {
        $data = $this->client->get('customers', $query);
        $items = $data['items'] ?? $data;

        return array_map(
            static fn (array $row) => Customer::fromArray($row),
            is_array($items) ? $items : [],
        );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function update(string $customerId, array $attributes): Customer
    {
        return Customer::fromArray($this->client->patch("customers/{$customerId}", $attributes));
    }

    public function cancel(string $customerId): Customer
    {
        $result = $this->client->delete("customers/{$customerId}");

        if (empty($result)) {
            // 204 No Content — build a minimal cancelled Customer from the known ID
            return Customer::fromArray(['id' => $customerId, 'status' => 'cancelled']);
        }

        return Customer::fromArray($result);
    }

    public function usage(string $customerId): CustomerUsage
    {
        return CustomerUsage::fromArray($this->client->get("customers/{$customerId}/usage"));
    }
}
