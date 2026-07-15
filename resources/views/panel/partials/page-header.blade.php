@php
    $kicker = $kicker ?? '';
    $title = $title ?? '';
    $subtitle = $subtitle ?? '';
    $meta = $meta ?? [];
@endphp

<div class="page-header-compact">
    @if ($kicker !== '')
        <div class="page-header-kicker">{{ $kicker }}</div>
    @endif
    @if ($title !== '')
        <h1 class="page-header-title">{{ $title }}</h1>
    @endif
    @if ($subtitle !== '')
        <p class="page-header-subtitle">{{ $subtitle }}</p>
    @endif

    @if (!empty($meta))
        <div class="page-header-meta">
            @foreach ($meta as $item)
                <span class="chip chip--neutral">{{ $item }}</span>
            @endforeach
        </div>
    @endif
</div>
