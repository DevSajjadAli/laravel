<?php

namespace Genvoris\Laravel\Webhooks\Events;

class PlanCreated
{
    public function __construct(public readonly array $payload) {}
}
