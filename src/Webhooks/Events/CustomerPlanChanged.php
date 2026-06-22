<?php

namespace Genvoris\Laravel\Webhooks\Events;

class CustomerPlanChanged
{
    public function __construct(public readonly array $payload) {}
}
