<?php

namespace Genvoris\Laravel\Tests\Unit;

use Genvoris\Laravel\Blade\GenvorisBladeDirectives;
use Genvoris\Laravel\Tests\TestCase;

class BladeDirectivesTest extends TestCase
{
    public function test_render_scripts_outputs_widget_script_tag(): void
    {
        $html = GenvorisBladeDirectives::renderScripts(['token' => 'jwt_token', 'noFab' => true]);

        $this->assertStringContainsString('<script', $html);
        $this->assertStringContainsString('api.genvoris.org/widget.js?no_fab=1', $html);
        $this->assertStringContainsString('data-api-url', $html);
        $this->assertStringContainsString('data-events-url', $html);
        $this->assertStringContainsString('data-platform="laravel"', $html);
        $this->assertStringContainsString('data-token="jwt_token"', $html);
        $this->assertStringContainsString('data-customer-token="jwt_token"', $html);
        $this->assertStringContainsString('data-no-fab="true"', $html);
        $this->assertStringContainsString('defer', $html);
    }

    public function test_render_config_never_includes_api_key(): void
    {
        $html = GenvorisBladeDirectives::renderConfig(['widgetEnabled' => true]);

        $this->assertStringNotContainsString(config('genvoris.api_key'), $html);
        $this->assertStringNotContainsString('gvk_', $html);
    }

    public function test_render_config_never_includes_webhook_secret(): void
    {
        $html = GenvorisBladeDirectives::renderConfig([]);

        $this->assertStringNotContainsString(config('genvoris.webhook.secret', ''), $html);
    }

    public function test_render_config_includes_proxy_base_and_widget_enabled(): void
    {
        $html = GenvorisBladeDirectives::renderConfig([]);

        $this->assertStringContainsString('window.genvorisConfig', $html);
        $this->assertStringContainsString('widgetEnabled', $html);
    }

    public function test_render_try_on_button_escapes_html(): void
    {
        $html = GenvorisBladeDirectives::renderTryOnButton([
            'productId' => '<script>alert(1)</script>',
        ]);

        $this->assertStringNotContainsString('<script>', $html);
        $this->assertStringContainsString('&lt;script&gt;', $html);
        $this->assertStringContainsString('data-genvoris-trigger', $html);
    }

    public function test_widget_url_is_correct_host(): void
    {
        $html = GenvorisBladeDirectives::renderScripts();

        // Must use api.genvoris.org, NOT cdn.genvoris.org
        $this->assertStringContainsString('api.genvoris.org', $html);
        $this->assertStringNotContainsString('cdn.genvoris.org', $html);
    }
}
