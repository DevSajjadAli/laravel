<?php

namespace Genvoris\Laravel\Tests;

use Genvoris\Laravel\GenvorisServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [GenvorisServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('genvoris.api_key', env('GENVORIS_API_KEY', 'gvk_test_placeholder'));
        $app['config']->set('genvoris.webhook.secret', env('GENVORIS_WEBHOOK_SECRET', 'test_webhook_secret_placeholder'));
        $app['config']->set('genvoris.api_base_url', 'https://genvoris.org/api/v1');
        $app['config']->set('genvoris.widget_url', 'https://api.genvoris.org/widget.js');
    }
}
