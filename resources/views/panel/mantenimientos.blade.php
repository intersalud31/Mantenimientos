<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $titulo ?? 'Mantenimientos' }}</title>
    <style>
        body {
            margin: 0;
            font-family: Inter, Segoe UI, sans-serif;
            min-height: 100vh;
            color: #e5eefc;
            background: linear-gradient(135deg, #020617, #0f172a 55%, #111827);
        }
        .wrap {
            max-width: 1320px;
            margin: 0 auto;
            padding: 40px 20px;
        }
        .card {
            border: 1px solid rgba(148, 163, 184, .18);
            border-radius: 24px;
            background: rgba(10, 18, 34, .82);
            padding: 28px;
            box-shadow: 0 30px 80px rgba(0, 0, 0, .28);
            margin-bottom: 20px;
        }
        .muted { color: #9fb0ca; }
        .btn, .btn-link {
            display: inline-block;
            padding: 12px 16px;
            border-radius: 12px;
            background: #2563eb;
            color: #fff;
            text-decoration: none;
            border: 0;
            cursor: pointer;
        }
        .btn.secondary {
            background: rgba(37, 99, 235, .15);
            border: 1px solid rgba(37, 99, 235, .35);
        }
        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .topline {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: center;
            flex-wrap: wrap;
        }
        .filters {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: end;
            margin-top: 18px;
        }
        .field {
            min-width: 220px;
            flex: 1;
        }
        label {
            display: block;
            margin-bottom: 6px;
            color: #dbeafe;
            font-weight: 600;
        }
        input, select, textarea {
            width: 100%;
            box-sizing: border-box;
            padding: 12px 14px;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, .18);
            background: rgba(15, 23, 42, .95);
            color: #e5eefc;
        }
        textarea { min-height: 90px; resize: vertical; }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 16px;
        }
        th, td {
            text-align: left;
            padding: 12px 10px;
            border-bottom: 1px solid rgba(148, 163, 184, .12);
            vertical-align: top;
        }
        th { color: #cbd5e1; font-size: .9rem; }
        .notice {
            margin: 18px 0 0;
            padding: 12px 14px;
            border-radius: 14px;
            background: rgba(59, 130, 246, .12);
            border: 1px solid rgba(59, 130, 246, .25);
            color: #bfdbfe;
        }
        .notice.error {
            background: rgba(239, 68, 68, .12);
            border-color: rgba(239, 68, 68, .25);
            color: #fecaca;
        }
        .grid-form {
            display: grid;
            gap: 12px;
        }
        .row-actions {
            display: grid;
            gap: 8px;
            min-width: 260px;
        }
        .pager {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
            margin-top: 16px;
        }
        .mini {
            font-size: .92rem;
        }
    </style>
    @include('panel.partials.dashboard-modern-styles')
    @include('panel.partials.hechos-saas-overrides')
</head>
<body class="dashboard-modern">
<main class="wrap">
    @include('panel.partials.topbar-saas', [
        'activeRoute' => ($modo ?? '') === 'programados' ? 'panel.programados' : 'panel.proximos',
        'pageTitle' => ($modo ?? '') === 'programados' ? 'Programados' : 'Próximos',
        'pageSubtitle' => ($modo ?? '') === 'programados'
            ? 'Mantenimientos ya agendados para ejecución.'
            : 'Seguimiento de mantenimientos pendientes.',
        'periodoConsulta' => $periodoConsulta ?? null,
    ])
    @php
        $esProximos = ($modo ?? '') === 'proximos';
        $colspanVacio = $esProximos ? 6 : 5;
        $periodoConsulta = $periodoConsulta ?? null;
        $periodoQuery = [];
        if (is_array($periodoConsulta)) {
            $periodoQuery = array_filter([
                'anio' => $periodoConsulta['anio'] ?? null,
                'semestre' => $periodoConsulta['semestre'] ?? null,
            ], static fn ($valor) => $valor !== null && $valor !== '');
        }
    @endphp
    <section class="card" id="{{ $esProximos ? 'tabla-proximos' : 'tabla-programados' }}">
        <div class="topline">
            <div>
                <h1>{{ $titulo ?? 'Mantenimientos' }}</h1>
                <p class="muted">{{ $subtitulo ?? '' }}</p>
            </div>
        </div>

        @if (session('panel_message'))
            <div class="notice">{{ session('panel_message') }}</div>
        @endif

        @if (session('panel_error'))
            <div class="notice error">{{ session('panel_error') }}</div>
        @endif

        <form method="GET" action="{{ $modo === 'programados' ? route('panel.programados') : route('panel.proximos') }}">
            <div class="filters">
                <div class="field">
                    <label for="q_{{ $modo }}">Buscar</label>
                    <input id="q_{{ $modo }}" type="text" name="{{ $modo === 'programados' ? 'q_programados' : 'q_proximos' }}" value="{{ $busqueda ?? '' }}" placeholder="{{ $esProximos ? 'Escribe el nombre, código o activo a programar' : 'Nombre o código activo' }}">
                </div>
                <div class="field" style="max-width: 180px;">
                    <label for="por_pagina">Por página</label>
                    <select id="por_pagina" name="{{ $modo === 'programados' ? 'programados_por_pagina' : 'proximos_por_pagina' }}">
                        @foreach ([10, 25, 50] as $opcion)
                            <option value="{{ $opcion }}" {{ (int) ($porPagina ?? 10) === $opcion ? 'selected' : '' }}>{{ $opcion }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn" type="submit">Aplicar</button>
                <a class="btn-link secondary" href="{{ $modo === 'programados' ? route('panel.programados', $periodoQuery) : route('panel.proximos', $periodoQuery) }}">Limpiar filtros</a>
            </div>
            <input type="hidden" name="{{ $modo === 'programados' ? 'programados_pagina' : 'proximos_pagina' }}" value="1">
            @foreach ($periodoQuery as $clave => $valor)
                <input type="hidden" name="{{ $clave }}" value="{{ $valor }}">
            @endforeach
        </form>
    </section>

    <section class="card">
        <div class="topline">
            <div>
                <h2>Listado</h2>
                <p class="muted">Total: {{ $total ?? 0 }} | Página {{ $pagina ?? 1 }} de {{ $totalPaginas ?? 1 }}</p>
            </div>
        </div>

        <table>
            <thead>
            @if ($esProximos)
                <tr>
                    <th>Area / Equipo</th>
                    <th>Usuario</th>
                    <th>Activo</th>
                    <th>Último mantenimiento</th>
                    <th>Próximo mantenimiento</th>
                    <th>Programación</th>
                </tr>
            @else
                <tr>
                    <th>Usuario</th>
                    <th>Codigo</th>
                    <th>Area</th>
                    <th>Fecha</th>
                    <th>Acción</th>
                </tr>
            @endif
            </thead>
            <tbody>
            @forelse ($items as $item)
                <tr>
                    @if ($esProximos)
                        @php
                            $fechaUltima = trim((string) ($item['ultima_fecha_programada'] ?? ''));
                            $fechaProxima = trim((string) ($item['proxima_fecha_programada'] ?? ''));
                            $observaciones = trim((string) ($item['observaciones'] ?? ''));
                            $ultimoTexto = trim((string) ($item['ultimo_mantenimiento_texto'] ?? ''));
                            $estadoAgenda = 'En rango';
                            $claseAgenda = 'estado-tabla-exito';
                            if ($fechaProxima !== '') {
                                try {
                                    $fechaProximaObj = \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $fechaProxima);
                                    $dias = (int) \Illuminate\Support\Carbon::today()->diffInDays($fechaProximaObj, false);
                                    if ($dias < 0) {
                                        $estadoAgenda = 'Vencido';
                                        $claseAgenda = 'estado-tabla-alerta';
                                    } elseif ($dias <= 7) {
                                        $estadoAgenda = 'Próximo';
                                        $claseAgenda = 'estado-tabla-info';
                                    }
                                } catch (\Throwable $e) {
                                    // Se conserva el estado por defecto si la fecha no es interpretable.
                                }
                            }
                        @endphp
                        <td>
                            <strong>{{ $item['area_nombre'] ?? '-' }}</strong>
                            <div class="muted mini">{{ $ultimoTexto !== '' ? $ultimoTexto : 'Sin historial' }}</div>
                            <div class="muted mini">
                                <span class="estado-tabla {{ $item['estado_badge_clase'] ?? 'estado-tabla-info' }}">{{ $item['estado_etiqueta'] ?? 'Pendiente' }}</span>
                            </div>
                        </td>
                        <td>
                            <strong>{{ $item['nombre'] ?? '' }}</strong>
                            <div class="muted mini">ID mantenimiento: {{ $item['mantenimiento_id'] ?? '-' }}</div>
                        </td>
                        <td>{{ $item['codigo_activo'] ?? '-' }}</td>
                        <td>
                            @if ($fechaUltima !== '')
                                <div>{{ \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $fechaUltima)->format('d/m/Y') }}</div>
                            @else
                                <div class="muted mini">Sin historial</div>
                            @endif
                            @if ($observaciones !== '')
                                <div class="muted mini">Obs: {{ $observaciones }}</div>
                            @endif
                        </td>
                        <td>
                            @if ($fechaProxima !== '')
                                <div class="proximo-resumen">
                                    <span class="fecha-destacada">{{ \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $fechaProxima)->format('d/m/Y') }}</span>
                                    <span class="estado-tabla {{ $claseAgenda }}">{{ $estadoAgenda }}</span>
                                </div>
                            @else
                                <div class="muted mini">Sin fecha sugerida</div>
                            @endif
                        </td>
                        <td>
                            <form class="grid-form" method="POST" action="{{ route('panel.proximos.programar') }}">
                                @csrf
                                <input type="hidden" name="mantenimiento_id" value="{{ $item['mantenimiento_id'] }}">
                                <input type="hidden" name="q_proximos" value="{{ $busqueda ?? '' }}">
                                <input type="hidden" name="proximos_pagina" value="{{ $pagina ?? 1 }}">
                                <input type="hidden" name="proximos_por_pagina" value="{{ $porPagina ?? 10 }}">
                                <input type="hidden" name="return_url" value="{{ request()->fullUrl() . '#tabla-proximos' }}">
                                @foreach ($periodoQuery as $clave => $valor)
                                    <input type="hidden" name="{{ $clave }}" value="{{ $valor }}">
                                @endforeach

                                <div class="field">
                                    <label>Fecha programada</label>
                                    <input type="date" name="fecha_programada" required>
                                </div>
                                <button class="btn" type="submit">Programar</button>
                            </form>
                        </td>
                    @else
                        <td>
                            <strong>{{ $item['nombre'] ?? '' }}</strong>
                            <div class="muted mini">ID mantenimiento: {{ $item['mantenimiento_id'] ?? '-' }}</div>
                        </td>
                        <td>{{ $item['codigo_activo'] ?? '-' }}</td>
                        <td>{{ $item['area_nombre'] ?? '-' }}</td>
                        <td>
                            {{ $item['proxima_fecha_programada'] ?? '-' }}
                            <div class="muted mini">Técnico: {{ $item['tecnico_responsable'] ?? '-' }}</div>
                            <div class="muted mini">
                                <span class="estado-tabla {{ $item['estado_badge_clase'] ?? 'estado-tabla-info' }}">{{ $item['estado_etiqueta'] ?? 'Programado' }}</span>
                            </div>
                        </td>
                        <td>
                            <form class="grid-form" method="POST" action="{{ route('panel.programados.hecho') }}" enctype="multipart/form-data">
                                @csrf
                                <input type="hidden" name="mantenimiento_id" value="{{ $item['mantenimiento_id'] }}">
                                <input type="hidden" name="q_programados" value="{{ $busqueda ?? '' }}">
                                <input type="hidden" name="programados_pagina" value="{{ $pagina ?? 1 }}">
                                <input type="hidden" name="programados_por_pagina" value="{{ $porPagina ?? 10 }}">
                                <input type="hidden" name="return_url" value="{{ request()->fullUrl() . '#tabla-programados' }}">
                                @foreach ($periodoQuery as $clave => $valor)
                                    <input type="hidden" name="{{ $clave }}" value="{{ $valor }}">
                                @endforeach

                                <div class="field">
                                    <label>Fecha ejecución</label>
                                    <input type="date" name="fecha_ejecucion" required>
                                </div>
                                <div class="field">
                                    <label>Técnico</label>
                                    <select name="tecnico_id" required>
                                        <option value="">Selecciona...</option>
                                        @foreach (($tecnicos ?? []) as $tecnico)
                                            <option value="{{ $tecnico['id'] }}">{{ $tecnico['nombre'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="field">
                                    <label>Observaciones</label>
                                    <textarea name="observaciones"></textarea>
                                </div>
                                <div class="field">
                                    <label>Constancia PDF</label>
                                    <input type="file" name="constancia_pdf" accept="application/pdf" required>
                                </div>
                                <button class="btn" type="submit">Marcar como hecho</button>
                            </form>
                        </td>
                    @endif
                </tr>
            @empty
                <tr>
                    <td colspan="{{ $colspanVacio }}" class="muted">No hay registros para mostrar.</td>
                </tr>
            @endforelse
            </tbody>
        </table>

        <div class="pager">
            @if (($pagina ?? 1) > 1)
                <a class="btn-link" href="{{ $modo === 'programados'
                    ? route('panel.programados', array_merge($periodoQuery, ['q_programados' => $busqueda ?? '', 'programados_pagina' => ($pagina ?? 1) - 1, 'programados_por_pagina' => $porPagina ?? 10])) . '#tabla-programados'
                    : route('panel.proximos', array_merge($periodoQuery, ['q_proximos' => $busqueda ?? '', 'proximos_pagina' => ($pagina ?? 1) - 1, 'proximos_por_pagina' => $porPagina ?? 10])) . '#tabla-proximos' }}">Anterior</a>
            @endif
            @if (($pagina ?? 1) < ($totalPaginas ?? 1))
                <a class="btn-link" href="{{ $modo === 'programados'
                    ? route('panel.programados', array_merge($periodoQuery, ['q_programados' => $busqueda ?? '', 'programados_pagina' => ($pagina ?? 1) + 1, 'programados_por_pagina' => $porPagina ?? 10])) . '#tabla-programados'
                    : route('panel.proximos', array_merge($periodoQuery, ['q_proximos' => $busqueda ?? '', 'proximos_pagina' => ($pagina ?? 1) + 1, 'proximos_por_pagina' => $porPagina ?? 10])) . '#tabla-proximos' }}">Siguiente</a>
            @endif
        </div>
    </section>
</main>
</body>
</html>

