<?php

namespace Genvoris\Laravel\Webhooks\Events;

class CustomerCreated
{
    public function __construct(public readonly array $payload) {}
}
