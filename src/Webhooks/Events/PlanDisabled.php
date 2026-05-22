<?php

namespace Genvoris\Laravel\Webhooks\Events;

class PlanDisabled
{
    public function __construct(public readonly array $payload) {}
}
