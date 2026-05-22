<?php

namespace Genvoris\Laravel\Webhooks\Events;

class CustomerCancelled
{
    public function __construct(public readonly array $payload) {}
}
