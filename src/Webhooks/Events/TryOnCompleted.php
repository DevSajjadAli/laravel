<?php

namespace Genvoris\Laravel\Webhooks\Events;

class TryOnCompleted
{
    public function __construct(public readonly array $payload) {}
}
