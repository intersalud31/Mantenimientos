<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Equipos no exigibles</title>
    @include('panel.partials.dashboard-modern-styles')
    @include('panel.partials.hechos-saas-overrides')
</head>
<body class="dashboard-modern">
    @php
        $currentRoute = 'panel.reportes';
    @endphp
    @include('panel.partials.topbar-saas', [
        'activeRoute' => $currentRoute,
        'periodoConsulta' => $periodoConsulta ?? null,
        'brand' => 'Mantenimientos TI',
        'subbrand' => 'Dashboard administrativo',
        'showSearch' => false,
        'showModuleContext' => false,
    ])

    @php
        $periodoQuery = array_filter([
            'anio' => $periodoConsulta['anio'] ?? request('anio'),
            'semestre' => $periodoConsulta['semestre'] ?? request('semestre'),
        ], static fn ($valor) => $valor !== null && $valor !== '');

        $busquedaActual = $busqueda ?? '';
        $motivoActual = trim((string) ($motivoNoExigible ?? ''));
        $paginaActual = (int) ($pagina ?? 1);
        $totalPaginasReporte = (int) ($totalPaginas ?? 1);
        $motivoOptions = is_array($motivoOptions ?? null) ? $motivoOptions : [];
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
        <section class="module-header" aria-label="Reportes">
            <div class="module-header-copy">
                <p class="module-header-kicker">Reportes</p>
                <h1 class="module-header-title">Equipos no exigibles</h1>
                <p class="module-header-subtitle">Consulta los equipos activos cuya siguiente exigencia de mantenimiento queda fuera del semestre vigente.</p>
            </div>
        </section>

        <section class="card panel-card">
            <div class="users-module-shell users-module-shell--head">
                <div class="users-toolbar users-toolbar--integrated">
                    <div>
                        <p class="section-kicker">Gestión</p>
                        <h2 class="panel-section-title">Buscar equipos</h2>
                        <p class="muted panel-section-subtitle">Busca por usuario, área o código de activo.</p>
                    </div>
                </div>

                <x-panel.filter-bar
                    action="{{ route('panel.reportes.equipos-nuevos') }}"
                    title="Gestión"
                    subtitle="Busca por usuario, área o código de activo y filtra por motivo."
                    live="true"
                    live-target="#reportes-equipos-nuevos-listado"
                    submit-label="Aplicar filtros"
                >
                    <x-slot:fields>
                        <label class="panel-filter-field" for="q_reportes_nuevos">
                            <span>Buscar equipos</span>
                            <input
                                id="q_reportes_nuevos"
                                type="search"
                                name="q_reportes_nuevos"
                                value="{{ $busquedaActual }}"
                                placeholder="Usuario, área o código activo"
                                autocomplete="off"
                                data-panel-filter-search
                            >
                        </label>

                        <label class="panel-filter-field panel-filter-field--narrow" for="motivo_no_exigible">
                            <span>Motivo</span>
                            <select id="motivo_no_exigible" name="motivo_no_exigible" data-panel-filter-live-field>
                                @foreach ($motivoOptions as $option)
                                    <option value="{{ $option['value'] }}" {{ $motivoActual === (string) $option['value'] ? 'selected' : '' }}>
                                        {{ $option['label'] }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                    </x-slot:fields>

                    <x-slot:hidden>
                        @foreach ($periodoQuery as $clave => $valor)
                            <input type="hidden" name="{{ $clave }}" value="{{ $valor }}">
                        @endforeach
                        <input type="hidden" name="reportes_nuevos_pagina" value="1">
                        <input type="hidden" name="reportes_nuevos_por_pagina" value="{{ $porPagina ?? 10 }}">
                    </x-slot:hidden>
                </x-panel.filter-bar>
            </div>
        </section>

        <section class="card panel-card">
            <div class="panel-card-header">
                <div class="panel-title-block">
                    <p class="section-kicker">Listado</p>
                    <h2 class="panel-section-title">Equipos no exigibles del semestre vigente</h2>
                    <p class="muted panel-section-subtitle">Total: {{ $total ?? 0 }} | Página {{ (int) ($paginaActual ?? 1) }} de {{ (int) ($totalPaginasReporte ?? 1) }}</p>
                </div>
            </div>

            <div id="reportes-equipos-nuevos-listado">
                <div class="panel-table-shell">
                    <table class="panel-table" aria-label="Equipos no exigibles">
                        <thead>
                            <tr>
                                <th>Usuario / Equipo</th>
                                <th>Código activo</th>
                                <th>Área</th>
                                <th>Motivo</th>
                                <th>Fecha base / compra</th>
                                <th>Próximo mantenimiento</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($items as $item)
                                @php
                                    $nombreEquipo = trim((string) ($item['nombre'] ?? ''));
                                    $motivoCodigo = trim((string) ($item['motivo_codigo'] ?? ''));
                                    $motivoLabel = trim((string) ($item['motivo_label'] ?? 'No exigible'));
                                    $codigoActivo = (string) ($item['codigo_activo'] ?? '-');
                                    $areaNombre = trim((string) ($item['area_nombre'] ?? 'SIN AREA'));
                                    $fechaBase = $formatearFecha($item['fecha_ultimo_mantenimiento_base'] ?? null);
                                    $fechaProxima = $formatearFecha($item['proxima_fecha_mantenimiento'] ?? null);
                                    $motivoClase = $motivoCodigo === \App\Services\Legacy\NoExigibleReasonService::EQUIPO_NUEVO
                                        ? 'estado-tabla-info'
                                        : 'estado-tabla-neutral';
                                @endphp
                                <tr>
                                    <td>
                                        <span class="texto-principal-tabla">{{ $nombreEquipo !== '' ? $nombreEquipo : 'Sin nombre' }}</span>
                                    </td>
                                    <td>{{ $codigoActivo }}</td>
                                    <td><span class="user-badge-area">{{ $areaNombre !== '' ? $areaNombre : 'SIN AREA' }}</span></td>
                                    <td>
                                        <span class="estado-tabla {{ $motivoClase }}">
                                            {{ $motivoLabel }}
                                        </span>
                                    </td>
                                    <td>{{ $fechaBase }}</td>
                                    <td>{{ $fechaProxima }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="muted panel-table-empty">No hay equipos no exigibles para mostrar en este periodo.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <nav role="navigation" class="pager" aria-label="Paginación de equipos no exigibles">
                @if ((int) ($paginaActual ?? 1) > 1)
                    <a class="btn-link" href="{{ route('panel.reportes.equipos-nuevos', array_merge($periodoQuery, [
                        'q_reportes_nuevos' => $busquedaActual,
                        'motivo_no_exigible' => $motivoActual,
                        'reportes_nuevos_pagina' => ((int) ($paginaActual ?? 1)) - 1,
                        'reportes_nuevos_por_pagina' => $porPagina ?? 10,
                    ])) . '#reportes-equipos-nuevos-listado' }}">Anterior</a>
                @endif
                @if ((int) ($paginaActual ?? 1) < (int) ($totalPaginasReporte ?? 1))
                    <a class="btn-link" href="{{ route('panel.reportes.equipos-nuevos', array_merge($periodoQuery, [
                        'q_reportes_nuevos' => $busquedaActual,
                        'motivo_no_exigible' => $motivoActual,
                        'reportes_nuevos_pagina' => ((int) ($paginaActual ?? 1)) + 1,
                        'reportes_nuevos_por_pagina' => $porPagina ?? 10,
                    ])) . '#reportes-equipos-nuevos-listado' }}">Siguiente</a>
                @endif
            </nav>
        </section>
    </main>
</body>
</html>
