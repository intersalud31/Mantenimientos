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
    $authRole = trim((string) ($authUser->rol ?? ''));
    $authInitials = mb_strtoupper(mb_substr($authName, 0, 2, 'UTF-8'), 'UTF-8');

    $primaryLinks = [
        ['label' => 'Panel principal', 'route' => 'panel', 'icon' => 'PN'],
        ['label' => 'Lista de usuarios', 'route' => 'panel.usuarios', 'icon' => 'US'],
        ['label' => 'Configuraci&oacute;n', 'route' => 'panel.configuracion', 'icon' => 'CF'],
    ];

    $menus = [
        [
            'label' => 'Operaci&oacute;n',
            'icon' => 'OP',
            'items' => [
                ['label' => 'Pr&oacute;ximos', 'route' => 'panel.proximos', 'icon' => 'PX'],
                ['label' => 'Programados', 'route' => 'panel.programados', 'icon' => 'PG'],
                ['label' => 'Ejecutados', 'route' => 'panel.hechos', 'icon' => 'HE'],
            ],
        ],
        [
            'label' => 'Reportes',
            'icon' => 'RP',
            'items' => [
                ['label' => 'Resumen semestral', 'route' => 'panel.reportes', 'icon' => 'RS'],
            ],
        ],
    ];

    $activeMenuLabel = '';
    $activeItemLabel = '';
    foreach ($primaryLinks as $link) {
        if (!empty($link['route']) && $currentRoute === $link['route']) {
            $activeMenuLabel = html_entity_decode($link['label'], ENT_QUOTES, 'UTF-8');
            $activeItemLabel = html_entity_decode($link['label'], ENT_QUOTES, 'UTF-8');
            break;
        }
    }

    if ($activeItemLabel === '') {
        foreach ($menus as $menu) {
            foreach ($menu['items'] as $item) {
                if (!empty($item['route']) && $currentRoute === $item['route']) {
                    $activeMenuLabel = html_entity_decode($menu['label'], ENT_QUOTES, 'UTF-8');
                    $activeItemLabel = html_entity_decode($item['label'], ENT_QUOTES, 'UTF-8');
                    break 2;
                }
            }
        }
    }

    $contextTitle = trim((string) ($pageTitle !== '' ? $pageTitle : ($activeItemLabel !== '' ? $activeItemLabel : 'Panel')));
@endphp

@include('panel.partials.panel-assets')

<aside class="app-sidebar" id="app-sidebar" data-app-sidebar aria-label="Navegaci&oacute;n principal del panel">
    <div class="app-sidebar-brand">
        <a class="app-brand-link" href="{{ Route::has('panel') ? route('panel') : '#' }}" aria-label="{{ $brand }}">
            <span class="app-brand-mark" aria-hidden="true">TI</span>
            <span class="app-brand-copy">
                <strong>{{ $brand }}</strong>
                <span>{{ $subbrand }}</span>
            </span>
        </a>
        <button
            type="button"
            class="app-icon-button app-sidebar-close"
            data-app-sidebar-close
            aria-label="Cerrar men&uacute;"
        >
            <span aria-hidden="true">x</span>
        </button>
    </div>

    <nav class="app-sidebar-nav" aria-label="M&oacute;dulos del sistema">
        <div class="app-nav-root-list" aria-label="Accesos principales">
            @foreach ($primaryLinks as $link)
                @if (!empty($link['route']) && Route::has($link['route']))
                    <a
                        href="{{ $periodoQuery !== [] ? route($link['route'], $periodoQuery) : route($link['route']) }}"
                        class="app-nav-link app-nav-link--root {{ $currentRoute === $link['route'] ? 'app-nav-link--active' : '' }}"
                        data-app-sidebar-link
                        title="{!! $link['label'] !!}"
                        @if ($currentRoute === $link['route']) aria-current="page" @endif
                    >
                        <span class="app-nav-icon" aria-hidden="true">{{ $link['icon'] }}</span>
                        <span class="app-nav-label">{!! $link['label'] !!}</span>
                    </a>
                @endif
            @endforeach
        </div>

        @foreach ($menus as $menu)
            @php
                $menuActive = collect($menu['items'])->contains(static function ($item) use ($currentRoute) {
                    return !empty($item['route']) && $currentRoute === $item['route'];
                });
                $menuId = 'app-sidebar-group-' . $loop->index;
            @endphp
            <section class="app-nav-group {{ $menuActive ? 'app-nav-group--active' : '' }}" data-app-nav-group>
                <button
                    type="button"
                    class="app-nav-group-trigger"
                    data-app-nav-toggle
                    aria-expanded="{{ $menuActive ? 'true' : 'false' }}"
                    aria-controls="{{ $menuId }}"
                    title="{!! $menu['label'] !!}"
                >
                    <span class="app-nav-icon" aria-hidden="true">{{ $menu['icon'] }}</span>
                    <span class="app-nav-label">{!! $menu['label'] !!}</span>
                    <span class="app-nav-caret" aria-hidden="true"></span>
                </button>

                <div class="app-nav-panel" id="{{ $menuId }}" data-app-nav-panel @if(!$menuActive) hidden @endif>
                    @foreach ($menu['items'] as $item)
                        @if (!empty($item['route']) && Route::has($item['route']))
                            <a
                                href="{{ $periodoQuery !== [] ? route($item['route'], $periodoQuery) : route($item['route']) }}"
                                class="app-nav-link {{ $currentRoute === $item['route'] ? 'app-nav-link--active' : '' }}"
                                data-app-sidebar-link
                                title="{!! $item['label'] !!}"
                                @if ($currentRoute === $item['route']) aria-current="page" @endif
                            >
                                <span class="app-nav-icon app-nav-icon--sub" aria-hidden="true">{{ $item['icon'] }}</span>
                                <span class="app-nav-label">{!! $item['label'] !!}</span>
                            </a>
                        @else
                            <span class="app-nav-link app-nav-link--disabled" title="{!! $item['label'] !!}">
                                <span class="app-nav-icon app-nav-icon--sub" aria-hidden="true">{{ $item['icon'] }}</span>
                                <span class="app-nav-label">{!! $item['label'] !!}</span>
                                <span class="app-nav-meta">Pr&oacute;ximamente</span>
                            </span>
                        @endif
                    @endforeach
                </div>
            </section>
        @endforeach
    </nav>

    <div class="app-sidebar-user">
        <span class="app-user-avatar" aria-hidden="true">{{ $authInitials }}</span>
        <span class="app-user-copy">
            <strong>{{ $authName }}</strong>
            @if ($authRole !== '')
                <span>{{ mb_convert_case($authRole, MB_CASE_TITLE, 'UTF-8') }}</span>
            @else
                <span>Usuario activo</span>
            @endif
        </span>
    </div>
