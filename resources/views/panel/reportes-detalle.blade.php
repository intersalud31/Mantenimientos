<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle ?? 'Detalle del Dashboard' }}</title>
    @include('panel.partials.dashboard-modern-styles')
    @include('panel.partials.hechos-saas-overrides')
</head>
<body class="dashboard-modern">
    @include('panel.partials.topbar-saas', [
        'activeRoute' => $activeRoute ?? 'panel.reportes',
        'periodoConsulta' => $periodoConsulta ?? null,
        'brand' => 'Mantenimientos TI',
        'subbrand' => 'Dashboard administrativo',
        'showSearch' => false,
        'showModuleContext' => false,
    ])

    @php
        $periodoQuery = is_array($periodoQuery ?? null) ? $periodoQuery : [];
        $columns = is_array($columns ?? null) ? $columns : [];
        $items = is_array($items ?? null) ? $items : [];
        $busquedaActual = trim((string) ($searchValue ?? ''));
        $paginaActual = (int) ($pagina ?? 1);
        $totalPaginasDetalle = (int) ($totalPaginas ?? 1);
        $porPaginaActual = (int) ($porPagina ?? 10);
        $routeName = (string) ($routeName ?? 'panel.reportes');
        $searchName = (string) ($searchName ?? 'q_dashboard');
        $pageName = (string) ($pageName ?? 'pagina');
        $perPageName = (string) ($perPageName ?? 'por_pagina');
        $formatearFecha = static function (?string $valor): string {
            $valor = trim((string) $valor);

            if ($valor === '') {
                return '—';
            }

            try {
                return \Illuminate\Support\Carbon::parse($valor)->format('d/m/Y');
            } catch (\Throwable) {
                return $valor;
            }
        };
    @endphp

    <main class="wrap reportes-stack">
        <section class="module-header" aria-label="{{ $pageTitle ?? 'Detalle' }}">
            <div class="module-header-copy">
                <p class="module-header-kicker">Reportes</p>
                <h1 class="module-header-title">{{ $pageTitle ?? 'Detalle del Dashboard' }}</h1>
                <p class="module-header-subtitle">{{ $pageSubtitle ?? '' }}</p>
            </div>
        </section>

        <section class="card panel-card">
            <div class="users-module-shell users-module-shell--head">
                <div class="users-toolbar users-toolbar--integrated">
                    <div>
                        <p class="section-kicker">Gestión</p>
                        <h2 class="panel-section-title">{{ $searchLabel ?? 'Buscar registros' }}</h2>
                        <p class="muted panel-section-subtitle">Busca por usuario, área o código de activo.</p>
                    </div>
                </div>

                <x-panel.live-search
                    action="{{ route($routeName) }}"
                    name="{{ $searchName }}"
                    label="{{ $searchLabel ?? 'Buscar registros' }}"
                    placeholder="{{ $searchPlaceholder ?? 'Usuario, área o código activo' }}"
                    value="{{ $busquedaActual }}"
                    target="#dashboard-detalle-listado"
                    debounce="500"
                    loading-label="Buscando..."
                    page-name="{{ $pageName }}"
                    page-value="1"
                >
                    <x-slot:hidden>
                        @foreach ($periodoQuery as $clave => $valor)
                            <input type="hidden" name="{{ $clave }}" value="{{ $valor }}">
                        @endforeach
                        <input type="hidden" name="{{ $perPageName }}" value="{{ $porPaginaActual }}">
                    </x-slot:hidden>
                </x-panel.live-search>
            </div>
        </section>

        <section class="card panel-card" id="dashboard-detalle-listado">
            <div class="panel-card-header">
                <div class="panel-title-block">
                    <p class="section-kicker">{{ $sectionKicker ?? 'Detalle' }}</p>
                    <h2 class="panel-section-title">{{ $sectionTitle ?? 'Registros del indicador' }}</h2>
                    <p class="muted panel-section-subtitle">{{ $sectionSubtitle ?? ('Total: ' . ($total ?? 0)) }}</p>
                    @if (!empty($sectionMeta ?? ''))
                        <p class="muted panel-section-subtitle">{{ $sectionMeta }}</p>
                    @endif
                </div>
            </div>

            <div class="panel-table-shell">
                <table class="panel-table" aria-label="{{ $sectionTitle ?? 'Detalle' }}">
                    <thead>
                        <tr>
                            @foreach ($columns as $column)
                                <th>{{ $column['label'] ?? '' }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($items as $item)
                            <tr>
                                @foreach ($columns as $column)
                                    @php
                                        $key = (string) ($column['key'] ?? '');
                                        $value = $item[$key] ?? '';
                                        $type = (string) ($column['type'] ?? 'text');
                                        $badgeKey = (string) ($column['badge_key'] ?? '');
                                    @endphp
                                    <td>
                                        @if ($type === 'date')
                                            {{ $formatearFecha(is_string($value) ? $value : null) }}
                                        @elseif ($type === 'badge')
                                            <span class="estado-tabla {{ $item[$badgeKey] ?? 'estado-tabla-neutral' }}">
                                                {{ $value !== '' ? $value : '—' }}
                                            </span>
                                        @elseif ($type === 'badge-area')
                                            <span class="user-badge-area">{{ $value !== '' ? $value : 'SIN ÁREA' }}</span>
                                        @elseif ($type === 'text-primary')
                                            <span class="texto-principal-tabla">{{ $value !== '' ? $value : '—' }}</span>
                                        @else
                                            {{ $value !== '' ? $value : '—' }}
                                        @endif
                                    </td>
                                @endforeach
                            </tr>
                        @empty
                            <tr>
                                <td colspan="{{ count($columns) }}" class="muted panel-table-empty">{{ $emptyMessage ?? 'No hay registros para mostrar.' }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <nav role="navigation" class="pager" aria-label="Paginación del detalle">
                @if ($paginaActual > 1)
                    <a class="btn-link" href="{{ route($routeName, array_merge($periodoQuery, [
                        $searchName => $busquedaActual,
                        $pageName => $paginaActual - 1,
                        $perPageName => $porPaginaActual,
                    ])) . '#dashboard-detalle-listado' }}">Anterior</a>
                @endif
                @if ($paginaActual < $totalPaginasDetalle)
                    <a class="btn-link" href="{{ route($routeName, array_merge($periodoQuery, [
                        $searchName => $busquedaActual,
                        $pageName => $paginaActual + 1,
                        $perPageName => $porPaginaActual,
                    ])) . '#dashboard-detalle-listado' }}">Siguiente</a>
                @endif
            </nav>
        </section>
    </main>
</body>
</html>
