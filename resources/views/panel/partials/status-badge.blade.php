@php
    $state = $state ?? 'neutral';
    $label = $label ?? '';
@endphp

<span class="status-badge status-badge--{{ $state }}">{{ $label }}</span>
