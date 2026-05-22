<?php

namespace Genvoris\Laravel\Webhooks\Events;

class CustomerQuotaWarning
{
    public function __construct(public readonly array $payload) {}
}
