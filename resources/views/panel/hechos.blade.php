<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $titulo ?? 'Hechos' }}</title>
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
        .mini { font-size: .92rem; }
        .btn, .btn-link {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 16px;
            border-radius: 12px;
            background: #2563eb;
            color: #fff;
            text-decoration: none;
            border: 0;
            cursor: pointer;
        }
        .btn.secondary, .btn-link.secondary {
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
        .pager {
            display: flex;
            gap: 8px;
            align-items: center;
            flex-wrap: wrap;
            margin-top: 16px;
        }
        .badge-area,
        .estado-tabla {
            display: inline-flex;
            align-items: center;
            padding: 4px 10px;
            border-radius: 999px;
            font-size: .78rem;
            letter-spacing: .02em;
            font-weight: 700;
        }
        .badge-area {
            background: rgba(37, 99, 235, .14);
            border: 1px solid rgba(37, 99, 235, .28);
            color: #bfdbfe;
        }
        .estado-tabla-exito {
            background: rgba(16, 185, 129, .14);
            border: 1px solid rgba(16, 185, 129, .25);
            color: #a7f3d0;
        }
        .estado-tabla-info {
            background: rgba(59, 130, 246, .14);
            border: 1px solid rgba(59, 130, 246, .25);
            color: #bfdbfe;
        }
        .estado-tabla-alerta {
            background: rgba(245, 158, 11, .14);
            border: 1px solid rgba(245, 158, 11, .25);
            color: #fde68a;
        }
        .estado-tabla-neutral {
            background: rgba(148, 163, 184, .14);
            border: 1px solid rgba(148, 163, 184, .25);
            color: #e2e8f0;
        }
        .acciones-usuarios {
            position: relative;
        }
        .menu-acciones-usuario {
            position: relative;
            display: inline-block;
        }
        .menu-acciones-disparador {
            list-style: none;
            width: 46px;
            height: 46px;
            border-radius: 14px;
            border: 1px solid rgba(148, 163, 184, .18);
            background: rgba(15, 23, 42, .95);
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            gap: 4px;
        }
        .menu-acciones-disparador span {
            display: block;
            width: 4px;
            height: 4px;
            border-radius: 999px;
            background: #e5eefc;
        }
        .menu-acciones-dropdown {
            position: absolute;
            right: 0;
            top: calc(100% + 8px);
            min-width: 220px;
            display: none;
            flex-direction: column;
            gap: 6px;
            padding: 10px;
            border-radius: 16px;
            background: rgba(2, 6, 23, .98);
            border: 1px solid rgba(148, 163, 184, .18);
            box-shadow: 0 20px 50px rgba(0, 0, 0, .4);
            z-index: 20;
        }
        .menu-acciones-usuario[open] .menu-acciones-dropdown {
            display: flex;
        }
        .menu-acciones-opcion {
            width: 100%;
            text-align: left;
            padding: 10px 12px;
            border-radius: 12px;
            border: 0;
            color: #e5eefc;
            background: rgba(15, 23, 42, .96);
            text-decoration: none;
            cursor: pointer;
        }
        .menu-acciones-opcion:hover {
            background: rgba(37, 99, 235, .22);
        }
        .menu-acciones-opcion-pdf,
        .menu-acciones-opcion-descarga {
            background: rgba(37, 99, 235, .16);
        }
        .estado-vacio {
            display: flex;
            gap: 14px;
            align-items: center;
            padding: 12px 0;
        }
        .estado-vacio strong {
            display: block;
            margin-bottom: 4px;
        }
        .estado-vacio-icono {
            font-size: 1.4rem;
        }
        .modal-panel-estatico {
            position: fixed;
            inset: 0;
            z-index: 50;
            display: grid;
            place-items: center;
            padding: 20px;
            background: rgba(2, 6, 23, .62);
        }
        .modal-panel-backdrop {
            position: absolute;
            inset: 0;
        }
        .modal-panel-dialogo {
            position: relative;
            z-index: 1;
            width: min(100%, 920px);
            max-height: calc(100vh - 40px);
            overflow: auto;
            border-radius: 24px;
            background: rgba(10, 18, 34, .98);
            border: 1px solid rgba(148, 163, 184, .18);
            box-shadow: 0 30px 80px rgba(0, 0, 0, .4);
            padding: 28px;
        }
        .modal-panel-cabecera {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: flex-start;
            margin-bottom: 22px;
        }
        .modal-panel-kicker {
            margin: 0 0 6px;
            text-transform: uppercase;
            letter-spacing: .08em;
            font-size: .78rem;
            color: #93c5fd;
        }
        .modal-panel-texto {
            margin: 8px 0 0;
            color: #9fb0ca;
        }
        .modal-panel-cerrar {
            width: 44px;
            height: 44px;
            border-radius: 999px;
            border: 1px solid rgba(148, 163, 184, .18);
            background: rgba(15, 23, 42, .95);
            color: #e5eefc;
            cursor: pointer;
        }
        .modal-panel-resumen,
        .modal-panel-grid {
            display: grid;
            gap: 14px;
        }
        .modal-panel-resumen {
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            margin-bottom: 18px;
        }
        .modal-panel-resumen-item,
        .detalle-realizado-evidencia-vacio,
        .evidencia-pdf-card-modal {
            border-radius: 18px;
            background: rgba(15, 23, 42, .9);
            border: 1px solid rgba(148, 163, 184, .12);
            padding: 16px;
        }
        .modal-panel-resumen-item span,
        .detalle-realizado-evidencia-label {
            display: block;
            margin-bottom: 8px;
            color: #93c5fd;
            font-size: .85rem;
            text-transform: uppercase;
            letter-spacing: .04em;
        }
        .modal-panel-grid {
            grid-template-columns: repeat(2, minmax(0, 1fr));
        }
        .modal-panel-campo-completo {
            grid-column: 1 / -1;
        }
        .campo-fecha-inline {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .detalle-realizado-evidencia-wrap {
            display: grid;
            gap: 12px;
        }
        .evidencia-pdf-card {
            display: flex;
            gap: 14px;
            align-items: center;
            justify-content: space-between;
            flex-wrap: wrap;
        }
        .evidencia-pdf-copy {
            flex: 1;
            min-width: 220px;
        }
        .evidencia-pdf-acciones {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        .evidencia-pdf-icono {
            font-size: 1.4rem;
        }
        [hidden] {
            display: none !important;
        }
        @media (max-width: 900px) {
            .modal-panel-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
    @include('panel.partials.dashboard-modern-styles')
</head>
<body class="dashboard-modern">
@php
    $itemsRealizados = $items ?? [];
    $colspanVacio = 7;
    $periodoConsulta = $periodoConsulta ?? null;
    $periodoQuery = [];
    if (is_array($periodoConsulta)) {
        $periodoQuery = array_filter([
            'anio' => $periodoConsulta['anio'] ?? null,
            'semestre' => $periodoConsulta['semestre'] ?? null,
        ], static fn ($valor) => $valor !== null && $valor !== '');
    }
    $formatearFecha = static function (?string $fecha): string {
        $fecha = trim((string) $fecha);

        if ($fecha === '') {
            return '-';
        }

        try {
            return \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $fecha)->format('d/m/Y');
        } catch (\Throwable $e) {
            return $fecha;
        }
    };
    $normalizarTexto = static function (?string $valor): string {
        $valor = trim((string) $valor);

        return $valor !== '' ? $valor : 'No registrado';
    };
@endphp
    @include('panel.partials.topbar-saas', [
        'activeRoute' => 'panel.hechos',
        'periodoConsulta' => $periodoConsulta ?? null,
        'brand' => 'Mantenimientos TI',
        'subbrand' => 'Dashboard administrativo',
        'showModuleContext' => false,
    ])
    @include('panel.partials.hechos-saas-overrides')
<main class="wrap">
    <section class="module-header" aria-label="Hechos">
        <div class="module-header-copy">
            <p class="module-header-kicker">Historial</p>
            <h1 class="module-header-title">Hechos</h1>
            <p class="module-header-subtitle">Historial operativo de mantenimientos ejecutados durante el periodo seleccionado.</p>
        </div>
    </section>

    <section class="card" id="tabla-realizados">
        @if (session('panel_message'))
            <div class="notice">{{ session('panel_message') }}</div>
        @endif

        @if (session('panel_error'))
            <div class="notice error">{{ session('panel_error') }}</div>
        @endif

        @include('panel.partials.filter-bar', [
            'formAction' => route('panel.hechos'),
            'queryPresets' => [
                'q_realizados' => $busqueda ?? '',
                'anio' => (int) ($periodoConsulta['anio'] ?? now()->year),
                'semestre' => (int) ($periodoConsulta['semestre'] ?? ((int) now()->format('n') <= 6 ? 1 : 2)),
                'realizados_por_pagina' => (int) ($porPagina ?? 10),
            ],
            'submitLabel' => 'Aplicar filtros',
            'resetLabel' => 'Limpiar',
        ])
    </section>

    <section class="card">
        <div class="page-header-compact" style="margin-bottom: 12px;">
            <div class="page-header-kicker">Resumen del listado</div>
            <div class="page-header-title" style="font-size: 1.1rem;">Lectura rápida del periodo consultado</div>
            <div class="page-header-meta">
                <span class="chip chip--neutral">Total: {{ $total ?? 0 }}</span>
                <span class="chip chip--neutral">Página {{ $pagina ?? 1 }} de {{ $totalPaginas ?? 1 }}</span>
                <span class="chip chip--neutral">Periodo: {{ (int) ($periodoConsulta['anio'] ?? now()->year) }} - Semestre {{ (int) ($periodoConsulta['semestre'] ?? ((int) now()->format('n') <= 6 ? 1 : 2)) }}</span>
            </div>
        </div>

        <div class="tabla-shell tabla-shell-premium">
            <div class="tabla-contenedor">
                <table class="tabla-usuarios tabla-premium">
                    <thead>
                        <tr>
                            <th>Area / Equipo</th>
                            <th>Usuario</th>
                            <th>Activo</th>
                            <th>Fecha programada</th>
                            <th>Fecha ejecución</th>
                            <th>Técnico / Responsable</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse ($itemsRealizados as $item)
                        @php
                            $rutaEvidencia = trim((string) ($item['evidencia_pdf'] ?? ''));
                            $urlEvidencia = route('panel.evidencia.pdf', [
                                'mantenimientoId' => $item['mantenimiento_id'] ?? 0,
                                'download' => 0,
                                'return_url' => request()->fullUrl() . '#tabla-realizados',
                            ]);
                            $urlDescarga = route('panel.evidencia.pdf', [
                                'mantenimientoId' => $item['mantenimiento_id'] ?? 0,
                                'download' => 1,
                                'return_url' => request()->fullUrl() . '#tabla-realizados',
                            ]);
                            $evidenciaDisponible = (bool) ($item['evidencia_disponible'] ?? false);
                            $nombreArchivoPdf = trim((string) ($item['evidencia_nombre'] ?? ''));
                            $observacionesFila = $normalizarTexto($item['observaciones'] ?? '');
                            $tecnicoFila = $normalizarTexto($item['tecnico_responsable'] ?? '');
                            $fechaProgramada = $formatearFecha((string) ($item['proxima_fecha_programada'] ?? ''));
                            $fechaEjecucion = $formatearFecha((string) ($item['fecha_ejecucion'] ?? ''));
                        @endphp
                        <tr>
                            <td data-label="Area / Equipo">
                                <span class="badge-area">{{ $item['area_nombre'] ?? 'SIN AREA' }}</span>
                            </td>
                            <td data-label="Usuario">
                                <strong class="texto-principal-tabla">{{ $item['nombre'] ?? '' }}</strong>
                                <div class="subline">Mantenimiento #{{ $item['mantenimiento_id'] ?? '-' }}</div>
                            </td>
                            <td data-label="Activo">
                                <span class="codigo-activo">{{ $item['codigo_activo'] ?? '-' }}</span>
                            </td>
                            <td data-label="Fecha programada">
                                @if ($fechaProgramada !== '-')
                                    <span class="fecha-destacada">{{ $fechaProgramada }}</span>
                                @else
                                    <span class="muted mini">Sin historial</span>
                                @endif
                            </td>
                            <td data-label="Fecha ejecución">
                                <span class="fecha-destacada">{{ $fechaEjecucion }}</span>
                                <div class="muted mini">
                                    @include('panel.partials.status-badge', [
                                        'state' => 'realizado',
                                        'label' => $item['estado_etiqueta'] ?? 'Realizado',
                                    ])
                                </div>
                            </td>
                            <td data-label="Técnico / Responsable">
                                <span class="status-badge status-badge--programado">{{ $tecnicoFila }}</span>
                            </td>
                            <td data-label="Acciones">
                                <div class="acciones-usuarios acciones-usuarios-menu">
                                    <details class="menu-acciones-usuario">
                                        <summary class="menu-acciones-disparador" aria-label="Abrir acciones del mantenimiento realizado">
                                            <span></span>
                                            <span></span>
                                            <span></span>
                                        </summary>

                                        <div class="menu-acciones-dropdown">
                                            <button
                                                type="button"
                                                class="menu-acciones-opcion menu-acciones-opcion-detalle"
                                                data-open-detalle-realizado
                                                data-detalle-usuario="{{ $item['nombre'] ?? '' }}"
                                                data-detalle-area="{{ $item['area_nombre'] ?? 'SIN AREA' }}"
                                                data-detalle-activo="{{ $item['codigo_activo'] ?? '' }}"
                                                data-detalle-fecha-programada="{{ $fechaProgramada }}"
                                                data-detalle-fecha-ejecucion="{{ $fechaEjecucion }}"
                                                data-detalle-tecnico="{{ $tecnicoFila }}"
                                                data-detalle-observaciones="{{ $observacionesFila !== '' ? $observacionesFila : 'No registrado' }}"
                                                data-detalle-evidencia-url="{{ $urlEvidencia }}"
                                                data-detalle-evidencia-nombre="{{ $nombreArchivoPdf !== '' ? $nombreArchivoPdf : 'Sin constancia adjunta' }}"
                                            >Ver detalle</button>

                                            @if ($evidenciaDisponible && $urlEvidencia !== '')
                                                <a
                                                    class="menu-acciones-opcion menu-acciones-opcion-pdf"
                                                    href="{{ $urlEvidencia }}"
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                >Ver constancia PDF</a>

                                                <a
                                                    class="menu-acciones-opcion menu-acciones-opcion-descarga"
                                                    href="{{ $urlDescarga }}"
                                                    download="{{ $nombreArchivoPdf }}"
                                                >Descargar constancia</a>
                                            @elseif ($rutaEvidencia !== '')
                                                <span class="menu-acciones-opcion estado-tabla estado-tabla-alerta" style="display:block;">Archivo no disponible</span>
                                            @endif
                                        </div>
                                    </details>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $colspanVacio }}">
                                <div class="estado-vacio">
                                    <span class="estado-vacio-icono">✦</span>
                                    <div>
                                        <strong>Aún no hay mantenimientos realizados</strong>
                                        <p class="muted">Los mantenimientos marcados como hechos aparecerán aquí automáticamente para trazabilidad y reporte.</p>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="pager">
            @php
                $paginaActual = (int) ($pagina ?? 1);
                $totalPaginasActual = (int) ($totalPaginas ?? 1);
                $porPaginaActual = (int) ($porPagina ?? 10);
                $baseParams = [
                    'q_realizados' => $busqueda ?? '',
                    'realizados_por_pagina' => $porPaginaActual,
                ];
                foreach ($periodoQuery as $clave => $valor) {
                    $baseParams[$clave] = $valor;
                }
            @endphp
            @if ($paginaActual > 1)
                <a class="btn-link" href="{{ route('panel.hechos', $baseParams + ['realizados_pagina' => $paginaActual - 1]) . '#tabla-realizados' }}">Anterior</a>
            @endif
            <span>Página {{ $paginaActual }} de {{ $totalPaginasActual }}</span>
            @if ($paginaActual < $totalPaginasActual)
                <a class="btn-link" href="{{ route('panel.hechos', $baseParams + ['realizados_pagina' => $paginaActual + 1]) . '#tabla-realizados' }}">Siguiente</a>
            @endif
        </div>
    </section>
</main>

<div class="modal-panel modal-panel-estatico" data-detalle-realizado-modal hidden>
    <div class="modal-panel-backdrop" data-close-detalle-realizado></div>
    <div class="modal-panel-dialogo modal-panel-dialogo-operativo modal-panel-dialogo-detalle" role="dialog" aria-modal="true" aria-labelledby="detalle-realizado-titulo">
        <div class="modal-panel-cabecera">
            <div>
                <p class="modal-panel-kicker">Detalle validado</p>
                <h4 id="detalle-realizado-titulo">Información del mantenimiento realizado</h4>
                <p class="modal-panel-texto">Consulta la ejecución, las observaciones registradas y la constancia PDF del cierre realizado.</p>
            </div>
            <button type="button" class="modal-panel-cerrar" data-close-detalle-realizado aria-label="Cerrar detalle del mantenimiento">×</button>
        </div>

        <div class="modal-panel-resumen">
            <article class="modal-panel-resumen-item">
                <span>Usuario</span>
                <strong data-detalle-modal-usuario>Sin seleccionar</strong>
            </article>
            <article class="modal-panel-resumen-item">
                <span>Area / Equipo</span>
                <strong data-detalle-modal-area>Sin seleccionar</strong>
            </article>
            <article class="modal-panel-resumen-item">
                <span>Activo</span>
                <strong data-detalle-modal-activo>Sin seleccionar</strong>
            </article>
            <article class="modal-panel-resumen-item">
                <span>Técnico</span>
                <strong data-detalle-modal-tecnico>No registrado</strong>
            </article>
        </div>

        <div class="modal-panel-grid">
            <label class="campo-fecha-inline">
                <span>Fecha programada</span>
                <input class="input-fecha-programar" type="text" data-detalle-modal-fecha-programada readonly>
            </label>

            <label class="campo-fecha-inline">
                <span>Fecha ejecución</span>
                <input class="input-fecha-programar" type="text" data-detalle-modal-fecha-ejecucion readonly>
            </label>

            <label class="campo-fecha-inline modal-panel-campo-completo">
                <span>Observaciones</span>
                <textarea rows="5" data-detalle-modal-observaciones readonly></textarea>
            </label>

            <div class="detalle-realizado-evidencia-wrap modal-panel-campo-completo">
                <span class="detalle-realizado-evidencia-label">Constancia PDF</span>

                <div class="evidencia-pdf-card evidencia-pdf-card-modal" data-detalle-evidencia-card hidden>
                    <span class="evidencia-pdf-icono detalle-realizado-evidencia-icono">PDF</span>
                    <div class="evidencia-pdf-copy">
                        <strong data-detalle-modal-evidencia-nombre>Sin constancia adjunta</strong>
                        <span>Documento de validación firmado por el colaborador atendido.</span>
                    </div>
                    <div class="evidencia-pdf-acciones">
                        <a class="btn-link secondary" data-detalle-modal-evidencia-ver href="#" target="_blank" rel="noopener noreferrer">Ver</a>
                        <a class="btn-link" data-detalle-modal-evidencia-descargar href="#">Descargar</a>
                    </div>
                </div>

                <div class="detalle-realizado-evidencia-vacio" data-detalle-evidencia-vacio>
                    <span class="estado-tabla estado-tabla-info">No registrado</span>
                    <p class="muted">No se adjunto constancia PDF en este mantenimiento.</p>
                </div>
            </div>
        </div>

        <div class="actions" style="margin-top: 20px;">
            <button type="button" class="btn" data-close-detalle-realizado>Cerrar</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const modal = document.querySelector('[data-detalle-realizado-modal]');
    if (!modal) {
        return;
    }

    const modalUsuario = modal.querySelector('[data-detalle-modal-usuario]');
    const modalArea = modal.querySelector('[data-detalle-modal-area]');
    const modalActivo = modal.querySelector('[data-detalle-modal-activo]');
    const modalTecnico = modal.querySelector('[data-detalle-modal-tecnico]');
    const modalFechaProgramada = modal.querySelector('[data-detalle-modal-fecha-programada]');
    const modalFechaEjecucion = modal.querySelector('[data-detalle-modal-fecha-ejecucion]');
    const modalObservaciones = modal.querySelector('[data-detalle-modal-observaciones]');
    const modalEvidenciaCard = modal.querySelector('[data-detalle-evidencia-card]');
    const modalEvidenciaVacio = modal.querySelector('[data-detalle-evidencia-vacio]');
    const modalEvidenciaNombre = modal.querySelector('[data-detalle-modal-evidencia-nombre]');
        const modalEvidenciaVer = modal.querySelector('[data-detalle-modal-evidencia-ver]');
        const modalEvidenciaDescargar = modal.querySelector('[data-detalle-modal-evidencia-descargar]');

    const abrirModal = (evento) => {
        const boton = evento.currentTarget;
        const evidenciaUrl = boton.dataset.detalleEvidenciaUrl || '';
        const evidenciaNombre = boton.dataset.detalleEvidenciaNombre || 'Sin constancia adjunta';

        modalUsuario.textContent = boton.dataset.detalleUsuario || 'Sin seleccionar';
        modalArea.textContent = boton.dataset.detalleArea || 'Sin seleccionar';
        modalActivo.textContent = boton.dataset.detalleActivo || 'Sin seleccionar';
        modalTecnico.textContent = boton.dataset.detalleTecnico || 'No registrado';
        modalFechaProgramada.value = boton.dataset.detalleFechaProgramada || '';
        modalFechaEjecucion.value = boton.dataset.detalleFechaEjecucion || '';
        modalObservaciones.value = boton.dataset.detalleObservaciones || 'No registrado';
        modalEvidenciaNombre.textContent = evidenciaNombre;

        if (evidenciaUrl) {
            modalEvidenciaCard.hidden = false;
            modalEvidenciaVacio.hidden = true;
            modalEvidenciaVer.href = evidenciaUrl;
            modalEvidenciaDescargar.href = evidenciaUrl.replace('download=0', 'download=1');
            modalEvidenciaDescargar.setAttribute('download', evidenciaNombre);
        } else {
            modalEvidenciaCard.hidden = true;
            modalEvidenciaVacio.hidden = false;
            modalEvidenciaVer.href = '#';
            modalEvidenciaDescargar.href = '#';
            modalEvidenciaDescargar.removeAttribute('download');
        }

        modal.hidden = false;
    };

    const cerrarModal = () => {
        modal.hidden = true;
    };

    document.querySelectorAll('[data-open-detalle-realizado]').forEach((boton) => {
        boton.addEventListener('click', abrirModal);
    });

    modal.querySelectorAll('[data-close-detalle-realizado]').forEach((boton) => {
        boton.addEventListener('click', cerrarModal);
    });

    document.addEventListener('keydown', (evento) => {
        if (evento.key === 'Escape' && !modal.hidden) {
            cerrarModal();
        }
    });
});
</script>
</body>
</html>