</aside>

<div class="app-sidebar-backdrop" data-app-sidebar-backdrop hidden></div>

<header class="app-topbar" data-app-topbar>
    <div class="app-topbar-left">
        <button
            type="button"
            class="app-icon-button app-sidebar-toggle"
            data-app-sidebar-toggle
            aria-controls="app-sidebar"
            aria-expanded="false"
            aria-label="Abrir o plegar navegaci&oacute;n"
        >
            <span class="app-toggle-lines" aria-hidden="true"></span>
        </button>

        <div class="app-context">
            <span class="app-context-kicker">{{ $activeMenuLabel !== '' ? $activeMenuLabel : 'Panel' }}</span>
            <strong>{{ $contextTitle }}</strong>
            @if ($pageSubtitle !== '')
                <span>{{ $pageSubtitle }}</span>
            @endif
        </div>
    </div>

    <div class="app-topbar-right">
        @if ($showSearch && $searchAction !== '')
            <form class="app-topbar-search" method="GET" action="{{ $searchAction }}">
                <span class="app-search-icon" aria-hidden="true">&#128269;</span>
                <input type="text" name="{{ $searchName }}" value="{{ $searchValue }}" placeholder="{{ $searchPlaceholder }}" aria-label="{{ $searchLabel }}">
                @foreach ($periodoQuery as $clave => $valor)
                    <input type="hidden" name="{{ $clave }}" value="{{ $valor }}">
                @endforeach
            </form>
        @endif

        @if ($periodoEtiqueta !== '')
            <span class="app-period-chip">{{ $periodoEtiqueta }}</span>
        @endif

        <span class="app-topbar-user">
            <span class="app-user-avatar" aria-hidden="true">{{ $authInitials }}</span>
            <span class="app-user-name">{{ $authName }}</span>
        </span>

        @if (Route::has('logout'))
            <form method="POST" action="{{ route('logout') }}" class="app-logout-form">
                @csrf
                <button class="btn btn-ghost app-logout-button" type="submit">Cerrar sesi&oacute;n</button>
            </form>
        @endif
    </div>

    @if ($showModuleContext && ($pageTitle !== '' || $pageSubtitle !== ''))
        <div class="app-topbar-focus">
            @if ($pageTitle !== '')
                <h1>{{ $pageTitle }}</h1>
            @endif
            @if ($pageSubtitle !== '')
                <p>{{ $pageSubtitle }}</p>
            @endif
        </div>
    @endif
</header>
