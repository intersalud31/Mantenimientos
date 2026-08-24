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
    @endphp

    <main class="wrap reportes-stack">
        <section class="module-header" aria-label="Dashboard">
            <div class="module-header-copy">
                <p class="module-header-kicker">Dashboard</p>
                <h1 class="module-header-title">Dashboard</h1>
                <p class="module-header-subtitle">Consulta el cumplimiento del periodo y la actividad operativa registrada para cada semestre.</p>
            </div>
            <div class="reportes-module-actions">
                <a class="chip chip--neutral" href="{{ route('panel.reportes.equipos-nuevos') }}">
                    Equipos nuevos sin mantenimiento
                </a>
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
                    Relación entre mantenimientos ejecutados y la base de fase considerada para el periodo.
                </p>

                <div class="progress-track" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="{{ $cumplimientoVisual }}" aria-valuetext="{{ $cumplimiento }} por ciento de cumplimiento">
                    <div class="progress-bar" style="width: {{ $cumplimientoVisual }}%"></div>
                </div>
            </div>

            <div class="reportes-metrics" aria-label="Indicadores del periodo">
                <article class="reportes-metric reportes-metric--primary">
                    <span>Usuarios activos</span>
                    <strong>{{ $formatearNumero($resumenSemestral['total_usuarios'] ?? 0) }}</strong>
                    <p>Usuarios habilitados dentro del periodo consultado.</p>
                </article>
                <article class="reportes-metric">
                    <span>Base de fase</span>
                    <strong>{{ $formatearNumero($resumenSemestral['total_base_fase'] ?? 0) }}</strong>
                    <p>Usuarios considerados para el cálculo del semestre.</p>
                </article>
                <article class="reportes-metric reportes-metric--warning">
                    <span>No exigibles</span>
                    <strong>{{ $formatearNumero($resumenSemestral['total_no_exigibles'] ?? 0) }}</strong>
                    <p>Usuarios fuera de la base evaluada para este periodo.</p>
                </article>
                <article class="reportes-metric reportes-metric--success">
                    <span>Ejecutados</span>
                    <strong>{{ $formatearNumero($resumenSemestral['total_realizados'] ?? 0) }}</strong>
                    <p>Mantenimientos ejecutados durante el periodo.</p>
                </article>
                <article class="reportes-metric reportes-metric--primary">
                    <span>Programados</span>
                    <strong>{{ $formatearNumero($resumenSemestral['total_programados'] ?? 0) }}</strong>
                    <p>Mantenimientos programados que siguen vigentes.</p>
                </article>
                <article class="reportes-metric reportes-metric--warning">
                    <span>Pendientes</span>
                    <strong>{{ $formatearNumero($resumenSemestral['total_pendientes'] ?? 0) }}</strong>
                    <p>Mantenimientos aún sin ejecutar en el periodo.</p>
                </article>
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
