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
    $showModuleContext = (bool) ($showModuleContext ?? true);
    $showSearch = (bool) ($showSearch ?? false);
    $searchAction = $searchAction ?? '';
    $searchName = $searchName ?? 'q';
    $searchValue = $searchValue ?? '';
    $searchPlaceholder = $searchPlaceholder ?? 'Buscar';
    $searchLabel = $searchLabel ?? 'Buscar';
    $authUser = auth()->user() ?? (session()->has('usuario_autenticado') ? (object) session('usuario_autenticado') : null);
    $authName = trim((string) ($authUser->name ?? $authUser->nombre ?? $authUser->correo ?? 'Administrador'));
    $authInitials = mb_strtoupper(mb_substr($authName, 0, 2, 'UTF-8'), 'UTF-8');

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
                ['label' => 'Reversiones', 'route' => null],
            ],
        ],
        [
            'label' => 'Reportes',
            'items' => [
                ['label' => 'Resumen semestral', 'route' => 'panel.reportes'],
                ['label' => 'Cumplimiento por periodo', 'route' => 'panel.reportes'],
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

<header class="topbar-shell topbar-shell--stacked" x-data="{ openMenu: null }" @keydown.escape.window="openMenu = null">
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
                @php
                    $menuKey = 'menu-' . $loop->index;
                    $menuActive = collect($menu['items'])->contains(static function ($item) use ($currentRoute) {
                        return !empty($item['route']) && $currentRoute === $item['route'];
                    });
                @endphp
                <div
                    class="menu-group"
                    @mouseenter="openMenu = '{{ $menuKey }}'"
                    @mouseleave="openMenu = null"
                >
                    <button
                        type="button"
                        class="menu-trigger {{ $menuActive ? 'menu-trigger--active' : '' }}"
                        @click="openMenu === '{{ $menuKey }}' ? openMenu = null : openMenu = '{{ $menuKey }}'"
                        :aria-expanded="openMenu === '{{ $menuKey }}'"
                        aria-haspopup="true"
                    >
                        <span class="menu-trigger-label">{{ $menu['label'] }}</span>
                        <span class="menu-caret"></span>
                    </button>

                    <div
                        class="dropdown-panel {{ $loop->last ? 'dropdown-panel-right' : '' }}"
                        x-cloak
                        x-show="openMenu === '{{ $menuKey }}'"
                        x-transition.opacity.scale.95.origin.top.left
                        @click.stop
                    >
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
            @if ($showSearch && $searchAction !== '')
                <form class="topbar-search" method="GET" action="{{ $searchAction }}">
                    <span class="search-icon">⌕</span>
                    <input type="text" name="{{ $searchName }}" value="{{ $searchValue }}" placeholder="{{ $searchPlaceholder }}" aria-label="{{ $searchLabel }}">
                    @foreach ($periodoQuery as $clave => $valor)
                        <input type="hidden" name="{{ $clave }}" value="{{ $valor }}">
                    @endforeach
                </form>
            @endif
            <span class="topbar-user-chip">
                <span class="topbar-user-avatar">{{ $authInitials }}</span>
                <span class="topbar-user-name">{{ $authName }}</span>
            </span>
            @if (Route::has('logout'))
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="btn btn-ghost" type="submit">Cerrar sesión</button>
                </form>
            @endif
        </div>
    </div>
    @if ($showModuleContext && ($pageTitle !== '' || $pageSubtitle !== ''))
        <div class="topbar-focus">
            <div class="topbar-focus-copy">
                @if ($pageTitle !== '')
                    <h1 class="topbar-focus-title">{{ $pageTitle }}</h1>
                @endif
                @if ($pageSubtitle !== '')
                    <p class="topbar-focus-subtitle">{{ $pageSubtitle }}</p>
                @endif
            </div>
        </div>
    @endif
</header>
