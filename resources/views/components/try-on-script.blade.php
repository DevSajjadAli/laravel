{{--
  genvoris::components.try-on-script
  Scripts-only partial (no config). Include after @genvorisConfig.
  Usage: @include('genvoris::components.try-on-script')
--}}
{!! \Genvoris\Laravel\Blade\GenvorisBladeDirectives::renderScripts([
    'token' => $token ?? null,
    'noFab' => $noFab ?? true,
]) !!}
