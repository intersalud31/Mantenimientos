<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reportes</title>
    @include('panel.partials.dashboard-modern-styles')
    @include('panel.partials.hechos-saas-overrides')
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
    <main class="wrap">
        <section class="module-header" aria-label="Reportes">
            <div class="module-header-copy">
                <p class="module-header-kicker">Reportes</p>
                <h1 class="module-header-title">Resumen semestral</h1>
                <p class="module-header-subtitle">Cumplimiento del periodo y lectura operativa por semestre.</p>
            </div>
        </section>

        <section class="card panel-card">
            <div class="panel-card-header">
                <div class="panel-title-block">
                    <p class="section-kicker">Filtros del periodo</p>
                    <h2 class="panel-section-title">Selecciona el año y semestre de consulta</h2>
                    <p class="muted panel-section-subtitle">Usa esta vista para revisar el cumplimiento semestral sin perder contexto.</p>
                </div>
            </div>

            @php
                $anioSeleccionado = (int) (($periodoConsulta['anio'] ?? request('anio')) ?: now()->year);
                $semestreSeleccionado = (int) (($periodoConsulta['semestre'] ?? request('semestre')) ?: ((int) now()->format('n') <= 6 ? 1 : 2));
            @endphp

            <x-panel.filter-bar
                action="{{ route('panel.reportes') }}"
                title="Filtros del periodo"
                subtitle="Selecciona el año y semestre de consulta sin perder contexto."
                grid-class="panel-filter-grid--period"
                submit-label="Ver periodo"
                reset-label="Semestre actual"
                reset-url="{{ route('panel.reportes') }}"
            >
                <x-slot:fields>
                    <label class="panel-filter-field panel-filter-field--narrow" for="anio">
                        <span>Año</span>
                        <input id="anio" type="number" name="anio" min="2000" max="{{ now()->year + 1 }}" value="{{ $anioSeleccionado }}">
                    </label>
                    <label class="panel-filter-field panel-filter-field--narrow" for="semestre">
                        <span>Semestre</span>
                        <select id="semestre" name="semestre">
                            <option value="1" {{ $semestreSeleccionado === 1 ? 'selected' : '' }}>Semestre 1</option>
                            <option value="2" {{ $semestreSeleccionado === 2 ? 'selected' : '' }}>Semestre 2</option>
                        </select>
                    </label>
                </x-slot:fields>

                <x-slot:actions>
                    <button class="btn" type="submit">Ver periodo</button>
                    <a class="btn-link secondary" href="{{ route('panel.reportes') }}">Semestre actual</a>
                </x-slot:actions>
            </x-panel.filter-bar>
        </section>

        <section class="card panel-card">
            <div class="panel-card-header">
                <div class="panel-title-block">
                    <p class="section-kicker">Indicadores</p>
                    <h2 class="panel-section-title">{{ $resumenSemestral['fase_nombre'] ?? 'Semestre actual' }}</h2>
                    <p class="muted panel-section-subtitle">{{ $resumenSemestral['inicio'] ?? '' }} a {{ $resumenSemestral['fin'] ?? '' }}</p>
                </div>
                @if (!empty($periodoConsulta['etiqueta']))
                    <div class="panel-meta-row">
                        <span class="chip chip--neutral">{{ $periodoConsulta['etiqueta'] }}</span>
                    </div>
                @endif
            </div>
            <div class="panel-grid panel-grid--metrics">
                <div class="stat">
                    <strong>Usuarios activos</strong>
                    <div class="muted">{{ $resumenSemestral['total_usuarios'] ?? 0 }}</div>
                </div>
                <div class="stat">
                    <strong>Base de fase</strong>
                    <div class="muted">{{ $resumenSemestral['total_base_fase'] ?? 0 }}</div>
                </div>
                <div class="stat">
                    <strong>No exigibles</strong>
                    <div class="muted">{{ $resumenSemestral['total_no_exigibles'] ?? 0 }}</div>
                </div>
                <div class="stat">
                    <strong>Realizados</strong>
                    <div class="muted">{{ $resumenSemestral['total_realizados'] ?? 0 }}</div>
                </div>
                <div class="stat">
                    <strong>Programados</strong>
                    <div class="muted">{{ $resumenSemestral['total_programados'] ?? 0 }}</div>
                </div>
                <div class="stat">
                    <strong>Pendientes</strong>
                    <div class="muted">{{ $resumenSemestral['total_pendientes'] ?? 0 }}</div>
                </div>
                <div class="stat">
                    <strong>Cumplimiento</strong>
                    <div class="muted">{{ $resumenSemestral['indice_cumplimiento'] ?? 0 }}%</div>
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
            <table class="panel-table">
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Nombre</th>
                        <th>Área</th>
                        <th>Fecha</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($actividadReciente as $actividad)
                        <tr>
                            <td>{{ $actividad['tipo'] }}</td>
                            <td>{{ $actividad['nombre'] }}</td>
                            <td>{{ $actividad['area_nombre'] }}</td>
                            <td>{{ $actividad['fecha'] }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="muted">No hay actividad reciente para mostrar.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
            </div>
        </section>
    </main>
</body>
</html>

