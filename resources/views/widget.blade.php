{{--
  genvoris::widget
  Full integration partial: config script + widget loader script.
  Usage: @include('genvoris::widget', ['productId' => $product->id, 'token' => $session->token])
--}}
<script>
window.genvorisConfig = {!! json_encode(array_merge([
    'apiProxyBase'  => rtrim(url(config('genvoris.proxy.path', 'genvoris-proxy')), '/') . '/',
    'widgetEnabled' => true,
], array_filter([
    'productId' => $productId ?? null,
    'token'     => $token ?? null,
])), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) !!};
</script>
<script src="{{ config('genvoris.widget_url', 'https://api.genvoris.org/widget.js') }}" defer></script>
