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
        Blade::directive('genvorisScripts', function (string $expression) {
            $expr = $expression ?: '[]';

            return "<?php echo \\Genvoris\\Laravel\\Blade\\GenvorisBladeDirectives::renderScripts({$expr}); ?>";
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

    public static function renderScripts(array $options = []): string
    {
        $widgetUrl = static::appendQuery(
            (string) config('genvoris.widget_url', 'https://api.genvoris.org/widget.js'),
            ['no_fab' => ! empty($options['noFab']) ? '1' : null],
        );
        $attrs = [
            'src' => $widgetUrl,
            'defer' => true,
            'data-vto-widget' => true,
            'data-api-url' => static::proxyBase(),
            'data-events-url' => static::eventsUrl(),
            'data-platform' => 'laravel',
        ];

        $token = $options['token'] ?? $options['customerToken'] ?? null;
        if (! empty($token)) {
            $attrs['data-token'] = (string) $token;
            $attrs['data-customer-token'] = (string) $token;
        }
        if (! empty($options['noFab'])) {
            $attrs['data-no-fab'] = 'true';
        }

        return '<script'.static::htmlAttributes($attrs).'></script>';
    }

    /**
     * Emits window.genvorisConfig without EVER including api_key or webhook.secret.
     *
     * @param  array<string, mixed>  $options  Caller-supplied overrides.
     */
    public static function renderConfig(array $options = []): string
    {
        $cfg = array_merge([
            'apiProxyBase' => static::proxyBase(),
            'eventsUrl' => static::eventsUrl(),
            'platform' => 'laravel',
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
        return static::renderConfig($options)."\n".static::renderScripts(array_merge($options, ['noFab' => $options['noFab'] ?? true]));
    }

    public static function renderTryOnButton(array $options = []): string
    {
        $label = htmlspecialchars((string) ($options['label'] ?? 'Try On'), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        $attrs = [
            'type' => 'button',
            'class' => (string) ($options['class'] ?? 'genvoris-try-on-btn'),
            'data-genvoris-trigger' => true,
            'data-genvoris-product' => (string) ($options['productId'] ?? ''),
        ];

        foreach ([
            'productTitle' => 'data-genvoris-title',
            'productImage' => 'data-genvoris-image',
            'productCategory' => 'data-genvoris-category',
            'productDescription' => 'data-genvoris-description',
            'sku' => 'data-genvoris-sku',
            'price' => 'data-genvoris-price',
            'currency' => 'data-genvoris-currency',
        ] as $option => $attribute) {
            if (isset($options[$option]) && $options[$option] !== '') {
                $attrs[$attribute] = (string) $options[$option];
            }
        }

        return '<button'.static::htmlAttributes($attrs).'>'.$label.'</button>';
    }

    /** @param array<string, string|null> $params */
    private static function appendQuery(string $url, array $params): string
    {
        $query = [];
        foreach ($params as $key => $value) {
            if ($value !== null && $value !== '') {
                $query[$key] = $value;
            }
        }
        if ($query === []) {
            return $url;
        }

        return $url.(str_contains($url, '?') ? '&' : '?').http_build_query($query);
    }

    /** @param array<string, bool|string> $attrs */
    private static function htmlAttributes(array $attrs): string
    {
        $html = '';
        foreach ($attrs as $key => $value) {
            if ($value === false || $value === '') {
                continue;
            }
            if ($value === true) {
                $html .= ' '.htmlspecialchars((string) $key, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
                continue;
            }
            $html .= ' '.htmlspecialchars((string) $key, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')
                .'="'.htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8').'"';
        }

        return $html;
    }

    private static function proxyBase(): string
    {
        if (! Route::has('genvoris.proxy')) {
            return '';
        }

        return rtrim(url(config('genvoris.proxy.path', 'genvoris-proxy')), '/').'/';
    }

    private static function eventsUrl(): string
    {
        $proxyBase = static::proxyBase();
        if ($proxyBase === '') {
            return '';
        }

        return $proxyBase.ltrim((string) config('genvoris.proxy.events_path', 'api/v1/events'), '/');
    }
}
