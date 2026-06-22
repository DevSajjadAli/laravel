{{--
  genvoris::components.try-on-button
  Trigger button that activates the Genvoris widget.
  Usage: @include('genvoris::components.try-on-button', ['productId' => $product->id])
--}}
{!! \Genvoris\Laravel\Blade\GenvorisBladeDirectives::renderTryOnButton([
    'productId' => $productId ?? '',
    'productTitle' => $productTitle ?? '',
    'productImage' => $productImage ?? '',
    'productCategory' => $productCategory ?? '',
    'productDescription' => $productDescription ?? '',
    'sku' => $sku ?? '',
    'price' => $price ?? '',
    'currency' => $currency ?? '',
    'label' => $label ?? 'Try On',
    'class' => $class ?? 'genvoris-try-on-btn',
]) !!}
