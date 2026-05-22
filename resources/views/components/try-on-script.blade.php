{{--
  genvoris::components.try-on-script
  Scripts-only partial (no config). Include after @genvorisConfig.
  Usage: @include('genvoris::components.try-on-script')
--}}
<script src="{{ config('genvoris.widget_url', 'https://api.genvoris.org/widget.js') }}" defer></script>
