<?php

namespace Genvoris\Laravel\DataObjects;

class Customer
{
    public function __construct(
        public readonly string $id,
        public readonly string $externalId,
        public readonly ?string $email,
        public readonly ?string $planId,
        public readonly ?string $status,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
    ) {}

    public static function fromArray(array $data): static
    {
        return new static(
            id: $data['id'] ?? '',
            externalId: $data['externalId'] ?? '',
            email: $data['email'] ?? null,
            planId: $data['planId'] ?? null,
            status: $data['status'] ?? null,
            createdAt: $data['createdAt'] ?? null,
            updatedAt: $data['updatedAt'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'externalId' => $this->externalId,
            'email' => $this->email,
            'planId' => $this->planId,
            'status' => $this->status,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }
}
