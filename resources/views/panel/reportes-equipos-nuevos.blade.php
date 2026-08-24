<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Equipos nuevos sin mantenimiento</title>
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
        $paginaActual = (int) ($pagina ?? 1);
        $totalPaginasReporte = (int) ($totalPaginas ?? 1);
        $origenEquipoPresenter = $origenEquipoPresenter ?? app(\App\Services\Legacy\OrigenEquipoPresenter::class);
        $formatearFecha = static function (?string $valor): string {
            $valor = trim((string) $valor);

            if ($valor === '') {
                return '&mdash;';
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
                <h1 class="module-header-title">Equipos nuevos sin mantenimiento</h1>
                <p class="module-header-subtitle">Consulta los equipos nuevos activos cuya pr&oacute;xima fecha a&uacute;n queda fuera del semestre vigente.</p>
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

                <x-panel.live-search
                    action="{{ route('panel.reportes.equipos-nuevos') }}"
                    name="q_reportes_nuevos"
                    label="Buscar equipos"
                    placeholder="Usuario, área o código activo"
                    value="{{ $busquedaActual }}"
                    target="#reportes-equipos-nuevos-listado"
                    debounce="500"
                    loading-label="Buscando..."
                    page-name="reportes_nuevos_pagina"
                    page-value="1"
                >
                    <x-slot:hidden>
                        @foreach ($periodoQuery as $clave => $valor)
                            <input type="hidden" name="{{ $clave }}" value="{{ $valor }}">
                        @endforeach
                        <input type="hidden" name="reportes_nuevos_por_pagina" value="{{ $porPagina ?? 10 }}">
                    </x-slot:hidden>
                </x-panel.live-search>
            </div>
        </section>

        <section class="card panel-card">
            <div class="panel-card-header">
                <div class="panel-title-block">
                    <p class="section-kicker">Listado</p>
                    <h2 class="panel-section-title">Equipos nuevos fuera del semestre vigente</h2>
                    <p class="muted panel-section-subtitle">Total: {{ $total ?? 0 }} | P&aacute;gina {{ (int) ($paginaActual ?? 1) }} de {{ (int) ($totalPaginasReporte ?? 1) }}</p>
                </div>
            </div>

            <div id="reportes-equipos-nuevos-listado">
                <div class="panel-table-shell">
                    <table class="panel-table" aria-label="Equipos nuevos sin mantenimiento">
                        <thead>
                            <tr>
                                <th>Usuario / Equipo</th>
                                <th>C&oacute;digo activo</th>
                                <th>&Aacute;rea</th>
                                <th>Tipo de equipo</th>
                                <th>Fecha base / compra</th>
                                <th>Pr&oacute;ximo mantenimiento</th>
                                <th>Motivo</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($items as $item)
                                @php
                                    $tipoEquipo = (string) ($item['tipo_equipo'] ?? 'nuevo');
                                    $nombreEquipo = trim((string) ($item['nombre'] ?? ''));
                                    $codigoActivo = (string) ($item['codigo_activo'] ?? '-');
                                    $areaNombre = trim((string) ($item['area_nombre'] ?? 'SIN AREA'));
                                    $fechaBase = $formatearFecha($item['fecha_ultimo_mantenimiento_base'] ?? null);
                                    $fechaProxima = $formatearFecha($item['proxima_fecha_mantenimiento'] ?? null);
                                @endphp
                                <tr>
                                    <td>
                                        <span class="texto-principal-tabla">{{ $nombreEquipo !== '' ? $nombreEquipo : 'Sin nombre' }}</span>
                                    </td>
                                    <td>{{ $codigoActivo }}</td>
                                    <td><span class="user-badge-area">{{ $areaNombre !== '' ? $areaNombre : 'SIN AREA' }}</span></td>
                                    <td>
                                        <span class="user-badge-type user-badge-type--{{ $origenEquipoPresenter->badgeVariant($tipoEquipo) }}">
                                            {{ $origenEquipoPresenter->label($tipoEquipo) }}
                                        </span>
                                    </td>
                                    <td>{{ $fechaBase }}</td>
                                    <td>{{ $fechaProxima }}</td>
                                    <td>
                                        <span class="estado-tabla estado-tabla-info">Equipo nuevo, aún no requiere mantenimiento</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="muted panel-table-empty">No hay equipos nuevos sin mantenimiento para mostrar.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <nav role="navigation" class="pager" aria-label="Paginación de equipos nuevos sin mantenimiento">
                @if ((int) ($paginaActual ?? 1) > 1)
                    <a class="btn-link" href="{{ route('panel.reportes.equipos-nuevos', array_merge($periodoQuery, [
                        'q_reportes_nuevos' => $busquedaActual,
                        'reportes_nuevos_pagina' => ((int) ($paginaActual ?? 1)) - 1,
                        'reportes_nuevos_por_pagina' => $porPagina ?? 10,
                    ])) . '#reportes-equipos-nuevos-listado' }}">Anterior</a>
                @endif
                @if ((int) ($paginaActual ?? 1) < (int) ($totalPaginasReporte ?? 1))
                    <a class="btn-link" href="{{ route('panel.reportes.equipos-nuevos', array_merge($periodoQuery, [
                        'q_reportes_nuevos' => $busquedaActual,
                        'reportes_nuevos_pagina' => ((int) ($paginaActual ?? 1)) + 1,
                        'reportes_nuevos_por_pagina' => $porPagina ?? 10,
                    ])) . '#reportes-equipos-nuevos-listado' }}">Siguiente</a>
                @endif
            </nav>
        </section>
    </main>
</body>
</html>
