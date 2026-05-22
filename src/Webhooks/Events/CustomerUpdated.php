<?php

namespace Genvoris\Laravel\Webhooks\Events;

class CustomerUpdated
{
    public function __construct(public readonly array $payload) {}
}
