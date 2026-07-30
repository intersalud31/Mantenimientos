<link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ @filemtime(public_path('css/app.css')) ?: time() }}">
<script defer src="{{ asset('js/app.js') }}?v={{ @filemtime(public_path('js/app.js')) ?: time() }}"></script>
