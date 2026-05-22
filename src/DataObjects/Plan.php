<?php

namespace Genvoris\Laravel\DataObjects;

class Plan
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly ?int $monthlyTryOns,
        public readonly ?string $externalPriceId,
        public readonly bool $active,
        public readonly ?string $createdAt,
        public readonly ?string $updatedAt,
    ) {}

    public static function fromArray(array $data): static
    {
        return new static(
            id: $data['id'] ?? '',
            name: $data['name'] ?? '',
            monthlyTryOns: isset($data['monthlyTryOns']) ? (int) $data['monthlyTryOns'] : null,
            externalPriceId: $data['externalPriceId'] ?? null,
            active: (bool) ($data['active'] ?? true),
            createdAt: $data['createdAt'] ?? null,
            updatedAt: $data['updatedAt'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'monthlyTryOns' => $this->monthlyTryOns,
            'externalPriceId' => $this->externalPriceId,
            'active' => $this->active,
            'createdAt' => $this->createdAt,
            'updatedAt' => $this->updatedAt,
        ];
    }
}
