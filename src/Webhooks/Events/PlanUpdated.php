<?php

namespace Genvoris\Laravel\Webhooks\Events;

class PlanUpdated
{
    public function __construct(public readonly array $payload) {}
}
