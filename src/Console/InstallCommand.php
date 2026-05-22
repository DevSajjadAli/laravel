<?php

namespace Genvoris\Laravel\Console;

use Illuminate\Console\Command;

class InstallCommand extends Command
{
    protected $signature = 'genvoris:install';

    protected $description = 'Install the Genvoris Laravel package: publish config, add .env keys.';

    public function handle(): int
    {
        $this->info('Installing Genvoris...');

        // 1. Publish config
        if ($this->confirm('Publish config/genvoris.php?', true)) {
            $this->callSilent('vendor:publish', ['--tag' => 'genvoris-config', '--force' => false]);
            $this->line('  <info>✓</info> Config published to config/genvoris.php');
        }

        // 2. Publish views
        if ($this->confirm('Publish Blade views to resources/views/vendor/genvoris/?', false)) {
            $this->callSilent('vendor:publish', ['--tag' => 'genvoris-views', '--force' => false]);
            $this->line('  <info>✓</info> Views published.');
        }

        // 3. Publish migration
        if ($this->confirm('Publish optional genvoris_customer_sessions migration?', false)) {
            $this->callSilent('vendor:publish', ['--tag' => 'genvoris-migrations', '--force' => false]);
            $this->line('  <info>✓</info> Migration published. Run `php artisan migrate` to create the table.');
        }

        // 4. Append keys to .env / .env.example if missing
        $this->addEnvKeys();

        $this->newLine();
        $this->info('Genvoris installed successfully.');
        $this->line('Next steps:');
        $this->line('  1. Set <comment>GENVORIS_API_KEY</comment> in your .env file.');
        $this->line('  2. Set <comment>GENVORIS_WEBHOOK_SECRET</comment> in your .env file (if using webhooks).');
        $this->line('  3. Run <comment>php artisan genvoris:test-connection</comment> to verify your API key.');
        $this->line('  Docs: <comment>https://docs.genvoris.org</comment>  Support: <comment>support@genvoris.org</comment>');

        return self::SUCCESS;
    }

    private function addEnvKeys(): void
    {
        $keys = [
            'GENVORIS_API_KEY' => '',
            'GENVORIS_WEBHOOK_SECRET' => '',
        ];

        foreach ([base_path('.env'), base_path('.env.example')] as $envFile) {
            if (! file_exists($envFile)) {
                continue;
            }

            $contents = file_get_contents($envFile);

            foreach ($keys as $key => $default) {
                if (! str_contains($contents, $key)) {
                    file_put_contents($envFile, "\n{$key}={$default}\n", FILE_APPEND);
                    $this->line("  <info>✓</info> Added {$key} to ".basename($envFile));
                }
            }
        }
    }
}
