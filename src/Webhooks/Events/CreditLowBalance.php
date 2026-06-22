<?php

namespace Genvoris\Laravel\Webhooks\Events;

class CreditLowBalance
{
    public function __construct(public readonly array $payload) {}
}
