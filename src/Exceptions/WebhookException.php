<?php

namespace Genvoris\Laravel\Exceptions;

class WebhookException extends GenvorisException
{
    public function __construct(string $message = 'Webhook signature verification failed.')
    {
        parent::__construct($message);
    }
}
