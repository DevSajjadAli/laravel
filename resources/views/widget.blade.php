{{--
  genvoris::widget
  Full integration partial: config script + widget loader script.
  Usage: @include('genvoris::widget', ['productId' => $product->id, 'token' => $session->token])
--}}
@php
    $genvorisOptions = array_filter([
        'productId' => $productId ?? null,
        'productTitle' => $productTitle ?? null,
        'productImage' => $productImage ?? null,
        'productCategory' => $productCategory ?? null,
        'productDescription' => $productDescription ?? null,
        'token' => $token ?? null,
        'noFab' => $noFab ?? true,
    ], static fn ($value) => $value !== null && $value !== '');
@endphp
{!! \Genvoris\Laravel\Blade\GenvorisBladeDirectives::renderWidget($genvorisOptions) !!}
