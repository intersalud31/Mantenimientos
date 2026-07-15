@php
    $currentRoute = $activeRoute ?? optional(request()->route())->getName();
    $periodoConsulta = $periodoConsulta ?? null;
    $periodoQuery = [];

    if (is_array($periodoConsulta)) {
        $periodoQuery = array_filter([
            'anio' => $periodoConsulta['anio'] ?? null,
            'semestre' => $periodoConsulta['semestre'] ?? null,
        ], static fn ($valor) => $valor !== null && $valor !== '');
    }

    $sections = [
        ['route' => 'panel', 'label' => 'Panel'],
        ['route' => 'panel.usuarios', 'label' => 'Usuarios'],
        ['route' => 'panel.proximos', 'label' => 'Próximos'],
        ['route' => 'panel.programados', 'label' => 'Programados'],
        ['route' => 'panel.hechos', 'label' => 'Hechos'],
        ['route' => 'panel.reportes', 'label' => 'Reportes'],
        ['route' => 'panel.configuracion', 'label' => 'Configuración'],
    ];
@endphp

<div class="actions panel-navigation" aria-label="Navegación principal del panel">
    @foreach ($sections as $section)
        <a class="btn-link {{ $currentRoute === $section['route'] ? 'secondary' : '' }}" href="{{ $periodoQuery !== [] ? route($section['route'], $periodoQuery) : route($section['route']) }}">
            {{ $section['label'] }}
        </a>
    @endforeach
</div>
