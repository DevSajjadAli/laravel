<?php

namespace Genvoris\Laravel\Webhooks\Events;

class CustomerPeriodRolled
{
    public function __construct(public readonly array $payload) {}
}
