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

    $periodoEtiqueta = $periodoConsulta['etiqueta'] ?? ($periodoConsulta['periodo_label'] ?? 'Semestre actual');
    $brand = $brand ?? 'Mantenimientos TI';
    $subbrand = $subbrand ?? 'Dashboard administrativo';

    $menus = [
        [
            'label' => 'Operación',
            'items' => [
                ['label' => 'Panel principal', 'route' => 'panel'],
                ['label' => 'Usuarios y activos', 'route' => 'panel.usuarios'],
                ['label' => 'Próximos', 'route' => 'panel.proximos'],
                ['label' => 'Programados', 'route' => 'panel.programados'],
            ],
        ],
        [
            'label' => 'Historial',
            'items' => [
                ['label' => 'Hechos', 'route' => 'panel.hechos'],
                ['label' => 'Reversiones', 'route' => null],
            ],
        ],
        [
            'label' => 'Reportes',
            'items' => [
                ['label' => 'Resumen semestral', 'route' => 'panel.reportes'],
            ],
        ],
        [
            'label' => 'Administración',
            'items' => [
                ['label' => 'Configuración', 'route' => 'panel.configuracion'],
            ],
        ],
    ];
@endphp

<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

<header class="topbar-shell" x-data="{ openMenu: null }" @click.outside="openMenu = null">
    <div class="topbar-inner">
        <div class="brand-block">
            <div class="brand-mark">TI</div>
            <div class="brand-copy">
                <strong>{{ $brand }}</strong>
                <span>{{ $subbrand }}</span>
            </div>
        </div>

        <nav class="topbar-menu" aria-label="Navegación principal">
            @foreach ($menus as $menu)
                <div class="menu-group">
                    <button
                        type="button"
                        class="menu-trigger"
                        @click.stop
                        @click="openMenu === '{{ $menu['label'] }}' ? openMenu = null : openMenu = '{{ $menu['label'] }}'"
                        :aria-expanded="openMenu === '{{ $menu['label'] }}'"
                    >
                        {{ $menu['label'] }}
                        <span class="menu-caret"></span>
                    </button>

                    <div class="dropdown-panel" x-cloak x-show="openMenu === '{{ $menu['label'] }}'" x-transition.origin.top.left @click.stop>
                        @foreach ($menu['items'] as $item)
                            @if (!empty($item['route']) && Route::has($item['route']))
                                <a
                                    href="{{ $periodoQuery !== [] ? route($item['route'], $periodoQuery) : route($item['route']) }}"
                                    class="dropdown-link {{ $currentRoute === $item['route'] ? 'active' : '' }}"
                                >
                                    <span>{{ $item['label'] }}</span>
                                </a>
                            @else
                                <span class="dropdown-disabled">
                                    <span>{{ $item['label'] }}</span>
                                    <span class="mini">Próximamente</span>
                                </span>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endforeach
        </nav>

        <div class="topbar-utility">
            <span class="chip chip--neutral">{{ $periodoEtiqueta }}</span>
            <span class="chip">Hechos</span>
        </div>
    </div>
</header>
