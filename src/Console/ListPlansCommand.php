<?php

namespace Genvoris\Laravel\Console;

use Genvoris\Laravel\Facades\Genvoris;
use Illuminate\Console\Command;

class ListPlansCommand extends Command
{
    protected $signature = 'genvoris:list-plans';

    protected $description = 'List all plans configured on your Genvoris account.';

    public function handle(): int
    {
        $plans = Genvoris::listPlans();

        if (count($plans) === 0) {
            $this->info('No plans found on your account.');

            return self::SUCCESS;
        }

        $rows = array_map(fn ($plan) => [
            $plan->id,
            $plan->name,
            $plan->active ? 'active' : 'disabled',
            $plan->monthlyTryOns ?? '—',
        ], $plans);

        $this->table(['ID', 'Name', 'Status', 'Monthly Try-Ons'], $rows);

        return self::SUCCESS;
    }
}
