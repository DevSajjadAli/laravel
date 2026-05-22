<?php

namespace Genvoris\Laravel\Blade;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;

class GenvorisBladeDirectives
{
    /**
     * Register all @genvoris* Blade directives.
     * Called from GenvorisServiceProvider::boot().
     */
    public static function register(): void
    {
        // @genvorisScripts — emits the widget loader <script> tag
        Blade::directive('genvorisScripts', function () {
            return '<?php echo \Genvoris\Laravel\Blade\GenvorisBladeDirectives::renderScripts(); ?>';
        });

        // @genvorisConfig($options = []) — emits window.genvorisConfig inline script
        Blade::directive('genvorisConfig', function (string $expression) {
            $expr = $expression ?: '[]';

            return "<?php echo \\Genvoris\\Laravel\\Blade\\GenvorisBladeDirectives::renderConfig({$expr}); ?>";
        });

        // @genvorisWidget($options = []) — config + scripts combined
        Blade::directive('genvorisWidget', function (string $expression) {
            $expr = $expression ?: '[]';

            return "<?php echo \\Genvoris\\Laravel\\Blade\\GenvorisBladeDirectives::renderWidget({$expr}); ?>";
        });

        // @genvorisTryOnButton($options = []) — renders the try-on button component
        Blade::directive('genvorisTryOnButton', function (string $expression) {
            $expr = $expression ?: '[]';

            return "<?php echo \\Genvoris\\Laravel\\Blade\\GenvorisBladeDirectives::renderTryOnButton({$expr}); ?>";
        });
    }

    // ------------------------------------------------------------------
    // Render helpers (called at runtime via the compiled Blade output)
    // ------------------------------------------------------------------

    public static function renderScripts(): string
    {
        $widgetUrl = htmlspecialchars(
            config('genvoris.widget_url', 'https://api.genvoris.org/widget.js'),
            ENT_QUOTES | ENT_SUBSTITUTE,
            'UTF-8',
        );

        return "<script src=\"{$widgetUrl}\" defer></script>";
    }

    /**
     * Emits window.genvorisConfig without EVER including api_key or webhook.secret.
     *
     * @param  array<string, mixed>  $options  Caller-supplied overrides.
     */
    public static function renderConfig(array $options = []): string
    {
        $proxyBase = '';
        if (Route::has('genvoris.proxy')) {
            // Build the proxy base URL from the named route (strip wildcard segment)
            $proxyBase = rtrim(url(config('genvoris.proxy.path', 'genvoris-proxy')), '/').'/';
        }

        $cfg = array_merge([
            'apiProxyBase' => $proxyBase,
            'widgetEnabled' => true,
        ], $options);

        // Security: NEVER expose api_key, webhook.secret, or any sensitive config
        unset($cfg['api_key'], $cfg['apiKey'], $cfg['webhook_secret'], $cfg['webhookSecret']);

        $json = json_encode(
            $cfg,
            JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE,
        );

        return "<script>window.genvorisConfig = {$json};</script>";
    }

    public static function renderWidget(array $options = []): string
    {
        return static::renderConfig($options)."\n".static::renderScripts();
    }

    public static function renderTryOnButton(array $options = []): string
    {
        $productId = $options['productId'] ?? '';
        $label = htmlspecialchars($options['label'] ?? 'Try On', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $class = htmlspecialchars($options['class'] ?? 'genvoris-try-on-btn', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $productId = htmlspecialchars((string) $productId, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

        return "<button class=\"{$class}\" data-genvoris-product=\"{$productId}\">{$label}</button>";
    }
}
