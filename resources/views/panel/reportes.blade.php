<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reportes</title>
    <style>
        body {
            margin: 0;
            font-family: Inter, Segoe UI, sans-serif;
            min-height: 100vh;
            color: #e5eefc;
            background: linear-gradient(135deg, #020617, #0f172a 55%, #111827);
        }
        .wrap {
            max-width: 1200px;
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
        .grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
        }
        .stat {
            padding: 18px;
            border-radius: 18px;
            background: rgba(15, 23, 42, .8);
            border: 1px solid rgba(148, 163, 184, .12);
        }
        .muted { color: #9fb0ca; }
        .btn, .btn-link {
            display: inline-block;
            margin-top: 12px;
            padding: 12px 16px;
            border-radius: 12px;
            background: #2563eb;
            color: #fff;
            text-decoration: none;
            border: 0;
            cursor: pointer;
        }
        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
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
        .topline {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: center;
            flex-wrap: wrap;
        }
    </style>
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

        <section class="card">
            <div class="topline">
                <div class="hero-copy">
                    <p class="section-kicker">Filtros del periodo</p>
                    <h2>Selecciona el año y semestre de consulta</h2>
                    <p class="muted">Usa esta vista para revisar el cumplimiento semestral sin perder contexto.</p>
                </div>
            </div>

            @php
                $anioSeleccionado = (int) (($periodoConsulta['anio'] ?? request('anio')) ?: now()->year);
                $semestreSeleccionado = (int) (($periodoConsulta['semestre'] ?? request('semestre')) ?: ((int) now()->format('n') <= 6 ? 1 : 2));
            @endphp

            <form method="GET" action="{{ route('panel.reportes') }}" class="filters" style="margin-top: 16px;">
                <div class="field" style="max-width: 180px;">
                    <label for="anio">Año</label>
                    <input id="anio" type="number" name="anio" min="2000" max="{{ now()->year + 1 }}" value="{{ $anioSeleccionado }}">
                </div>
                <div class="field" style="max-width: 180px;">
                    <label for="semestre">Semestre</label>
                    <select id="semestre" name="semestre">
                        <option value="1" {{ $semestreSeleccionado === 1 ? 'selected' : '' }}>Semestre 1</option>
                        <option value="2" {{ $semestreSeleccionado === 2 ? 'selected' : '' }}>Semestre 2</option>
                    </select>
                </div>
                <button class="btn" type="submit">Ver periodo</button>
                <a class="btn-link secondary" href="{{ route('panel.reportes') }}">Semestre actual</a>
            </form>
        </section>

        <section class="card">
            <h2>{{ $resumenSemestral['fase_nombre'] ?? 'Semestre actual' }}</h2>
            <p class="muted">{{ $resumenSemestral['inicio'] ?? '' }} a {{ $resumenSemestral['fin'] ?? '' }}</p>
            @if (!empty($periodoConsulta['etiqueta']))
                <p class="muted">{{ $periodoConsulta['etiqueta'] }}</p>
            @endif
            <div class="grid">
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

        <section class="card">
            <h2>Actividad reciente</h2>
            <table>
                <thead>
                    <tr>
                        <th>Tipo</th>
                        <th>Nombre</th>
                        <th>Area</th>
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
        </section>
    </main>
</body>
</html>
