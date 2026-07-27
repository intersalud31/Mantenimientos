@props([
    'action' => '',
    'method' => 'GET',
    'title' => null,
    'subtitle' => null,
    'gridClass' => '',
    'submitLabel' => 'Aplicar',
    'resetLabel' => 'Limpiar',
    'resetUrl' => null,
    'loadingLabel' => 'Buscando...',
    'live' => false,
    'liveTarget' => null,
])

@php
    $formClasses = trim('panel-filter-card' . ($live ? ' panel-filter-card--live' : ''));
    $gridClasses = trim('panel-filter-grid ' . $gridClass);
@endphp

<form
    method="{{ $method }}"
    action="{{ $action }}"
    class="{{ $formClasses }}"
    @if ($live && $liveTarget) data-panel-filter-live="true" data-panel-filter-live-target="{{ $liveTarget }}" @endif
>
    <div class="panel-filter-shell">
        @if ($title !== null || $subtitle !== null)
            <div class="panel-filter-header">
                <div class="panel-filter-copy">
                    @if ($title !== null && $title !== '')
                        <p class="section-kicker panel-filter-kicker">{{ $title }}</p>
                    @endif
                    @if ($subtitle !== null && $subtitle !== '')
                        <p class="muted panel-filter-subtitle">{{ $subtitle }}</p>
                    @endif
                </div>

                <span class="panel-filter-loading" data-panel-filter-loading hidden>{{ $loadingLabel }}</span>
            </div>
        @endif

        <div class="{{ $gridClasses }}">
            {{ $fields ?? $slot }}

            @isset($hidden)
                <div class="panel-filter-hidden">
                    {{ $hidden }}
                </div>
            @endisset

            @isset($actions)
                <div class="panel-filter-actions">
                    {{ $actions }}
                </div>
            @endisset
        </div>
    </div>
</form>
