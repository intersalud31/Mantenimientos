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
    $pageTitle = $pageTitle ?? '';
    $pageSubtitle = $pageSubtitle ?? '';
    $showSearch = (bool) ($showSearch ?? false);
    $searchAction = $searchAction ?? '';
    $searchName = $searchName ?? 'q';
    $searchValue = $searchValue ?? '';
    $searchPlaceholder = $searchPlaceholder ?? 'Buscar';
    $searchLabel = $searchLabel ?? 'Buscar';

    $menus = [
        [
            'label' => 'Operación',
            'items' => [
                ['label' => 'Panel principal', 'route' => 'panel'],
                ['label' => 'Usuarios / Activos', 'route' => 'panel.usuarios'],
                ['label' => 'Próximos', 'route' => 'panel.proximos'],
                ['label' => 'Programados', 'route' => 'panel.programados'],
            ],
        ],
        [
            'label' => 'Historial',
            'items' => [
                ['label' => 'Hechos', 'route' => 'panel.hechos'],
                ['label' => 'Evidencias PDF', 'route' => null],
                ['label' => 'Reversiones', 'route' => 'panel.configuracion'],
            ],
        ],
        [
            'label' => 'Reportes',
            'items' => [
                ['label' => 'Resumen semestral', 'route' => 'panel.reportes'],
                ['label' => 'Cumplimiento por periodo', 'route' => 'panel.reportes'],
                ['label' => 'Pendientes', 'route' => 'panel.proximos'],
                ['label' => 'Programados', 'route' => 'panel.programados'],
            ],
        ],
        [
            'label' => 'Administración',
            'items' => [
                ['label' => 'Configuración', 'route' => 'panel.configuracion'],
                ['label' => 'Parámetros', 'route' => null],
                ['label' => 'Estados', 'route' => null],
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
            <span class="chip">Sistema corporativo</span>
            @if ($showSearch && $searchAction !== '')
                <form class="topbar-search" method="GET" action="{{ $searchAction }}">
                    <span class="search-icon">⌕</span>
                    <input type="text" name="{{ $searchName }}" value="{{ $searchValue }}" placeholder="{{ $searchPlaceholder }}" aria-label="{{ $searchLabel }}">
                    @foreach ($periodoQuery as $clave => $valor)
                        <input type="hidden" name="{{ $clave }}" value="{{ $valor }}">
                    @endforeach
                </form>
            @endif
        </div>
    </div>
</header>

@if ($pageTitle !== '' || $pageSubtitle !== '')
    <div class="page-identity">
        <div class="topbar-inner" style="padding: 0; grid-template-columns: 1fr;">
            <div class="hero-copy">
                @if ($pageTitle !== '')
                    <h1>{{ $pageTitle }}</h1>
                @endif
                @if ($pageSubtitle !== '')
                    <p class="muted">{{ $pageSubtitle }}</p>
                @endif
            </div>
        </div>
    </div>
@endif
