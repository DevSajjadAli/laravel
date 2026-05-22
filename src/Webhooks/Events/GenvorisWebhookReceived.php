<?php

namespace Genvoris\Laravel\Webhooks\Events;

/**
 * Fired for every verified Genvoris webhook event before the typed event.
 * Listeners can use $event->type to branch on event type without importing
 * the individual typed event classes.
 */
class GenvorisWebhookReceived
{
    public function __construct(
        public readonly string $type,
        public readonly string $id,
        public readonly array $payload,
    ) {}
}
