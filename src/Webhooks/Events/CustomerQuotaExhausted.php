<?php

namespace Genvoris\Laravel\Webhooks\Events;

class CustomerQuotaExhausted
{
    public function __construct(public readonly array $payload) {}
}
