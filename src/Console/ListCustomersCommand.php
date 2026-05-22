<?php

namespace Genvoris\Laravel\Console;

use Genvoris\Laravel\Facades\Genvoris;
use Illuminate\Console\Command;

class ListCustomersCommand extends Command
{
    protected $signature = 'genvoris:list-customers
                            {--limit=20 : Maximum number of customers to show}
                            {--page=1   : Page number}';

    protected $description = 'List your Genvoris end-customers (paginated).';

    public function handle(): int
    {
        $limit = max(1, (int) $this->option('limit'));
        $page = max(1, (int) $this->option('page'));

        $customers = Genvoris::listCustomers(['limit' => $limit, 'page' => $page]);

        if (count($customers) === 0) {
            $this->info('No customers found.');

            return self::SUCCESS;
        }

        $rows = array_map(fn ($c) => [
            $c->id,
            $c->externalId,
            $c->email ?? '—',
            $c->status ?? '—',
            $c->planId ?? '—',
        ], $customers);

        $this->table(['ID', 'External ID', 'Email', 'Status', 'Plan ID'], $rows);
        $this->line('Showing '.count($customers).' customer(s). Use --page to paginate.');

        return self::SUCCESS;
    }
}
