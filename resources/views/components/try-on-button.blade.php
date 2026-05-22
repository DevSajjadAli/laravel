{{--
  genvoris::components.try-on-button
  Trigger button that activates the Genvoris widget.
  Usage: @include('genvoris::components.try-on-button', ['productId' => $product->id])
--}}
<button
    class="{{ $class ?? 'genvoris-try-on-btn' }}"
    data-genvoris-product="{{ $productId ?? '' }}"
>{{ $label ?? 'Try On' }}</button>
