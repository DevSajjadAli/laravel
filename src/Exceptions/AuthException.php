<?php

namespace Genvoris\Laravel\Exceptions;

class AuthException extends GenvorisException
{
    public function __construct(
        string $message = 'Genvoris authentication failed. Check your API key.',
        public readonly int $statusCode = 401,
    ) {
        parent::__construct($message);
    }
}
