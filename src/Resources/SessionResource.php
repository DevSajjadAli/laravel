<?php

namespace Genvoris\Laravel\Resources;

use Genvoris\Laravel\DataObjects\Customer;
use Genvoris\Laravel\DataObjects\Session;
use Genvoris\Laravel\Http\Client;
use Illuminate\Support\Facades\Cache;

class SessionResource
{
    public function __construct(
        private readonly Client $client,
        private readonly CustomerResource $customers,
    ) {}

    /**
     * Mint a session token for an existing Genvoris customer.
     *
     * @param  int  $expiresIn  TTL in seconds. Clamped to [60, 3600] per portal contract.
     */
    public function mint(string $customerId, int $expiresIn = 900): Session
    {
        $expiresIn = max(60, min(3600, $expiresIn));

        if (config('genvoris.cache.sessions', true)) {
            $cacheKey = 'genvoris_session_'.$customerId.'_'.$expiresIn;
            $cacheTtl = config('genvoris.cache.ttl', 840); // seconds
            $store = config('genvoris.cache.store');

            return Cache::store($store)->remember(
                $cacheKey,
                $cacheTtl,
                fn () => $this->callMint($customerId, $expiresIn),
            );
        }

        return $this->callMint($customerId, $expiresIn);
    }

    /**
     * Upsert a Genvoris customer from a local user ID, then mint a session.
     *
     * @param  array<string, mixed>  $customerAttributes  Optional attributes for the upsert.
     */
    public function mintForUser(string $externalId, int $expiresIn = 900, array $customerAttributes = []): Session
    {
        $customer = $this->customers->upsert($externalId, $customerAttributes);

        return $this->mint($customer->id, $expiresIn);
    }

    private function callMint(string $customerId, int $expiresIn): Session
    {
        $data = $this->client->post("customers/{$customerId}/sessions", [
            'expiresIn' => $expiresIn,
        ]);

        return Session::fromArray($data);
    }
}
