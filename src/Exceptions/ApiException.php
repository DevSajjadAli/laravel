<?php

namespace Genvoris\Laravel\Exceptions;

class ApiException extends GenvorisException
{
    public function __construct(
        string $message,
        public readonly int $statusCode = 0,
        public readonly ?string $errorCode = null,
        public readonly ?string $requestId = null,
    ) {
        parent::__construct($message);
    }
}
