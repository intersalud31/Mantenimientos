<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Configuración</title>
    @include('panel.partials.dashboard-modern-styles')
    @include('panel.partials.hechos-saas-overrides')
    @include('panel.partials.usuarios-saas-overrides')
</head>
<body class="dashboard-modern">
    @include('panel.partials.topbar-saas', [
        'activeRoute' => 'panel.configuracion',
        'pageTitle' => 'Configuración',
        'pageSubtitle' => 'Acciones administrativas, técnicos activos y reversión controlada de mantenimientos.',
        'periodoConsulta' => $periodoConsulta ?? null,
        'showModuleContext' => false,
    ])

    @php
        $tecnicosActivos = collect($tecnicos ?? []);
        $totalTecnicosActivos = $tecnicosActivos->count();
        $busquedaUsuario = trim((string) ($busquedaUsuario ?? ''));
        $cantidadReversiones = max(1, min(10, (int) ($cantidadReversiones ?? 1)));
        $usuarioResueltoNombre = trim((string) ($usuarioResuelto['nombre'] ?? ''));
        $usuarioResueltoCodigo = (int) ($usuarioResuelto['codigo_activo'] ?? 0);
        $usuarioConfirmado = $usuarioResueltoNombre !== '' && $usuarioResueltoCodigo > 0;
        $pasoActual = $usuarioConfirmado ? 2 : 1;
    @endphp

    <main class="wrap">
        <section class="module-header" aria-label="Configuración">
            <div class="module-header-copy">
                <p class="module-header-kicker">Administración</p>
                <h1 class="module-header-title">Configuración</h1>
                <p class="module-header-subtitle">Administración de técnicos y reversión controlada de mantenimientos.</p>
            </div>
        </section>

        @if (session('panel_message'))
            <section class="card panel-card">
                <div class="notice">{{ session('panel_message') }}</div>
            </section>
        @endif

        @if (session('panel_error'))
            <section class="card panel-card">
                <div class="notice error">{{ session('panel_error') }}</div>
            </section>
        @endif

        <section class="card users-module-shell users-module-shell--head config-section">
            <div class="users-toolbar users-toolbar--integrated">
                <div>
                    <p class="section-kicker">Gestión</p>
                    <h2 class="panel-section-title">Registrar técnico</h2>
                    <p class="muted panel-section-subtitle">Agrega los responsables disponibles para la ejecución de mantenimientos.</p>
                </div>
            </div>

            <form method="POST" action="{{ route('panel.tecnicos.crear') }}" class="config-inline-form">
                @csrf
                <input type="hidden" name="return_url" value="{{ request()->fullUrl() }}">

                <div class="config-inline-form-row">
                    <div class="field">
                        <label for="nombre_tecnico">Nombre del técnico</label>
                        <input id="nombre_tecnico" type="text" name="nombre_tecnico" maxlength="150" required placeholder="Ej: Juan Manuel" value="{{ old('nombre_tecnico') }}">
                        @error('nombre_tecnico')
                            <span class="field-error">{{ $message }}</span>
                        @enderror
                    </div>

                    <div class="field-actions config-inline-form-actions">
                        <button class="btn btn-primary" type="submit">Guardar técnico</button>
                    </div>
                </div>
            </form>
        </section>

        <section class="card users-module-shell users-module-shell--table config-section">
            <div class="panel-card-header panel-topline-spaced">
                <div class="panel-title-block">
                    <p class="section-kicker">Técnicos</p>
                    <h2 class="panel-section-title">Técnicos activos</h2>
                    <p class="muted panel-section-subtitle">Responsables disponibles para asignar mantenimientos.</p>
                </div>
                <div class="panel-meta-row">
                    <span class="chip chip--neutral">{{ $totalTecnicosActivos }} registro(s)</span>
                </div>
            </div>

            @if ($totalTecnicosActivos > 0)
                <ul class="config-tech-list" aria-label="Técnicos activos registrados actualmente">
                    @foreach ($tecnicosActivos as $tecnico)
                        <li class="config-tech-item">
                            <span class="config-tech-dot" aria-hidden="true"></span>
                            <span class="config-tech-name">{{ $tecnico['nombre'] }}</span>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="config-empty-state">
                    <strong>Aún no hay técnicos activos.</strong>
                    <p class="muted">Registra el primer técnico para que el módulo de reversión tenga responsables disponibles.</p>
                </div>
            @endif
        </section>

        <section class="card users-module-shell users-module-shell--head config-section" id="configuracion-reversion">
            <div class="users-toolbar users-toolbar--integrated">
                <div>
                    <p class="section-kicker">Reversión</p>
                    <h2 class="panel-section-title">Revertir última acción</h2>
                    <p class="muted panel-section-subtitle">Busca al usuario, confirma el objetivo y selecciona cuántas acciones recientes deseas revertir.</p>
                </div>
            </div>

            <div class="config-stepbar" aria-label="Flujo de reversión">
                <span class="config-step {{ $pasoActual === 1 ? 'config-step--current' : 'config-step--done' }}" @if ($pasoActual === 1) aria-current="step" @endif>1. Buscar usuario</span>
                <span class="config-step {{ $pasoActual === 2 ? 'config-step--current' : 'config-step--pending' }}" @if ($pasoActual === 2) aria-current="step" @endif>2. Confirmar objetivo</span>
                <span class="config-step config-step--pending">3. Revertir acciones</span>
            </div>

            <x-panel.live-search
                action="{{ route('panel.configuracion') }}"
                name="busqueda_usuario"
                label="Buscar usuario"
                placeholder="Ej: Natalia o 845"
                value="{{ $busquedaUsuario }}"
                target="#configuracion-reversion"
                debounce="500"
                loading-label="Buscando..."
            >
                <x-slot:hidden>
                    <input type="hidden" name="cantidad_reversiones" value="{{ $cantidadReversiones }}">
                    @if ($usuarioResueltoCodigo > 0)
                        <input type="hidden" name="codigo_activo" value="{{ $usuarioResueltoCodigo }}">
                    @endif
                </x-slot:hidden>
            </x-panel.live-search>

            @if ($busquedaUsuario === '' && !$usuarioConfirmado)
                <div class="config-empty-callout">
                    <strong>Busca un usuario para comenzar la reversión.</strong>
                    <p class="muted">Puedes usar el nombre o el código activo. El sistema resolverá el usuario antes de permitir revertir acciones.</p>
                </div>
            @elseif (!$usuarioConfirmado)
                <div class="config-empty-callout">
                    <strong>No se encontró un usuario con esa búsqueda.</strong>
                    <p class="muted">Revisa el nombre o el código activo e inténtalo de nuevo.</p>
                </div>
            @else
                <section class="config-reversion-summary" aria-label="Usuario confirmado">
                    <div class="config-reversion-summary-copy">
                        <p class="section-kicker">Usuario confirmado</p>
                        <h3>{{ $usuarioResueltoNombre }}</h3>
                        <p class="muted">Código activo {{ $usuarioResueltoCodigo }}</p>
                    </div>
                </section>

                <form method="POST" action="{{ route('panel.configuracion.revertir') }}" class="config-revert-form">
                    @csrf
                    <input type="hidden" name="return_url" value="{{ request()->fullUrl() }}">
                    <input type="hidden" name="busqueda_usuario" value="{{ $busquedaUsuario }}">
                    <input type="hidden" name="codigo_activo" value="{{ $usuarioResueltoCodigo }}">

                    <div class="config-revert-grid">
                        <div class="field">
                            <label for="cantidad_reversiones">Cantidad de reversiones</label>
                            <select id="cantidad_reversiones" name="cantidad_reversiones">
                                @for ($i = 1; $i <= 10; $i++)
                                    <option value="{{ $i }}" {{ $cantidadReversiones === $i ? 'selected' : '' }}>{{ $i }}</option>
                                @endfor
                            </select>
                        </div>

                        <div class="field-actions config-revert-actions">
                            <button class="btn btn-primary" type="submit">Revertir acciones</button>
                        </div>
                    </div>

                    <div class="config-revert-note">
                        El flujo revertirá las acciones más recientes, desde la más nueva hacia atrás.
                    </div>
                </form>
            @endif
        </section>
    </main>
</body>
</html>
