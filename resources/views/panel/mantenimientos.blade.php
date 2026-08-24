<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $titulo ?? 'Mantenimientos' }}</title>
    @include('panel.partials.dashboard-modern-styles')
    @include('panel.partials.hechos-saas-overrides')
</head>
<body class="dashboard-modern">
    @include('panel.partials.topbar-saas', [
        'activeRoute' => ($modo ?? '') === 'programados' ? 'panel.programados' : 'panel.proximos',
        'pageTitle' => ($modo ?? '') === 'programados' ? 'Programados' : 'Próximos',
        'pageSubtitle' => ($modo ?? '') === 'programados'
            ? 'Mantenimientos ya agendados para ejecución.'
            : 'Seguimiento de mantenimientos pendientes.',
        'periodoConsulta' => $periodoConsulta ?? null,
        'showModuleContext' => false,
    ])
<main class="wrap">
    @php
        $esProximos = ($modo ?? '') === 'proximos';
        $colspanVacio = $esProximos ? 6 : 5;
        $listadoId = $esProximos ? 'proximos-listado' : 'tabla-programados';
        $periodoConsulta = $periodoConsulta ?? null;
        $periodoQuery = [];
        if (is_array($periodoConsulta)) {
            $periodoQuery = array_filter([
                'anio' => $periodoConsulta['anio'] ?? null,
                'semestre' => $periodoConsulta['semestre'] ?? null,
            ], static fn ($valor) => $valor !== null && $valor !== '');
        }
        $fechaMinimaProgramacion = \Illuminate\Support\Carbon::today()->toDateString();
    @endphp
    <section class="module-header" aria-label="{{ $titulo ?? 'Mantenimientos' }}">
        <div class="module-header-copy">
            <p class="module-header-kicker">Operación</p>
            <h1 class="module-header-title">{{ $titulo ?? 'Mantenimientos' }}</h1>
            <p class="module-header-subtitle">{{ $subtitulo ?? '' }}</p>
        </div>
    </section>

    <section class="card panel-card">
        <div class="users-module-shell users-module-shell--head">
            <div class="users-toolbar users-toolbar--integrated">
                <div>
                    <p class="section-kicker">Gestión</p>
                    <h2 class="panel-section-title">Buscar mantenimientos</h2>
                    <p class="muted panel-section-subtitle">Busca por usuario, área o código de activo.</p>
                </div>
            </div>

            <x-panel.live-search
                action="{{ $esProximos ? route('panel.proximos') : route('panel.programados') }}"
                name="{{ $esProximos ? 'q_proximos' : 'q_programados' }}"
                label="Buscar mantenimientos"
                placeholder="Usuario, área o código de activo"
                value="{{ $busqueda ?? '' }}"
                target="#{{ $listadoId }}"
                debounce="500"
                loading-label="Buscando..."
                page-name="{{ $esProximos ? 'proximos_pagina' : 'programados_pagina' }}"
                page-value="1"
            >
                <x-slot:hidden>
                    @foreach ($periodoQuery as $clave => $valor)
                        <input type="hidden" name="{{ $clave }}" value="{{ $valor }}">
                    @endforeach
                    <input type="hidden" name="{{ $esProximos ? 'proximos_por_pagina' : 'programados_por_pagina' }}" value="{{ $porPagina ?? ($esProximos ? 10 : 10) }}">
                </x-slot:hidden>
            </x-panel.live-search>
        </div>

        @if (session('panel_message'))
            <div class="notice">{{ session('panel_message') }}</div>
        @endif

        @if (session('panel_error'))
            <div class="notice error">{{ session('panel_error') }}</div>
        @endif
    </section>

    <section class="card panel-card">
        <div id="{{ $listadoId }}">
        <div class="panel-card-header">
            <div class="panel-title-block">
                <p class="section-kicker">Listado</p>
                <h2 class="panel-section-title">{{ $esProximos ? 'Mantenimientos por programar' : 'Mantenimientos programados' }}</h2>
                <p class="muted panel-section-subtitle">Total: {{ $total ?? 0 }} | Página {{ $pagina ?? 1 }} de {{ $totalPaginas ?? 1 }}</p>
            </div>
        </div>

        <div class="panel-table-shell">
        <table class="panel-table">
            <thead>
            @if ($esProximos)
                <tr>
                    <th>NOMBRE</th>
                    <th>ÁREA / EQUIPO</th>
                    <th>ACTIVO</th>
                    <th>ÚLTIMO MANTENIMIENTO</th>
                    <th>PRÓXIMO MANTENIMIENTO</th>
                    <th>PROGRAMACIÓN</th>
                </tr>
            @else
                <tr>
                    <th>Usuario</th>
                    <th>Código</th>
                    <th>Área</th>
                    <th class="tabla-programados-columna-fecha">Fecha de programación</th>
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
                        @endphp
                        <td>
                            <span class="texto-principal-tabla">{{ $item['nombre'] ?? '' }}</span>
                        </td>
                        <td>
                            <span class="texto-principal-tabla">{{ $item['area_nombre'] ?? '-' }}</span>
                        </td>
                        <td class="texto-normal-tabla">{{ $item['codigo_activo'] ?? '-' }}</td>
                        <td>
                            @if ($fechaUltima !== '')
                                <div>{{ \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $fechaUltima)->format('d/m/Y') }}</div>
                            @else
                                <div>—</div>
                            @endif
                        </td>
                        <td>
                            @if ($fechaProxima !== '')
                                <div class="texto-normal-tabla">{{ \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $fechaProxima)->format('d/m/Y') }}</div>
                            @else
                                <div>—</div>
                            @endif
                        </td>
                        <td>
                            <div class="maintenance-action-summary">
                                <button
                                    type="button"
                                    class="btn"
                                    data-open-maintenance-modal
                                    data-maintenance-target="programar"
                                    data-maintenance-id="{{ $item['mantenimiento_id'] }}"
                                    data-maintenance-summary="#{{ $item['mantenimiento_id'] }} - {{ $item['nombre'] ?? '' }}"
                                    data-maintenance-suggested-date="{{ $fechaProxima }}"
                                    data-maintenance-programmed-date="{{ $fechaProxima }}"
                                >Programar</button>
                            </div>
                        </td>
                    @else
                        <td>
                            <span class="tabla-programados-nombre">{{ $item['nombre'] ?? '' }}</span>
                        </td>
                        <td>{{ $item['codigo_activo'] ?? '-' }}</td>
                        <td>{{ $item['area_nombre'] ?? '-' }}</td>
                        <td class="tabla-programados-columna-fecha">{{ $item['proxima_fecha_programada'] ?? '-' }}</td>
                        <td>
                            <div class="maintenance-action-summary maintenance-action-summary--programados">
                                <span class="estado-tabla {{ $item['estado_vigente_badge_clase'] ?? 'estado-tabla-info' }}">
                                    {{ $item['estado_vigente_etiqueta'] ?? 'Programado' }}
                                </span>
                                <button
                                    type="button"
                                    class="btn"
                                    data-open-maintenance-modal
                                    data-maintenance-target="hecho"
                                    data-maintenance-id="{{ $item['mantenimiento_id'] }}"
                                    data-maintenance-summary="#{{ $item['mantenimiento_id'] }} - {{ $item['nombre'] ?? '' }}"
                                    data-maintenance-programmed-date="{{ $item['proxima_fecha_programada'] ?? '' }}"
                                >Marcar como hecho</button>
                            </div>
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
        </div>

        @if ($esProximos)
            <div class="modal-panel modal-panel-estatico" data-maintenance-modal hidden>
                <div class="modal-panel-backdrop" data-maintenance-modal-close></div>
                <div class="modal-panel-dialogo modal-panel-dialogo-operativo" role="dialog" aria-modal="true" aria-labelledby="programar-mantenimiento-titulo">
                    <div class="modal-panel-cabecera">
                        <div>
                            <h4 id="programar-mantenimiento-titulo">Programar mantenimiento</h4>
                        </div>
                        <button type="button" class="modal-panel-cerrar" data-maintenance-modal-close aria-label="Cerrar programación">Cerrar</button>
                    </div>

                    <form method="POST" action="{{ route('panel.proximos.programar') }}" class="maintenance-modal-form">
                        @csrf
                        <input type="hidden" name="mantenimiento_id" data-maintenance-id-input>
                        <input type="hidden" name="q_proximos" value="{{ $busqueda ?? '' }}">
                        <input type="hidden" name="proximos_pagina" value="{{ $pagina ?? 1 }}">
                        <input type="hidden" name="proximos_por_pagina" value="{{ $porPagina ?? 10 }}">
                        <input type="hidden" name="return_url" value="{{ request()->fullUrl() . '#proximos-listado' }}">
                        @foreach ($periodoQuery as $clave => $valor)
                            <input type="hidden" name="{{ $clave }}" value="{{ $valor }}">
                        @endforeach

                        <div class="maintenance-modal-grid">
                            @php
                                $fechaProgramadaInicial = old('fecha_programada', $fechaMinimaProgramacion);
                                $fechaProgramadaEtiqueta = \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $fechaProgramadaInicial)->format('d/m/Y');
                            @endphp
                            <div class="field maintenance-date-field maintenance-modal-full" data-maintenance-date-field>
                                <label id="fecha_programada_proximos_label">Fecha programada</label>
                                <input
                                    type="hidden"
                                    id="fecha_programada_proximos"
                                    name="fecha_programada"
                                    value="{{ $fechaProgramadaInicial }}"
                                    data-maintenance-date-input
                                    data-maintenance-date-min="{{ \Illuminate\Support\Carbon::today()->toDateString() }}"
                                    data-maintenance-date-holidays='@json($festivosCalendario ?? [])'
                                    data-maintenance-date-preserve="{{ old('fecha_programada') !== null ? '1' : '0' }}"
                                >
                                <button
                                    type="button"
                                    class="maintenance-date-trigger"
                                    data-maintenance-date-trigger
                                    aria-haspopup="dialog"
                                    aria-expanded="false"
                                    aria-controls="maintenance-date-picker"
                                    aria-labelledby="fecha_programada_proximos_label"
                                >
                                    <span class="maintenance-date-trigger-text" data-maintenance-date-display>{{ $fechaProgramadaEtiqueta }}</span>
                                    <span class="maintenance-date-trigger-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
                                            <path d="M7 3v3M17 3v3M4 8h16M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
                                        </svg>
                                    </span>
                                </button>

                                <div class="maintenance-date-picker" id="maintenance-date-picker" data-maintenance-date-picker hidden>
                                    <div class="maintenance-date-picker-header">
                                        <button type="button" class="maintenance-date-picker-nav" data-maintenance-date-prev aria-label="Mes anterior">&lsaquo;</button>
                                        <div class="maintenance-date-picker-title-block">
                                            <div class="maintenance-date-picker-title" data-maintenance-date-month></div>
                                            <label class="maintenance-date-picker-year-label" for="maintenance-date-year">Año</label>
                                            <select id="maintenance-date-year" class="maintenance-date-picker-year" data-maintenance-date-year></select>
                                        </div>
                                        <button type="button" class="maintenance-date-picker-nav" data-maintenance-date-next aria-label="Mes siguiente">&rsaquo;</button>
                                    </div>

                                    <div class="maintenance-date-picker-weekdays" aria-hidden="true">
                                        <span>L</span>
                                        <span>M</span>
                                        <span>X</span>
                                        <span>J</span>
                                        <span>V</span>
                                        <span>S</span>
                                        <span>D</span>
                                    </div>

                                    <div class="maintenance-date-picker-grid" data-maintenance-date-grid></div>

                                    <div class="maintenance-date-picker-footer">
                                        <button type="button" class="btn btn-ghost maintenance-date-picker-today" data-maintenance-date-today>Hoy</button>
                                        <button type="button" class="btn btn-ghost maintenance-date-picker-close" data-maintenance-date-close>Listo</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="maintenance-modal-actions">
                            <button class="btn" type="submit">Programar</button>
                        </div>
                    </form>
                </div>
            </div>
        @else
            <div class="modal-panel modal-panel-estatico" data-maintenance-modal hidden>
                <div class="modal-panel-backdrop" data-maintenance-modal-close></div>
                <div class="modal-panel-dialogo modal-panel-dialogo-operativo" role="dialog" aria-modal="true" aria-labelledby="marcar-hecho-titulo">
                    <div class="modal-panel-cabecera">
                        <div>
                            <h4 id="marcar-hecho-titulo">Marcar como hecho</h4>
                        </div>
                        <button type="button" class="modal-panel-cerrar" data-maintenance-modal-close aria-label="Cerrar ejecución">Cerrar</button>
                    </div>

                    <form method="POST" action="{{ route('panel.programados.hecho') }}" enctype="multipart/form-data" class="maintenance-modal-form">
                        @csrf
                        <input type="hidden" name="mantenimiento_id" data-maintenance-id-input>
                        <input type="hidden" name="q_programados" value="{{ $busqueda ?? '' }}">
                        <input type="hidden" name="programados_pagina" value="{{ $pagina ?? 1 }}">
                        <input type="hidden" name="programados_por_pagina" value="{{ $porPagina ?? 10 }}">
                        <input type="hidden" name="return_url" value="{{ request()->fullUrl() . '#tabla-programados' }}">
                        @foreach ($periodoQuery as $clave => $valor)
                            <input type="hidden" name="{{ $clave }}" value="{{ $valor }}">
                        @endforeach

                        <div class="maintenance-modal-grid">
                            @php
                                $fechaEjecucionInicial = old('fecha_ejecucion', \Illuminate\Support\Carbon::today()->toDateString());
                                $fechaEjecucionEtiqueta = \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $fechaEjecucionInicial)->format('d/m/Y');
                            @endphp
                            <div class="field maintenance-date-field" data-maintenance-date-field>
                                <label id="fecha_ejecucion_programado_label">Fecha de ejecución</label>
                                <input
                                    type="hidden"
                                    id="fecha_ejecucion_programado"
                                    name="fecha_ejecucion"
                                    value="{{ $fechaEjecucionInicial }}"
                                    data-maintenance-date-input
                                    data-maintenance-date-max="{{ \Illuminate\Support\Carbon::today()->toDateString() }}"
                                    data-maintenance-date-allow-non-business="1"
                                    data-maintenance-date-holidays='@json($festivosCalendario ?? [])'
                                >
                                <button
                                    type="button"
                                    class="maintenance-date-trigger"
                                    data-maintenance-date-trigger
                                    aria-haspopup="dialog"
                                    aria-expanded="false"
                                    aria-controls="maintenance-date-picker"
                                    aria-labelledby="fecha_ejecucion_programado_label"
                                >
                                    <span class="maintenance-date-trigger-text" data-maintenance-date-display>{{ $fechaEjecucionEtiqueta }}</span>
                                    <span class="maintenance-date-trigger-icon" aria-hidden="true">
                                        <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
                                            <path d="M7 3v3M17 3v3M4 8h16M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
                                        </svg>
                                    </span>
                                </button>

                                <div class="maintenance-date-picker" id="maintenance-date-picker" data-maintenance-date-picker hidden>
                                    <div class="maintenance-date-picker-header">
                                        <button type="button" class="maintenance-date-picker-nav" data-maintenance-date-prev aria-label="Mes anterior">&lsaquo;</button>
                                        <div class="maintenance-date-picker-title-block">
                                            <div class="maintenance-date-picker-title" data-maintenance-date-month></div>
                                            <label class="maintenance-date-picker-year-label" for="maintenance-date-year">Año</label>
                                            <select id="maintenance-date-year" class="maintenance-date-picker-year" data-maintenance-date-year></select>
                                        </div>
                                        <button type="button" class="maintenance-date-picker-nav" data-maintenance-date-next aria-label="Mes siguiente">&rsaquo;</button>
                                    </div>

                                    <div class="maintenance-date-picker-weekdays" aria-hidden="true">
                                        <span>L</span>
                                        <span>M</span>
                                        <span>X</span>
                                        <span>J</span>
                                        <span>V</span>
                                        <span>S</span>
                                        <span>D</span>
                                    </div>

                                    <div class="maintenance-date-picker-grid" data-maintenance-date-grid></div>

                                    <div class="maintenance-date-picker-footer">
                                        <button type="button" class="btn btn-ghost maintenance-date-picker-today" data-maintenance-date-today>Hoy</button>
                                        <button type="button" class="btn btn-ghost maintenance-date-picker-close" data-maintenance-date-close>Listo</button>
                                    </div>
                                </div>
                                <p class="field-help">Puedes registrar la fecha real en la que se realizó el mantenimiento.</p>
                            </div>
                            <div class="field">
                                <label>Fecha programada</label>
                                <input type="text" class="input-fecha-programar tabla-programados-columna-fecha" data-maintenance-programmed-date-display readonly>
                            </div>
                            <div class="field">
                                <label for="tecnico_id_programado">Realizado por</label>
                                <select id="tecnico_id_programado" class="maintenance-modal-select" name="tecnico_id" required>
                                    <option value="">Selecciona una persona</option>
                                    @foreach (($tecnicos ?? []) as $tecnico)
                                        <option value="{{ $tecnico['id'] }}">{{ $tecnico['nombre'] }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="field maintenance-modal-full">
                                <label>Observaciones</label>
                                <textarea name="observaciones" rows="4"></textarea>
                            </div>
                            <div class="field maintenance-modal-full">
                                <label>Constancia PDF</label>
                                <div class="maintenance-upload" data-maintenance-file-dropzone>
                                    <div class="maintenance-upload-stage">
                                        <input
                                            type="file"
                                            id="constancia_pdf_programado"
                                            name="constancia_pdf"
                                            class="maintenance-upload-input"
                                            accept="application/pdf"
                                            required
                                            data-maintenance-file-input
                                        >
                                        <label class="maintenance-upload-card" for="constancia_pdf_programado">
                                            <span class="maintenance-upload-icon" aria-hidden="true">
                                                <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
                                                    <path d="M6 2h8l4 4v16H6z" fill="currentColor" opacity=".16"></path>
                                                    <path d="M14 2v6h6" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="1.8"></path>
                                                    <path d="M8 12h8M8 16h8M8 8h3" fill="none" stroke="currentColor" stroke-linecap="round" stroke-width="1.8"></path>
                                                </svg>
                                            </span>
                                            <span class="maintenance-upload-copy">
                                                <strong>Seleccionar constancia en PDF</strong>
                                                <span>Haz clic o arrastra el archivo aquí</span>
                                                <small>Solo PDF.</small>
                                            </span>
                                        </label>
                                    </div>
                                    <div class="maintenance-upload-footer">
                                        <span class="maintenance-upload-name" data-maintenance-file-name>Sin archivo seleccionado</span>
                                        <button type="button" class="btn btn-ghost maintenance-upload-clear" data-maintenance-file-clear>Quitar</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="maintenance-modal-actions">
                            <button class="btn" type="submit">Marcar como hecho</button>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        <nav role="navigation" class="pager" aria-label="Paginación de mantenimientos {{ $esProximos ? 'próximos' : 'programados' }}">
            @if (($pagina ?? 1) > 1)
                <a class="btn-link" href="{{ $modo === 'programados'
                    ? route('panel.programados', array_merge($periodoQuery, ['q_programados' => $busqueda ?? '', 'programados_pagina' => ($pagina ?? 1) - 1, 'programados_por_pagina' => $porPagina ?? 10])) . '#'.$listadoId
                    : route('panel.proximos', array_merge($periodoQuery, ['q_proximos' => $busqueda ?? '', 'proximos_pagina' => ($pagina ?? 1) - 1, 'proximos_por_pagina' => $porPagina ?? 10])) . '#proximos-listado' }}">Anterior</a>
            @endif
            @if (($pagina ?? 1) < ($totalPaginas ?? 1))
                <a class="btn-link" href="{{ $modo === 'programados'
                    ? route('panel.programados', array_merge($periodoQuery, ['q_programados' => $busqueda ?? '', 'programados_pagina' => ($pagina ?? 1) + 1, 'programados_por_pagina' => $porPagina ?? 10])) . '#'.$listadoId
                    : route('panel.proximos', array_merge($periodoQuery, ['q_proximos' => $busqueda ?? '', 'proximos_pagina' => ($pagina ?? 1) + 1, 'proximos_por_pagina' => $porPagina ?? 10])) . '#proximos-listado' }}">Siguiente</a>
            @endif
        </nav>
        </div>
    </section>
</main>
</body>
</html>



