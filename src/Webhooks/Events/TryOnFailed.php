<?php

namespace Genvoris\Laravel\Webhooks\Events;

class TryOnFailed
{
    public function __construct(public readonly array $payload) {}
}
