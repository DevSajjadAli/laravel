<?php

namespace Genvoris\Laravel\Concerns;

use Genvoris\Laravel\DataObjects\Customer;
use Genvoris\Laravel\DataObjects\CustomerUsage;
use Genvoris\Laravel\DataObjects\Session;
use Genvoris\Laravel\Facades\Genvoris;
use Illuminate\Support\Facades\Schema;

/**
 * Add Genvoris Virtual Try-On integration to an Eloquent model (typically User).
 *
 * Usage:
 *   class User extends Authenticatable
 *   {
 *       use \Genvoris\Laravel\Concerns\HasGenvorisAccess;
 *   }
 *
 * Requires the model to have a string-castable primary key accessible via $this->getKey().
 * If the optional genvoris_customer_sessions migration has been run, Genvoris customer IDs
 * are cached locally to avoid repeated API calls. If the table does not exist, all lookups
 * hit the API directly — both modes are supported.
 */
trait HasGenvorisAccess
{
    // ------------------------------------------------------------------
    // Identification
    // ------------------------------------------------------------------

    /**
     * The external ID sent to the Genvoris API — prefix + local PK.
     */
    public function genvorisExternalId(): string
    {
        $prefix = config('genvoris.external_id_prefix', 'laravel_');

        return $prefix.$this->getKey();
    }

    /**
     * The Genvoris platform customer ID (e.g. "cus_xxx").
     * Returns null when the customer has not been synced yet.
     */
    public function genvorisCustomerId(): ?string
    {
        if ($this->hasLocalTable()) {
            try {
                $row = \DB::table('genvoris_customer_sessions')
                    ->where('user_type', static::class)
                    ->where('user_id', $this->getKey())
                    ->value('genvoris_customer_id');

                return $row ?: null;
            } catch (\Throwable) {
                // Table may not exist yet — fall through to null
            }
        }

        return null;
    }

    // ------------------------------------------------------------------
    // Sync
    // ------------------------------------------------------------------

    /**
     * Upsert this model's user in the Genvoris platform and cache the
     * resulting customer ID locally (when the migration table exists).
     *
     * @param  array<string, mixed>  $attributes  Optional attributes (email, planId, etc.)
     */
    public function syncToGenvoris(array $attributes = []): Customer
    {
        if (isset($this->email) && ! isset($attributes['email'])) {
            $attributes['email'] = $this->email;
        }

        $customer = Genvoris::upsertCustomer($this->genvorisExternalId(), $attributes);

        if ($this->hasLocalTable()) {
            try {
                \DB::table('genvoris_customer_sessions')->updateOrInsert(
                    ['user_type' => static::class, 'user_id' => $this->getKey()],
                    [
                        'genvoris_customer_id' => $customer->id,
                        'external_id' => $customer->externalId,
                        'plan_id' => $customer->planId,
                        'status' => $customer->status,
                        'last_synced_at' => now()->toDateTimeString(),
                    ],
                );
            } catch (\Throwable) {
                // Non-fatal: local cache write failed
            }
        }

        return $customer;
    }

    // ------------------------------------------------------------------
    // Session minting
    // ------------------------------------------------------------------

    /**
     * Sync to Genvoris (if needed) and mint a session token.
     * The token should be passed server-side to the storefront page and
     * handed to the widget — it must NEVER be logged or stored.
     */
    public function genvorisSession(int $expiresIn = 900, array $customerAttributes = []): Session
    {
        $customerId = $this->genvorisCustomerId();

        if ($customerId !== null) {
            return Genvoris::mintSession($customerId, $expiresIn);
        }

        // No cached ID — upsert first, then mint
        return Genvoris::sessions()->mintForUser(
            $this->genvorisExternalId(),
            $expiresIn,
            $customerAttributes,
        );
    }

    // ------------------------------------------------------------------
    // Usage / entitlement
    // ------------------------------------------------------------------

    public function genvorisUsage(): CustomerUsage
    {
        $customerId = $this->resolveOrSyncCustomerId();

        return Genvoris::customerUsage($customerId);
    }

    /**
     * Whether this user currently has quota to perform a try-on.
     */
    public function canTryOn(): bool
    {
        try {
            return $this->genvorisUsage()->canTryOn();
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Full Customer object from the portal.
     */
    public function genvorisPortalCustomer(): Customer
    {
        $customerId = $this->resolveOrSyncCustomerId();

        return Genvoris::customers()->find($customerId);
    }

    // ------------------------------------------------------------------
    // Internal helpers
    // ------------------------------------------------------------------

    private function resolveOrSyncCustomerId(): string
    {
        $customerId = $this->genvorisCustomerId();

        if ($customerId !== null) {
            return $customerId;
        }

        // No cached ID — sync to get/create the customer
        $customer = $this->syncToGenvoris();

        return $customer->id;
    }

    private function hasLocalTable(): bool
    {
        static $checked = null;
        if ($checked === null) {
            try {
                $checked = Schema::hasTable('genvoris_customer_sessions');
            } catch (\Throwable) {
                $checked = false;
            }
        }

        return $checked;
    }
}
