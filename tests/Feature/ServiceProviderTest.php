<?php

namespace Genvoris\Laravel\Tests\Feature;

use Genvoris\Laravel\Facades\Genvoris;
use Genvoris\Laravel\Tests\TestCase;

class ServiceProviderTest extends TestCase
{
    public function test_genvoris_facade_resolves(): void
    {
        $this->assertInstanceOf(\Genvoris\Laravel\Genvoris::class, Genvoris::getFacadeRoot());
    }

    public function test_config_is_loaded(): void
    {
        $this->assertNotNull(config('genvoris.api_key'));
        $this->assertNotNull(config('genvoris.api_base_url'));
        $this->assertSame('https://api.genvoris.org/widget.js', config('genvoris.widget_url'));
    }

    public function test_genvoris_binding_resolves(): void
    {
        $this->assertInstanceOf(
            \Genvoris\Laravel\Genvoris::class,
            app('genvoris'),
        );
    }
}
