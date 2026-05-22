<?php

namespace Genvoris\Laravel\DataObjects;

class CustomerUsage
{
    /**
     * @param  array<string, mixed>  $current  Keys: used, limit, remaining, ok
     * @param  array<int, mixed>  $history
     */
    public function __construct(
        public readonly string $customerId,
        public readonly ?string $externalId,
        public readonly ?string $planName,
        public readonly ?string $status,
        public readonly ?string $periodStart,
        public readonly ?string $periodEnd,
        public readonly array $current,
        public readonly array $history,
    ) {}

    public static function fromArray(array $data): static
    {
        return new static(
            customerId: $data['customerId'] ?? '',
            externalId: $data['externalId'] ?? null,
            planName: $data['planName'] ?? null,
            status: $data['status'] ?? null,
            periodStart: $data['periodStart'] ?? null,
            periodEnd: $data['periodEnd'] ?? null,
            current: is_array($data['current'] ?? null) ? $data['current'] : [],
            history: is_array($data['history'] ?? null) ? $data['history'] : [],
        );
    }

    public function canTryOn(): bool
    {
        if ($this->status === 'quota_exhausted') {
            return false;
        }

        // Support flat {ok: bool} format
        if (isset($this->current['ok'])) {
            return (bool) $this->current['ok'];
        }

        // Support array-of-periods format: [{tryOnsUsed, tryOnsLimit}, ...]
        $period = $this->current[0] ?? null;
        if ($period === null) {
            return true;
        }

        $used = (int) ($period['tryOnsUsed'] ?? 0);
        $limit = (int) ($period['tryOnsLimit'] ?? 0);

        return $limit === 0 || $used < $limit;
    }

    public function toArray(): array
    {
        return [
            'customerId' => $this->customerId,
            'externalId' => $this->externalId,
            'planName' => $this->planName,
            'status' => $this->status,
            'periodStart' => $this->periodStart,
            'periodEnd' => $this->periodEnd,
            'current' => $this->current,
            'history' => $this->history,
        ];
    }
}
