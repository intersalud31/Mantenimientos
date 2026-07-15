<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Configuración</title>
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
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
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
        .btn.secondary {
            background: rgba(37, 99, 235, .15);
            border: 1px solid rgba(37, 99, 235, .35);
        }
        .actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
        }
        input, select {
            width: 100%;
            box-sizing: border-box;
            margin-top: 8px;
            padding: 12px 14px;
            border-radius: 12px;
            border: 1px solid rgba(148, 163, 184, .18);
            background: rgba(15, 23, 42, .95);
            color: #e5eefc;
        }
        label {
            display: block;
            margin-top: 14px;
            font-weight: 600;
            color: #dbeafe;
        }
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
        .topline {
            display: flex;
            justify-content: space-between;
            gap: 16px;
            align-items: center;
            flex-wrap: wrap;
        }
        .list {
            margin: 14px 0 0;
            padding-left: 18px;
        }
        .hint {
            margin-top: 8px;
            font-size: .92rem;
            color: #9fb0ca;
        }
    </style>
    @include('panel.partials.dashboard-modern-styles')
    @include('panel.partials.hechos-saas-overrides')
</head>
<body class="dashboard-modern">
    <main class="wrap">
        @include('panel.partials.topbar-saas', [
            'activeRoute' => 'panel.configuracion',
            'pageTitle' => 'Configuración',
            'pageSubtitle' => 'Acciones administrativas y técnicos activos.',
            'periodoConsulta' => $periodoConsulta ?? null,
        ])
        <section class="card">
            <div class="topline">
                <div>
                    <h1>Configuración</h1>
                    <p class="muted">Aquí dejamos las acciones administrativas que no van en usuarios ni reportes.</p>
                </div>
            </div>

            @if (session('panel_message'))
                <div class="notice">{{ session('panel_message') }}</div>
            @endif

            @if (session('panel_error'))
                <div class="notice error">{{ session('panel_error') }}</div>
            @endif
        </section>

        <section class="grid">
            <article class="card">
                <h2>Registrar técnico</h2>
                <p class="muted">Esto alimenta el selector de responsables cuando marcas un mantenimiento como hecho.</p>
                <form method="POST" action="{{ route('panel.tecnicos.crear') }}">
                    @csrf
                    <label for="nombre_tecnico">Nombre del técnico</label>
                    <input id="nombre_tecnico" type="text" name="nombre_tecnico" maxlength="150" required>
                    <input type="hidden" name="return_url" value="{{ request()->fullUrl() }}">
                    <button class="btn" type="submit">Guardar técnico</button>
                </form>

                <div class="hint">
                    Técnicos activos registrados actualmente:
                </div>
                @if (count($tecnicos ?? []) > 0)
                    <ul class="list">
                        @foreach ($tecnicos as $tecnico)
                            <li>{{ $tecnico['nombre'] }}</li>
                        @endforeach
                    </ul>
                @else
                    <p class="muted">Aún no hay técnicos activos.</p>
                @endif
            </article>

            <article class="card">
                <h2>Revertir última acción</h2>
                <p class="muted">Busca por nombre o código activo. Luego elige cuántas reversiones quieres aplicar, entre 1 y 10.</p>
                <form method="GET" action="{{ route('panel.configuracion') }}">
                    <label for="busqueda_usuario_preview">Buscar usuario</label>
                    <input id="busqueda_usuario_preview" type="text" name="busqueda_usuario" value="{{ $busquedaUsuario }}" placeholder="Ej: Natalia o 845">
                    <button class="btn secondary" type="submit">Buscar</button>
                </form>

                @if (!empty($usuarioResuelto))
                    <div class="stat" style="margin-top: 16px;">
                        <strong>Usuario resuelto</strong>
                        <div class="muted">{{ $usuarioResuelto['nombre'] ?? '-' }}</div>
                        <div class="muted">Código activo: {{ $usuarioResuelto['codigo_activo'] ?? '-' }}</div>
                    </div>
                @endif

                <form method="POST" action="{{ route('panel.configuracion.revertir') }}">
                    @csrf
                    <label for="busqueda_usuario">Usuario a revertir</label>
                    <input id="busqueda_usuario" type="text" name="busqueda_usuario" value="{{ $busquedaUsuario }}" required>
                    <input type="hidden" name="return_url" value="{{ request()->fullUrl() }}">

                    <label for="cantidad_reversiones">Cantidad de reversiones</label>
                    <select id="cantidad_reversiones" name="cantidad_reversiones">
                        @for ($i = 1; $i <= 10; $i++)
                            <option value="{{ $i }}" {{ (int) ($cantidadReversiones ?? 1) === $i ? 'selected' : '' }}>{{ $i }}</option>
                        @endfor
                    </select>

                    <button class="btn" type="submit">Revertir acciones</button>
                </form>
                <p class="hint">El flujo primero resuelve el usuario y después aplica la reversión sobre sus mantenimientos recientes.</p>
            </article>
        </section>
    </main>
</body>
</html>
