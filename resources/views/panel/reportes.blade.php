<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Dashboard</title>
    @include('panel.partials.dashboard-modern-styles')
    @include('panel.partials.hechos-saas-overrides')
    <style>
        body.dashboard-modern .reportes-stack {
            display: flex;
            flex-direction: column;
            gap: 18px;
            align-items: stretch;
        }

        body.dashboard-modern .reportes-stack > * {
            width: 100%;
            max-width: 100%;
            min-width: 0;
        }

        body.dashboard-modern .reportes-filter-wrap .panel-filter-card {
            width: 100%;
        }

        body.dashboard-modern .reportes-filter-wrap .panel-filter-grid {
            grid-template-columns: minmax(0, 1fr) minmax(220px, .82fr) auto auto;
            gap: 14px 16px;
            align-items: end;
        }

        body.dashboard-modern .reportes-filter-wrap .panel-filter-actions {
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-end;
            align-items: end;
            gap: 10px;
            align-self: end;
            padding-bottom: 2px;
        }

        body.dashboard-modern .reportes-summary-text {
            margin: 0;
            color: #475569;
            line-height: 1.65;
        }

        body.dashboard-modern .reportes-module-actions {
            display: flex;
            justify-content: flex-start;
            align-items: center;
            gap: 10px;
            flex-wrap: wrap;
        }

        body.dashboard-modern .reportes-metric-link {
            text-decoration: none;
            color: inherit;
            transition: transform .18s ease, box-shadow .18s ease, background-color .18s ease, border-color .18s ease;
        }

        body.dashboard-modern .reportes-metric-link:hover,
        body.dashboard-modern .reportes-metric-link:focus-visible {
            background: #f8fbff;
            box-shadow: inset 0 0 0 1px rgba(37, 99, 235, .14);
            transform: translateY(-1px);
            outline: none;
        }

        body.dashboard-modern .reportes-compliance {
            padding: 18px 20px;
            border-radius: 20px;
            border: 1px solid #e2e8f0;
            background: #f8fbff;
        }

        body.dashboard-modern .reportes-compliance-top {
            display: flex;
            justify-content: space-between;
            gap: 12px;
            align-items: flex-start;
            flex-wrap: wrap;
            margin-bottom: 14px;
        }

        body.dashboard-modern .reportes-compliance-value {
            margin: 4px 0 0;
            color: #1d4ed8;
            font-size: clamp(2rem, 4vw, 3rem);
            font-weight: 800;
            letter-spacing: -.04em;
            line-height: 1;
        }

        body.dashboard-modern .reportes-compliance-ratio {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: 10px;
            color: #1e293b;
            font-weight: 700;
        }

        body.dashboard-modern .reportes-compliance-note {
            margin: 10px 0 0;
        }

        body.dashboard-modern .reportes-compliance--band {
            margin-top: 18px;
        }

        body.dashboard-modern .reportes-metrics {
            margin-top: 22px;
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            border: 1px solid #e2e8f0;
            border-radius: 22px;
            overflow: hidden;
            background: #fff;
        }

        body.dashboard-modern .reportes-metric {
            padding: 18px 18px 20px;
            border-right: 1px solid #e2e8f0;
            border-bottom: 1px solid #e2e8f0;
            min-width: 0;
        }

        body.dashboard-modern .reportes-metric:nth-child(3n) {
            border-right: 0;
        }

        body.dashboard-modern .reportes-metric:nth-last-child(-n+3) {
            border-bottom: 0;
        }

        body.dashboard-modern .reportes-metric span {
            display: block;
            color: #64748b;
            font-size: .82rem;
            font-weight: 800;
            letter-spacing: .04em;
            text-transform: uppercase;
        }

        body.dashboard-modern .reportes-metric strong {
            display: block;
            margin-top: 8px;
            color: #0f172a;
            font-size: 1.7rem;
            line-height: 1;
            letter-spacing: -.03em;
        }

        body.dashboard-modern .reportes-metric p {
            margin: 10px 0 0;
            color: #64748b;
            line-height: 1.5;
        }

        body.dashboard-modern .reportes-metric--primary strong {
            color: #1d4ed8;
        }

        body.dashboard-modern .reportes-metric--success strong {
            color: #059669;
        }

        body.dashboard-modern .reportes-metric--warning strong {
            color: #d97706;
        }

        body.dashboard-modern .reportes-distribution-layout {
            display: grid;
            grid-template-columns: minmax(260px, 340px) minmax(0, 1fr);
            gap: 24px;
            align-items: center;
        }

        body.dashboard-modern .reportes-distribution-chart-wrap {
            display: flex;
            justify-content: center;
            align-items: center;
            min-width: 0;
        }

        body.dashboard-modern .reportes-distribution-chart {
            width: min(100%, 320px);
            height: auto;
            overflow: visible;
        }

        body.dashboard-modern .reportes-distribution-track {
            fill: none;
            stroke: #e2e8f0;
            stroke-width: 24;
        }

        body.dashboard-modern .reportes-distribution-segment {
            fill: none;
            stroke-width: 24;
            stroke-linecap: butt;
            transition: opacity .18s ease, filter .18s ease;
        }

        body.dashboard-modern .reportes-distribution-segment:hover,
        body.dashboard-modern .reportes-distribution-segment:focus-visible {
            opacity: .92;
            filter: brightness(.98);
            outline: none;
        }

        body.dashboard-modern .reportes-distribution-hole {
            fill: #fff;
        }

        body.dashboard-modern .reportes-distribution-total {
            fill: #0f172a;
            font-size: 1.95rem;
            font-weight: 800;
            letter-spacing: -.04em;
            text-anchor: middle;
        }

        body.dashboard-modern .reportes-distribution-caption {
            fill: #64748b;
            font-size: .82rem;
            font-weight: 700;
            letter-spacing: .04em;
            text-transform: uppercase;
            text-anchor: middle;
        }

        body.dashboard-modern .reportes-distribution-legend {
            display: grid;
            gap: 12px;
            min-width: 0;
        }

        body.dashboard-modern .reportes-distribution-link {
            display: grid;
            grid-template-columns: auto minmax(0, 1fr) auto;
            gap: 12px;
            align-items: center;
            padding: 14px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 18px;
            background: #fff;
            color: inherit;
            text-decoration: none;
            transition: transform .18s ease, box-shadow .18s ease, border-color .18s ease, background-color .18s ease;
        }

        body.dashboard-modern .reportes-distribution-link:hover,
        body.dashboard-modern .reportes-distribution-link:focus-visible {
            transform: translateY(-1px);
            border-color: rgba(37, 99, 235, .22);
            background: #f8fbff;
            box-shadow: inset 0 0 0 1px rgba(37, 99, 235, .10);
            outline: none;
        }

        body.dashboard-modern .reportes-distribution-swatch {
            width: 14px;
            height: 14px;
            border-radius: 999px;
            background: var(--swatch-color, #cbd5e1);
            box-shadow: 0 0 0 4px color-mix(in srgb, var(--swatch-color, #cbd5e1) 18%, #ffffff);
            flex-shrink: 0;
        }

        body.dashboard-modern .reportes-distribution-copy {
            min-width: 0;
        }

        body.dashboard-modern .reportes-distribution-copy strong,
        body.dashboard-modern .reportes-distribution-copy small {
            display: block;
        }

        body.dashboard-modern .reportes-distribution-copy strong {
            color: #0f172a;
            font-size: .98rem;
            font-weight: 700;
        }

        body.dashboard-modern .reportes-distribution-copy small {
            margin-top: 4px;
            color: #64748b;
            line-height: 1.45;
        }

        body.dashboard-modern .reportes-distribution-value {
            color: #0f172a;
            font-size: 1.15rem;
            font-weight: 800;
            white-space: nowrap;
        }

        body.dashboard-modern .reportes-activity-table td {
            vertical-align: middle;
        }

        body.dashboard-modern .reportes-activity-date {
            white-space: nowrap;
            font-weight: 700;
            color: #0f172a;
        }

        body.dashboard-modern .reportes-activity-name,
        body.dashboard-modern .reportes-activity-area {
            font-weight: 600;
        }

        body.dashboard-modern .reportes-activity-empty {
            padding: 16px 0 6px;
        }

        body.dashboard-modern .reportes-activity-badge {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 100px;
        }

        @media (max-width: 1200px) {
            body.dashboard-modern .reportes-metrics {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            body.dashboard-modern .reportes-distribution-layout {
                grid-template-columns: minmax(0, 1fr);
            }

            body.dashboard-modern .reportes-metric:nth-child(3n) {
                border-right: 1px solid #e2e8f0;
            }

            body.dashboard-modern .reportes-metric:nth-child(2n) {
                border-right: 0;
            }

            body.dashboard-modern .reportes-metric:nth-last-child(-n+2) {
                border-bottom: 0;
            }
        }

        @media (max-width: 760px) {
            body.dashboard-modern .reportes-filter-wrap .panel-filter-grid,
            body.dashboard-modern .reportes-metrics {
                grid-template-columns: 1fr;
            }

            body.dashboard-modern .reportes-filter-wrap .panel-filter-actions {
                justify-content: stretch;
            }

            body.dashboard-modern .reportes-module-actions {
                width: 100%;
            }

            body.dashboard-modern .reportes-metric {
                border-right: 0;
            }

            body.dashboard-modern .reportes-metric:not(:last-child) {
                border-bottom: 1px solid #e2e8f0;
            }

            body.dashboard-modern .reportes-distribution-link {
                grid-template-columns: auto minmax(0, 1fr);
            }

            body.dashboard-modern .reportes-distribution-value {
                grid-column: 2;
                justify-self: start;
            }
        }
    </style>
</head>
<body class="dashboard-modern">
    @include('panel.partials.topbar-saas', [
        'activeRoute' => 'panel.reportes',
        'periodoConsulta' => $periodoConsulta ?? null,
        'brand' => 'Mantenimientos TI',
        'subbrand' => 'Dashboard administrativo',
        'showSearch' => false,
        'showModuleContext' => false,
    ])

    @php
        $anioSeleccionado = (int) (($periodoConsulta['anio'] ?? request('anio')) ?: now()->year);
        $semestreSeleccionado = (int) (($periodoConsulta['semestre'] ?? request('semestre')) ?: ((int) now()->format('n') <= 6 ? 1 : 2));
        $faseNombre = trim((string) ($resumenSemestral['fase_nombre'] ?? ($semestreSeleccionado === 1 ? 'Primer semestre' : 'Segundo semestre')));
        $anioResumen = (int) ($resumenSemestral['anio'] ?? $anioSeleccionado);
        $periodoVisible = trim($faseNombre . ' de ' . $anioResumen);
        $cumplimiento = (int) ($resumenSemestral['indice_cumplimiento'] ?? 0);
        $cumplimientoVisual = max(0, min(100, $cumplimiento));
        $formatearFecha = static function (?string $valor): string {
            $valor = trim((string) $valor);

            if ($valor === '') {
                return 'Sin fecha';
            }

            try {
                return str_replace('.', '', \Illuminate\Support\Carbon::parse($valor)->locale('es')->translatedFormat('j M Y'));
            } catch (\Throwable) {
                return $valor;
            }
        };
        $inicioVisible = $formatearFecha($resumenSemestral['inicio'] ?? '');
        $finVisible = $formatearFecha($resumenSemestral['fin'] ?? '');
        $rangoVisible = $inicioVisible !== 'Sin fecha' && $finVisible !== 'Sin fecha'
            ? $inicioVisible . ' a ' . $finVisible
            : trim($inicioVisible . ' ' . $finVisible);
        $formatearNumero = static function ($valor): string {
            return number_format((int) $valor, 0, ',', '.');
        };
        $tipoActividad = static function (?string $valor): array {
            $valor = trim((string) $valor);
            $clave = mb_strtolower($valor, 'UTF-8');

            return match ($clave) {
                'programado' => ['label' => 'Programado', 'class' => 'estado-tabla-info'],
                'realizado', 'ejecutado' => ['label' => 'Ejecutado', 'class' => 'estado-tabla-exito'],
                default => ['label' => $valor !== '' ? mb_convert_case($valor, MB_CASE_TITLE, 'UTF-8') : 'Actividad', 'class' => 'estado-tabla-neutral'],
            };
        };
        $graficoDistribucion = [
            [
                'label' => 'Ejecutados',
                'value' => (int) ($resumenSemestral['total_realizados'] ?? 0),
                'href' => route('panel.hechos', $periodoQuery),
                'color' => '#059669',
                'description' => 'Mantenimientos completados dentro del semestre consultado.',
            ],
            [
                'label' => 'Programados',
                'value' => (int) ($resumenSemestral['total_programados'] ?? 0),
                'href' => route('panel.programados', $periodoQuery),
                'color' => '#2563eb',
                'description' => 'Mantenimientos ya agendados y aún vigentes.',
            ],
            [
                'label' => 'Pendientes',
                'value' => (int) ($resumenSemestral['total_pendientes'] ?? 0),
                'href' => route('panel.reportes.pendientes', $periodoQuery),
                'color' => '#d97706',
                'description' => 'Mantenimientos aún sin ejecutar durante el periodo.',
            ],
            [
                'label' => 'No exigibles',
                'value' => (int) ($resumenSemestral['total_no_exigibles'] ?? 0),
                'href' => route('panel.reportes.equipos-nuevos', $periodoQuery),
                'color' => '#64748b',
                'description' => 'Usuarios activos cuya exigencia queda fuera del semestre consultado.',
            ],
        ];
        $totalDistribucion = array_sum(array_map(static fn (array $item): int => $item['value'], $graficoDistribucion));
        $radioDistribucion = 84;
        $circunferenciaDistribucion = 2 * pi() * $radioDistribucion;
        $desplazamientoDistribucion = 0.0;
        $resumenDistribucion = [];

        foreach ($graficoDistribucion as $indice => $item) {
            $valor = (int) $item['value'];
            $fraccion = $totalDistribucion > 0 ? $valor / $totalDistribucion : 0;
            $trazo = $circunferenciaDistribucion * $fraccion;
            $graficoDistribucion[$indice]['stroke'] = $trazo;
            $graficoDistribucion[$indice]['offset'] = $desplazamientoDistribucion;
            $desplazamientoDistribucion += $trazo;
            $resumenDistribucion[] = $item['label'] . ' ' . $formatearNumero($valor);
        }

        $descripcionDistribucion = implode(', ', $resumenDistribucion);
    @endphp

    <main class="wrap reportes-stack">
        <section class="module-header" aria-label="Dashboard">
            <div class="module-header-copy">
                <p class="module-header-kicker">Dashboard</p>
                <h1 class="module-header-title">Dashboard</h1>
                <p class="module-header-subtitle">Consulta el cumplimiento del periodo y la actividad operativa registrada para cada semestre.</p>
            </div>
        </section>

        <div class="reportes-filter-wrap">
            <x-panel.filter-bar
                action="{{ route('panel.reportes') }}"
                title="Periodo de consulta"
                subtitle="Selecciona el año y semestre para revisar el resumen semestral."
                grid-class="panel-filter-grid--period"
                submit-label="Consultar periodo"
            >
                <x-slot:fields>
                    <label class="panel-filter-field panel-filter-field--narrow" for="anio">
                        <span>Año</span>
                        <input id="anio" type="number" name="anio" min="2000" max="{{ now()->year + 1 }}" value="{{ $anioSeleccionado }}">
                    </label>
                    <label class="panel-filter-field panel-filter-field--narrow" for="semestre">
                        <span>Semestre</span>
                        <select id="semestre" name="semestre">
                            <option value="1" {{ $semestreSeleccionado === 1 ? 'selected' : '' }}>Primer semestre</option>
                            <option value="2" {{ $semestreSeleccionado === 2 ? 'selected' : '' }}>Segundo semestre</option>
                        </select>
                    </label>
                </x-slot:fields>

                <x-slot:actions>
                    <button class="btn secondary" type="submit">Consultar periodo</button>
                </x-slot:actions>
            </x-panel.filter-bar>
        </div>

        <section class="card panel-card">
            <div class="panel-card-header">
                <div class="panel-title-block">
                    <p class="section-kicker">Indicadores</p>
                    <h2 class="panel-section-title">{{ $periodoVisible !== '' ? $periodoVisible : 'Semestre consultado' }}</h2>
                    <p class="muted panel-section-subtitle">{{ $rangoVisible }}</p>
                    <p class="reportes-summary-text">Resumen operativo del periodo seleccionado con el nivel de cumplimiento y la actividad reciente consolidada en una sola vista.</p>
                </div>
            </div>

            <div class="reportes-compliance reportes-compliance--band" aria-label="Cumplimiento del periodo">
                <div class="reportes-compliance-top">
                    <div>
                        <p class="section-kicker">Cumplimiento</p>
                        <div class="reportes-compliance-value">{{ $cumplimiento }}%</div>
                    </div>
                </div>

                <div class="reportes-compliance-ratio">{{ $resumenSemestral['total_realizados'] ?? 0 }} de {{ $resumenSemestral['total_base_fase'] ?? 0 }}</div>

                <p class="muted reportes-compliance-note">
                    Relación entre mantenimientos ejecutados y los equipos a gestionar considerados para el periodo.
                </p>

                <div class="progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $cumplimientoVisual }}" aria-valuetext="{{ $cumplimiento }} por ciento de cumplimiento">
                    <div class="progress-bar" style="width: {{ $cumplimientoVisual }}%"></div>
                </div>
            </div>

            <div class="reportes-metrics" aria-label="Indicadores del periodo">
                <a
                    class="reportes-metric reportes-metric--primary reportes-metric-link"
                    href="{{ route('panel.usuarios', array_merge($periodoQuery, ['estado_usuario' => 1])) }}"
                    aria-label="Ver detalle de usuarios activos del periodo"
                >
                    <span>Usuarios activos</span>
                    <strong>{{ $formatearNumero($resumenSemestral['total_usuarios'] ?? 0) }}</strong>
                    <p>Usuarios habilitados dentro del periodo consultado.</p>
                </a>
                <a
                    class="reportes-metric reportes-metric-link"
                    href="{{ route('panel.reportes.base-fase', $periodoQuery) }}"
                    aria-label="Ver detalle de equipos a gestionar del periodo"
                >
                    <span>Equipos a gestionar</span>
                    <strong>{{ $formatearNumero($resumenSemestral['total_base_fase'] ?? 0) }}</strong>
                    <p>Equipos activos que requieren seguimiento de mantenimiento durante el periodo.</p>
                </a>
                <a
                    class="reportes-metric reportes-metric--warning reportes-metric-link"
                    href="{{ route('panel.reportes.equipos-nuevos', $periodoQuery) }}"
                    aria-label="Ver detalle de usuarios no exigibles del periodo"
                >
                    <span>No exigibles</span>
                    <strong>{{ $formatearNumero($resumenSemestral['total_no_exigibles'] ?? 0) }}</strong>
                    <p>Usuarios activos cuya exigencia de mantenimiento queda fuera del periodo consultado.</p>
                </a>
                <a
                    class="reportes-metric reportes-metric--success reportes-metric-link"
                    href="{{ route('panel.hechos', $periodoQuery) }}"
                    aria-label="Ver detalle de mantenimientos ejecutados del periodo"
                >
                    <span>Ejecutados</span>
                    <strong>{{ $formatearNumero($resumenSemestral['total_realizados'] ?? 0) }}</strong>
                    <p>Mantenimientos ejecutados durante el periodo.</p>
                </a>
                <a
                    class="reportes-metric reportes-metric--primary reportes-metric-link"
                    href="{{ route('panel.programados', $periodoQuery) }}"
                    aria-label="Ver detalle de mantenimientos programados del periodo"
                >
                    <span>Programados</span>
                    <strong>{{ $formatearNumero($resumenSemestral['total_programados'] ?? 0) }}</strong>
                    <p>Mantenimientos programados que siguen vigentes.</p>
                </a>
                <a
                    class="reportes-metric reportes-metric--warning reportes-metric-link"
                    href="{{ route('panel.reportes.pendientes', $periodoQuery) }}"
                    aria-label="Ver detalle de mantenimientos pendientes del periodo"
                >
                    <span>Pendientes</span>
                    <strong>{{ $formatearNumero($resumenSemestral['total_pendientes'] ?? 0) }}</strong>
                    <p>Mantenimientos aún sin ejecutar en el periodo.</p>
                </a>
            </div>
        </section>

        <section class="card panel-card">
            <div class="panel-card-header">
                <div class="panel-title-block">
                    <p class="section-kicker">Distribución del periodo</p>
                    <h2 class="panel-section-title">Distribución operativa del semestre</h2>
                    <p class="muted panel-section-subtitle">Resumen de los mantenimientos según su situación durante el semestre seleccionado.</p>
                </div>
            </div>

            <div class="reportes-distribution-layout">
                <div class="reportes-distribution-chart-wrap">
                    <svg
                        class="reportes-distribution-chart"
                        viewBox="0 0 220 220"
                        role="img"
                        aria-labelledby="reportes-distribution-title reportes-distribution-desc"
                    >
                        <title id="reportes-distribution-title">Distribución del periodo</title>
                        <desc id="reportes-distribution-desc">{{ $descripcionDistribucion !== '' ? $descripcionDistribucion : 'Sin datos para el periodo consultado.' }}</desc>

                        <circle class="reportes-distribution-track" cx="110" cy="110" r="{{ $radioDistribucion }}"></circle>

                        @if ($totalDistribucion > 0)
                            @foreach ($graficoDistribucion as $segmento)
                                @if (($segmento['value'] ?? 0) > 0)
                                    <a
                                        href="{{ $segmento['href'] }}"
                                        aria-label="Ver {{ mb_strtolower($segmento['label'], 'UTF-8') }} del periodo: {{ $formatearNumero($segmento['value']) }}"
                                    >
                                        <circle
                                            class="reportes-distribution-segment"
                                            cx="110"
                                            cy="110"
                                            r="{{ $radioDistribucion }}"
                                            stroke="{{ $segmento['color'] }}"
                                            stroke-dasharray="{{ $segmento['stroke'] }} {{ $circunferenciaDistribucion }}"
                                            stroke-dashoffset="-{{ $segmento['offset'] }}"
                                            transform="rotate(-90 110 110)"
                                        ></circle>
                                    </a>
                                @endif
                            @endforeach
                        @endif

                        <circle class="reportes-distribution-hole" cx="110" cy="110" r="58"></circle>
                        <text class="reportes-distribution-total" x="110" y="108">{{ $formatearNumero($totalDistribucion) }}</text>
                        <text class="reportes-distribution-caption" x="110" y="132">registros</text>
                    </svg>
                </div>

                <div class="reportes-distribution-legend" aria-label="Leyenda del gráfico de distribución">
                    @foreach ($graficoDistribucion as $segmento)
                        <a
                            class="reportes-distribution-link"
                            href="{{ $segmento['href'] }}"
                            aria-label="Ir al detalle de {{ mb_strtolower($segmento['label'], 'UTF-8') }} con {{ $formatearNumero($segmento['value']) }} registros"
                        >
                            <span class="reportes-distribution-swatch" style="--swatch-color: {{ $segmento['color'] }}"></span>
                            <span class="reportes-distribution-copy">
                                <strong>{{ $segmento['label'] }}</strong>
                                <small>{{ $segmento['description'] }}</small>
                            </span>
                            <span class="reportes-distribution-value">{{ $formatearNumero($segmento['value']) }}</span>
                        </a>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="card panel-card">
            <div class="panel-card-header">
                <div class="panel-title-block">
                    <p class="section-kicker">Historial</p>
                    <h2 class="panel-section-title">Actividad reciente</h2>
                    <p class="muted panel-section-subtitle">Últimos movimientos registrados para el periodo consultado.</p>
                </div>
            </div>

            <div class="panel-table-shell">
                @if (!empty($actividadReciente))
                    <table class="panel-table reportes-activity-table" aria-label="Actividad reciente del periodo consultado">
                        <thead>
                            <tr>
                                <th>Tipo</th>
                                <th>Nombre</th>
                                <th>Área</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($actividadReciente as $actividad)
                                @php
                                    $tipoConfigurado = $tipoActividad((string) ($actividad['tipo'] ?? ''));
                                @endphp
                                <tr>
                                    <td>
                                        <span class="estado-tabla {{ $tipoConfigurado['class'] }} reportes-activity-badge">
                                            {{ $tipoConfigurado['label'] }}
                                        </span>
                                    </td>
                                    <td class="reportes-activity-name">{{ $actividad['nombre'] ?? 'Sin nombre' }}</td>
                                    <td class="reportes-activity-area">{{ $actividad['area_nombre'] ?? 'Sin área' }}</td>
                                    <td class="reportes-activity-date">{{ $formatearFecha($actividad['fecha'] ?? '') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @else
                    <div class="empty-state reportes-activity-empty" role="status" aria-live="polite">
                        <span class="empty-state-icon" aria-hidden="true">0</span>
                        <div>
                            <strong>No hay actividad reciente</strong>
                            <p class="muted">No existen movimientos para el periodo consultado.</p>
                        </div>
                    </div>
                @endif
            </div>
        </section>
    </main>
</body>
</html>
