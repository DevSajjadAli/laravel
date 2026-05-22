<?php

namespace Genvoris\Laravel\DataObjects;

class Session
{
    public function __construct(
        public readonly string $token,
        public readonly ?string $tokenType,
        public readonly ?int $expiresIn,
        public readonly ?string $customerId,
        public readonly ?string $expiresAt,
    ) {}

    public static function fromArray(array $data): static
    {
        return new static(
            token: $data['token'] ?? '',
            tokenType: $data['tokenType'] ?? null,
            expiresIn: isset($data['expiresIn']) ? (int) $data['expiresIn'] : null,
            customerId: $data['customerId'] ?? null,
            expiresAt: $data['expiresAt'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'token' => $this->token,
            'tokenType' => $this->tokenType,
            'expiresIn' => $this->expiresIn,
            'customerId' => $this->customerId,
            'expiresAt' => $this->expiresAt,
        ];
    }
}
