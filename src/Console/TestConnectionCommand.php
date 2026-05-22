<?php

namespace Genvoris\Laravel\Console;

use Genvoris\Laravel\Exceptions\AuthException;
use Genvoris\Laravel\Exceptions\GenvorisException;
use Genvoris\Laravel\Facades\Genvoris;
use Illuminate\Console\Command;

class TestConnectionCommand extends Command
{
    protected $signature = 'genvoris:test-connection';

    protected $description = 'Verify that your GENVORIS_API_KEY is valid by listing plans.';

    public function handle(): int
    {
        $apiKey = config('genvoris.api_key', '');

        if ($apiKey === '') {
            $this->error('GENVORIS_API_KEY is not set. Run `php artisan genvoris:install`.');

            return self::FAILURE;
        }

        // Show only first 8 chars of the key — never the full value
        $maskedKey = substr($apiKey, 0, 8).'...';
        $this->line("Testing connection with key: <comment>{$maskedKey}</comment>");

        try {
            $plans = Genvoris::listPlans();
        } catch (AuthException $e) {
            $this->error('Authentication failed. Your API key may be invalid or revoked.');
            $this->line('Check your GENVORIS_API_KEY and visit https://docs.genvoris.org');

            return self::FAILURE;
        } catch (GenvorisException $e) {
            $this->error('Connection failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $count = count($plans);
        $this->info("Connection successful. {$count} plan(s) found on your account.");

        if ($count > 0) {
            $this->line('  First plan: <comment>'.$plans[0]->name.'</comment>');
        }

        return self::SUCCESS;
    }
}
