<?php

namespace Genvoris\Laravel;

use Genvoris\Laravel\DataObjects\Customer;
use Genvoris\Laravel\DataObjects\CustomerUsage;
use Genvoris\Laravel\DataObjects\Plan;
use Genvoris\Laravel\DataObjects\Session;
use Genvoris\Laravel\Http\Client;
use Genvoris\Laravel\Resources\CustomerResource;
use Genvoris\Laravel\Resources\PlanResource;
use Genvoris\Laravel\Resources\SessionResource;
use Genvoris\Laravel\Webhooks\WebhookVerifier;

class Genvoris
{
    private ?CustomerResource $customersResource = null;

    private ?PlanResource $plansResource = null;

    private ?SessionResource $sessionsResource = null;

    public function __construct(private readonly Client $client) {}

    // ------------------------------------------------------------------
    // Resource namespaces (lazy-initialized)
    // ------------------------------------------------------------------

    public function customers(): CustomerResource
    {
        return $this->customersResource ??= new CustomerResource($this->client);
    }

    public function plans(): PlanResource
    {
        return $this->plansResource ??= new PlanResource($this->client);
    }

    public function sessions(): SessionResource
    {
        return $this->sessionsResource ??= new SessionResource($this->client, $this->customers());
    }

    public function webhooks(): WebhookVerifier
    {
        return new WebhookVerifier;
    }

    // ------------------------------------------------------------------
    // Convenience methods
    // ------------------------------------------------------------------

    /**
     * Create or update a Genvoris customer by local external ID.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function upsertCustomer(string $externalId, array $attributes = []): Customer
    {
        return $this->customers()->upsert($externalId, $attributes);
    }

    /**
     * Mint a session token for an existing Genvoris customer.
     */
    public function mintSession(string $customerId, int $expiresIn = 900): Session
    {
        return $this->sessions()->mint($customerId, $expiresIn);
    }

    /**
     * @return Plan[]
     */
    public function listPlans(array $query = []): array
    {
        return $this->plans()->list($query);
    }

    /**
     * @return Customer[]
     */
    public function listCustomers(array $query = []): array
    {
        return $this->customers()->list($query);
    }

    public function customerUsage(string $customerId): CustomerUsage
    {
        return $this->customers()->usage($customerId);
    }
}
