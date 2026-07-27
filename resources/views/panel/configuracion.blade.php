<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Configuración</title>
    @include('panel.partials.dashboard-modern-styles')
    @include('panel.partials.hechos-saas-overrides')
</head>
<body class="dashboard-modern">
    @include('panel.partials.topbar-saas', [
        'activeRoute' => 'panel.configuracion',
        'pageTitle' => 'Configuración',
        'pageSubtitle' => 'Acciones administrativas y técnicos activos.',
        'periodoConsulta' => $periodoConsulta ?? null,
        'showModuleContext' => false,
    ])
    <main class="wrap">
        <section class="module-header" aria-label="Configuración">
            <div class="module-header-copy">
                <p class="module-header-kicker">Administración</p>
                <h1 class="module-header-title">Configuración</h1>
                <p class="module-header-subtitle">Acciones administrativas, técnicos activos y reversión controlada de mantenimientos.</p>
            </div>
        </section>

        @if (session('panel_message') || session('panel_error'))
        <section class="card panel-card panel-card--compact">
            @if (session('panel_message'))
                <div class="notice">{{ session('panel_message') }}</div>
            @endif

            @if (session('panel_error'))
                <div class="notice error">{{ session('panel_error') }}</div>
            @endif
        </section>
        @endif

        <section class="panel-grid panel-grid--two">
            <article class="card panel-card panel-form-stack">
                <div class="panel-card-header">
                    <div class="panel-title-block">
                        <p class="section-kicker">Técnicos</p>
                        <h2 class="panel-section-title">Registrar técnico</h2>
                        <p class="muted panel-section-subtitle">Esto alimenta el selector de responsables cuando marcas un mantenimiento como hecho.</p>
                    </div>
                </div>
                <form method="POST" action="{{ route('panel.tecnicos.crear') }}">
                    @csrf
                    <label for="nombre_tecnico">Nombre del técnico</label>
                    <input id="nombre_tecnico" type="text" name="nombre_tecnico" maxlength="150" required>
                    <input type="hidden" name="return_url" value="{{ request()->fullUrl() }}">
                    <div class="panel-form-actions">
                        <button class="btn" type="submit">Guardar técnico</button>
                    </div>
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

            <article class="card panel-card panel-form-stack">
                <div class="panel-card-header">
                    <div class="panel-title-block">
                        <p class="section-kicker">Reversión</p>
                        <h2 class="panel-section-title">Revertir última acción</h2>
                        <p class="muted panel-section-subtitle">Busca por nombre o código activo. Luego elige cuántas reversiones quieres aplicar, entre 1 y 10.</p>
                    </div>
                </div>
                <form method="GET" action="{{ route('panel.configuracion') }}">
                    <label for="busqueda_usuario_preview">Buscar usuario</label>
                    <input id="busqueda_usuario_preview" type="text" name="busqueda_usuario" value="{{ $busquedaUsuario }}" placeholder="Ej: Natalia o 845">
                    <div class="panel-form-actions">
                        <button class="btn secondary" type="submit">Buscar</button>
                    </div>
                </form>

                @if (!empty($usuarioResuelto))
                    <div class="stat panel-spaced">
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

                    <div class="panel-form-actions">
                        <button class="btn" type="submit">Revertir acciones</button>
                    </div>
                </form>
                <p class="hint">El flujo primero resuelve el usuario y después aplica la reversión sobre sus mantenimientos recientes.</p>
            </article>
        </section>
    </main>
</body>
</html>

