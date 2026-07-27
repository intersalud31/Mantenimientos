<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $titulo ?? 'Hechos' }}</title>
    @include('panel.partials.dashboard-modern-styles')
    @include('panel.partials.hechos-saas-overrides')
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
<main class="wrap">
    <section class="module-header" aria-label="Hechos">
        <div class="module-header-copy">
            <p class="module-header-kicker">Historial</p>
            <h1 class="module-header-title">Hechos</h1>
            <p class="module-header-subtitle">Historial operativo de mantenimientos ejecutados durante el periodo seleccionado.</p>
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
                action="{{ route('panel.hechos') }}"
                name="q_realizados"
                label="Buscar mantenimientos"
                placeholder="Usuario, área o código de activo"
                value="{{ $busqueda ?? '' }}"
                target="#tabla-realizados"
                debounce="500"
                loading-label="Buscando..."
                page-name="realizados_pagina"
                page-value="1"
            >
                <x-slot:hidden>
                    @foreach ($periodoQuery as $clave => $valor)
                        <input type="hidden" name="{{ $clave }}" value="{{ $valor }}">
                    @endforeach
                    <input type="hidden" name="realizados_por_pagina" value="{{ $porPagina ?? 10 }}">
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
        <div id="tabla-realizados">
            <div class="panel-card-header">
                <div class="panel-title-block">
                    <p class="section-kicker">Listado</p>
                    <h2 class="panel-section-title">Mantenimientos realizados</h2>
                    <p class="muted panel-section-subtitle">Total: {{ $total ?? 0 }} | Página {{ $pagina ?? 1 }} de {{ $totalPaginas ?? 1 }}</p>
                </div>
            </div>

            <div class="panel-table-shell">
                <table class="panel-table">
                    <thead>
                        <tr>
                            <th>ÁREA / EQUIPO</th>
                            <th>USUARIO</th>
                            <th>ACTIVO</th>
                            <th>FECHA PROGRAMADA</th>
                            <th>FECHA DE EJECUCIÓN</th>
                            <th>TÉCNICO / RESPONSABLE</th>
                            <th>ACCIONES</th>
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
                            <td data-label="ÁREA / EQUIPO">
                                <span class="texto-principal-tabla">{{ $item['area_nombre'] ?? 'SIN ÁREA' }}</span>
                            </td>
                            <td data-label="USUARIO">
                                <span class="texto-principal-tabla">{{ $item['nombre'] ?? '' }}</span>
                            </td>
                            <td data-label="ACTIVO">
                                <span class="codigo-activo">{{ $item['codigo_activo'] ?? '-' }}</span>
                            </td>
                            <td data-label="FECHA PROGRAMADA">
                                @if ($fechaProgramada !== '-')
                                    <div>{{ $fechaProgramada }}</div>
                                @else
                                    <div>—</div>
                                @endif
                            </td>
                            <td data-label="FECHA DE EJECUCIÓN">
                                <div>{{ $fechaEjecucion }}</div>
                                <div class="muted mini">
                                    @include('panel.partials.status-badge', [
                                        'state' => 'realizado',
                                        'label' => $item['estado_etiqueta'] ?? 'Hecho',
                                    ])
                                </div>
                            </td>
                            <td data-label="TÉCNICO / RESPONSABLE">
                                <span class="texto-principal-tabla">{{ $tecnicoFila }}</span>
                            </td>
                            <td data-label="ACCIONES">
                                <div class="maintenance-action-summary">
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
                                                data-detalle-area="{{ $item['area_nombre'] ?? 'SIN ÁREA' }}"
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
                                                <span class="menu-acciones-opcion estado-tabla estado-tabla-alerta panel-display-block">Archivo no disponible</span>
                                            @endif
                                        </div>
                                    </details>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $colspanVacio }}" class="panel-table-empty">
                                <div class="estado-vacio">
                                    <span class="estado-vacio-icono">⋆</span>
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

        <nav role="navigation" class="pager" aria-label="Paginación de mantenimientos realizados">
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
        </nav>
    </section>
    <div class="modal-panel modal-panel-estatico" data-detalle-realizado-modal hidden>
        <div class="modal-panel-backdrop" data-close-detalle-realizado></div>
        <div class="modal-panel-dialogo modal-panel-dialogo-operativo modal-panel-dialogo-detalle" role="dialog" aria-modal="true" aria-labelledby="detalle-realizado-titulo">
            <div class="modal-panel-cabecera">
                <div>
                    <p class="modal-panel-kicker">Detalle validado</p>
                    <h4 id="detalle-realizado-titulo">Información del mantenimiento realizado</h4>
                    <p class="modal-panel-texto">Consulta la ejecución, las observaciones registradas y la constancia PDF del cierre realizado.</p>
                </div>
                <button type="button" class="modal-panel-cerrar" data-close-detalle-realizado aria-label="Cerrar detalle del mantenimiento">&times;</button>
            </div>

            <div class="modal-panel-resumen">
                <article class="modal-panel-resumen-item">
                    <span>Usuario</span>
                    <strong data-detalle-modal-usuario>Sin seleccionar</strong>
                </article>
                <article class="modal-panel-resumen-item">
                    <span>Área / Equipo</span>
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
                        <p class="muted">No se adjuntó constancia PDF en este mantenimiento.</p>
                    </div>
                </div>
            </div>

            <div class="actions panel-page-actions">
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
