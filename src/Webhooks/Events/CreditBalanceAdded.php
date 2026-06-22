<?php

namespace Genvoris\Laravel\Webhooks\Events;

class CreditBalanceAdded
{
    public function __construct(public readonly array $payload) {}
}
