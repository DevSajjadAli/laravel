<?php

namespace Genvoris\Laravel\Facades;

use Genvoris\Laravel\DataObjects\Customer;
use Genvoris\Laravel\DataObjects\CustomerUsage;
use Genvoris\Laravel\DataObjects\Plan;
use Genvoris\Laravel\DataObjects\Session;
use Genvoris\Laravel\Resources\CustomerResource;
use Genvoris\Laravel\Resources\PlanResource;
use Genvoris\Laravel\Resources\SessionResource;
use Genvoris\Laravel\Webhooks\WebhookVerifier;
use Illuminate\Support\Facades\Facade;

/**
 * @method static CustomerResource customers()
 * @method static PlanResource plans()
 * @method static SessionResource sessions()
 * @method static WebhookVerifier webhooks()
 * @method static Customer upsertCustomer(string $externalId, array $attributes = [])
 * @method static Session mintSession(string $customerId, int $expiresIn = 900)
 * @method static Plan[] listPlans(array $query = [])
 * @method static Customer[] listCustomers(array $query = [])
 * @method static CustomerUsage customerUsage(string $customerId)
 *
 * @see \Genvoris\Laravel\Genvoris
 */
class Genvoris extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'genvoris';
    }
}
