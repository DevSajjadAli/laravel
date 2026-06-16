<?php

namespace Genvoris\Laravel\Http\Controllers;

use Genvoris\Laravel\Webhooks\Events\CustomerCancelled;
use Genvoris\Laravel\Webhooks\Events\CustomerCreated;
use Genvoris\Laravel\Webhooks\Events\CustomerPeriodRolled;
use Genvoris\Laravel\Webhooks\Events\CustomerQuotaExhausted;
use Genvoris\Laravel\Webhooks\Events\CustomerQuotaWarning;
use Genvoris\Laravel\Webhooks\Events\CustomerUpdated;
use Genvoris\Laravel\Webhooks\Events\GenvorisWebhookReceived;
use Genvoris\Laravel\Webhooks\Events\PlanCreated;
use Genvoris\Laravel\Webhooks\Events\PlanDisabled;
use Genvoris\Laravel\Webhooks\Events\PlanUpdated;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;

class WebhookController extends Controller
{
    /** Maps portal event types → typed event classes. */
    private const EVENT_MAP = [
        'end_customer.created' => CustomerCreated::class,
        'end_customer.updated' => CustomerUpdated::class,
        'end_customer.cancelled' => CustomerCancelled::class,
        'end_customer.quota_warning' => CustomerQuotaWarning::class,
        'end_customer.quota_exhausted' => CustomerQuotaExhausted::class,
        'end_customer.period_rolled' => CustomerPeriodRolled::class,
        'plan.created' => PlanCreated::class,
        'plan.updated' => PlanUpdated::class,
        'plan.disabled' => PlanDisabled::class,
    ];

    /**
     * Handle a verified Genvoris webhook.
     * Signature must already have been verified by VerifyGenvorisWebhook middleware.
     */
    public function handle(Request $request): JsonResponse
    {
        $payload = $request->json()->all();
        $type = $payload['type'] ?? 'unknown';
        $id = $payload['id'] ?? '';

        // The portal's canonical, per-delivery identifier is carried in the
        // `X-Genvoris-Delivery` header (the same value is replayed on retries).
        // Prefer it for idempotency; fall back to a body `id` if present.
        $deliveryId = $request->header('X-Genvoris-Delivery') ?: $id;

        // Idempotency: the portal may retry delivery of the same event. Each
        // delivery carries a stable id. Atomically claim it in the cache;
        // if it was already claimed we acknowledge (200) without re-dispatching
        // so listeners never run twice. TTL comfortably exceeds the portal's
        // retry window. Deliveries without an id fall through (cannot dedup).
        if ($deliveryId !== '') {
            $claimed = Cache::add('genvoris:webhook:'.$deliveryId, true, now()->addDay());
            if (!$claimed) {
                return response()->json(['received' => true, 'duplicate' => true]);
            }
        }

        // Fire the generic event first (allows catch-all listeners)
        Event::dispatch(new GenvorisWebhookReceived($type, $id, $payload));

        // Fire the typed event if we recognise the type
        if (isset(self::EVENT_MAP[$type])) {
            $eventClass = self::EVENT_MAP[$type];
            Event::dispatch(new $eventClass($payload));
        }

        return response()->json(['received' => true]);
    }
}
