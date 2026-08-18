<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Configuración</title>
    @include('panel.partials.dashboard-modern-styles')
    @include('panel.partials.hechos-saas-overrides')
    @include('panel.partials.usuarios-saas-overrides')
    <style>
        body.dashboard-modern .state-management-shell {
            display: grid;
            gap: 16px;
        }

        body.dashboard-modern .state-management-shell,
        body.dashboard-modern .state-management-shell input,
        body.dashboard-modern .state-management-shell select,
        body.dashboard-modern .state-management-shell textarea,
        body.dashboard-modern .state-management-shell button,
        body.dashboard-modern .state-management-shell label {
            font-family: inherit;
        }

        body.dashboard-modern .state-management-shell .sr-only {
            position: absolute;
            width: 1px;
            height: 1px;
            padding: 0;
            margin: -1px;
            overflow: hidden;
            clip: rect(0, 0, 0, 0);
            white-space: nowrap;
            border: 0;
        }

        body.dashboard-modern .state-management-summary {
            display: grid;
            grid-template-columns: minmax(0, 1.35fr) repeat(3, minmax(0, .75fr));
            gap: 0;
            padding: 0;
            overflow: hidden;
            border: 1px solid #dfe7f2;
            border-radius: 20px;
            background: #fff;
        }

        body.dashboard-modern .state-management-summary-main {
            display: grid;
            gap: 8px;
            padding: 18px 20px 16px;
            min-width: 0;
        }

        body.dashboard-modern .state-management-summary-main h3 {
            margin: 0;
            color: #0f172a;
            font-size: 1.12rem;
            font-weight: 700;
            line-height: 1.2;
            letter-spacing: -.02em;
        }

        body.dashboard-modern .state-management-summary-intro {
            margin: 0;
            color: #64748b;
            font-size: .95rem;
            line-height: 1.5;
        }

        body.dashboard-modern .state-management-summary-main > .muted {
            display: none;
        }

        body.dashboard-modern .state-management-summary-meta-list {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            min-width: 0;
        }

        body.dashboard-modern .state-management-summary-meta {
            display: grid;
            gap: 6px;
            padding: 14px 20px 16px;
            min-width: 0;
            align-content: center;
        }

        body.dashboard-modern .state-management-summary-meta + .state-management-summary-meta {
            border-left: 1px solid #edf2f7;
        }

        body.dashboard-modern .state-management-summary-label {
            margin: 0;
            color: #64748b;
            font-size: .82rem;
            font-weight: 700;
            line-height: 1.35;
        }

        body.dashboard-modern .state-management-summary-value {
            margin: 0;
            color: #0f172a;
            font-size: .96rem;
            font-weight: 600;
            line-height: 1.4;
        }

        body.dashboard-modern .state-management-options {
            display: grid;
            gap: 10px;
            margin: 0;
            padding: 0;
            border: 0;
            min-width: 0;
        }

        body.dashboard-modern .state-management-options legend {
            padding: 0;
        }

        body.dashboard-modern .state-management-option {
            display: grid;
            grid-template-columns: 20px minmax(0, 1fr) auto;
            align-items: center;
            gap: 14px;
            min-height: 72px;
            padding: 14px 16px;
            border: 1px solid #dfe7f2;
            border-radius: 18px;
            background: #fff;
            cursor: pointer;
            transition: border-color .16s ease, background-color .16s ease, box-shadow .16s ease;
        }

        body.dashboard-modern .state-management-option:hover {
            border-color: #cbd5e1;
            background: #f8fbff;
        }

        body.dashboard-modern .state-management-option:focus-within {
            border-color: #93c5fd;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .10);
        }

        body.dashboard-modern .state-management-option--selected {
            border-color: #2563eb;
            background: #f8fbff;
            box-shadow: 0 8px 20px rgba(37, 99, 235, .08);
        }

        body.dashboard-modern .state-management-option--current {
            background: #f8fafc;
            cursor: not-allowed;
        }

        body.dashboard-modern .state-management-option--current:hover {
            border-color: #dfe7f2;
            background: #f8fafc;
            box-shadow: none;
        }

        body.dashboard-modern .state-management-radio {
            width: 16px;
            height: 16px;
            margin: 0;
            accent-color: #2563eb;
        }

        body.dashboard-modern .state-management-option--current .state-management-radio {
            pointer-events: none;
        }

        body.dashboard-modern .state-management-content {
            display: grid;
            gap: 4px;
            min-width: 0;
        }

        body.dashboard-modern .state-management-title {
            margin: 0;
            color: #0f172a;
            font-size: .98rem;
            font-weight: 700;
            line-height: 1.35;
            letter-spacing: -.01em;
        }

        body.dashboard-modern .state-management-description {
            margin: 0;
            color: #64748b;
            font-size: .92rem;
            line-height: 1.5;
        }

        body.dashboard-modern .state-management-option-badge {
            justify-self: end;
        }

        body.dashboard-modern .state-management-search-results {
            display: grid;
            gap: 10px;
        }

        body.dashboard-modern .state-management-search-option {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 14px;
            width: 100%;
            padding: 14px 16px;
            border: 1px solid #dfe7f2;
            border-radius: 16px;
            background: #fff;
            color: #0f172a;
            text-align: left;
            cursor: pointer;
            transition: border-color .16s ease, background-color .16s ease, box-shadow .16s ease;
        }

        body.dashboard-modern .state-management-search-option:hover,
        body.dashboard-modern .state-management-search-option:focus-visible {
            border-color: #93c5fd;
            background: #f8fbff;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, .10);
            outline: none;
        }

        body.dashboard-modern .state-management-search-option-main {
            display: grid;
            gap: 4px;
            min-width: 0;
        }

        body.dashboard-modern .state-management-search-option-title {
            color: #0f172a;
            font-size: .98rem;
            font-weight: 600;
            line-height: 1.35;
        }

        body.dashboard-modern .state-management-search-option-meta {
            color: #64748b;
            font-size: .92rem;
            line-height: 1.45;
        }

        body.dashboard-modern .state-management-fields {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 14px;
            align-items: end;
        }

        body.dashboard-modern .state-management-fields .state-management-fields-full,
        body.dashboard-modern .state-management-fields .maintenance-modal-full {
            grid-column: 1 / -1;
        }

        body.dashboard-modern .state-management-actions {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-top: 2px;
        }

        body.dashboard-modern .state-management-actions .btn {
            min-width: 220px;
        }

        body.dashboard-modern .state-management-preview {
            display: grid;
            gap: 16px;
        }

        body.dashboard-modern .state-management-preview-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 12px;
        }

        body.dashboard-modern .state-management-preview-item,
        body.dashboard-modern .state-management-preview-grid > div,
        body.dashboard-modern .state-management-preview-list > div {
            display: grid;
            gap: 6px;
            min-height: 0;
            padding: 14px 16px;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            background: #fff;
        }

        body.dashboard-modern .state-management-preview-item strong,
        body.dashboard-modern .state-management-preview-grid > div strong,
        body.dashboard-modern .state-management-preview-list > div strong {
            color: #64748b;
            font-size: .82rem;
            font-weight: 700;
            line-height: 1.35;
        }

        body.dashboard-modern .state-management-preview-item span,
        body.dashboard-modern .state-management-preview-item div,
        body.dashboard-modern .state-management-preview-grid > div span,
        body.dashboard-modern .state-management-preview-list > div span {
            color: #0f172a;
            font-size: .95rem;
            font-weight: 600;
            line-height: 1.45;
            word-break: break-word;
        }

        body.dashboard-modern .state-management-preview-list {
            display: grid;
            gap: 10px;
        }

        body.dashboard-modern .state-management-preview-list .state-management-preview-item {
            background: #f8fbff;
        }

        body.dashboard-modern .state-management-shell .config-revert-form {
            display: grid;
            gap: 16px;
        }

        body.dashboard-modern .state-management-shell .config-revert-grid,
        body.dashboard-modern .state-management-shell .config-revert-actions,
        body.dashboard-modern .state-management-shell .field-actions.config-revert-actions,
        body.dashboard-modern .state-management-shell .panel-card {
            margin-top: 0;
        }

        @media (max-width: 1024px) {
            body.dashboard-modern .state-management-summary,
            body.dashboard-modern .state-management-summary-meta-list,
            body.dashboard-modern .state-management-fields {
                grid-template-columns: 1fr;
            }

            body.dashboard-modern .state-management-summary-meta + .state-management-summary-meta {
                border-left: 0;
                border-top: 1px solid #edf2f7;
            }
        }

        @media (max-width: 720px) {
            body.dashboard-modern .state-management-option {
                grid-template-columns: 20px minmax(0, 1fr);
                align-items: start;
            }

            body.dashboard-modern .state-management-option-badge {
                grid-column: 2;
                justify-self: start;
            }

            body.dashboard-modern .state-management-search-option {
                align-items: flex-start;
                flex-direction: column;
            }

            body.dashboard-modern .state-management-actions .btn {
                width: 100%;
                min-width: 0;
            }
        }
    </style>
</head>
<body class="dashboard-modern">
    @include('panel.partials.topbar-saas', [
        'activeRoute' => 'panel.configuracion',
        'pageTitle' => 'Configuración',
        'pageSubtitle' => 'Acciones administrativas, técnicos activos y gestión controlada de estados de mantenimiento.',
        'periodoConsulta' => $periodoConsulta ?? null,
        'showModuleContext' => false,
    ])

    @php
        $tecnicosActivos = collect($tecnicos ?? []);
        $totalTecnicosActivos = $tecnicosActivos->count();
        $busquedaUsuario = trim((string) old('busqueda_usuario', $busquedaUsuario ?? ''));
        $estadoDestinoSeleccionado = trim((string) old('estado_destino', $estadoDestino ?? ''));
        $usuarioResueltoNombre = trim((string) ($usuarioResuelto['nombre'] ?? ''));
        $usuarioResueltoCodigo = (int) ($usuarioResuelto['codigo_activo'] ?? 0);
        $usuarioConfirmado = $usuarioResueltoNombre !== '' && $usuarioResueltoCodigo > 0;
        $pasoActual = !$usuarioConfirmado ? 1 : (is_array($reversionPreview ?? null) ? 3 : 2);
        $estadoActual = is_array($reversionAnalisis ?? null) ? ($reversionAnalisis['estado_actual']['valor'] ?? null) : null;
        $estadoActualEtiqueta = is_array($reversionAnalisis ?? null) ? ($reversionAnalisis['estado_actual']['etiqueta'] ?? 'Sin estado') : 'Sin estado';
        $periodoActualEtiqueta = is_array($reversionAnalisis ?? null) ? ($reversionAnalisis['periodo']['etiqueta'] ?? '') : '';
        $destinos = collect($reversionAnalisis['destinos'] ?? []);
        $destinoSeleccionado = $destinos->firstWhere('estado_destino', $estadoDestinoSeleccionado);
        $preview = is_array($reversionPreview ?? null) ? $reversionPreview : null;
        $requiereProgramacion = in_array($estadoDestinoSeleccionado, ['programado', 'ejecutado'], true);
        $requiereEjecucion = $estadoDestinoSeleccionado === 'ejecutado';
        $motivoAnterior = old('motivo', '');
    @endphp

    <main class="wrap">
        <section class="module-header" aria-label="Configuración">
            <div class="module-header-copy">
                <p class="module-header-kicker">Administración</p>
                <h1 class="module-header-title">Configuración</h1>
                <p class="module-header-subtitle">Administración de técnicos y gestión segura del estado actual de cada mantenimiento.</p>
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
                    <p class="muted">Registra el primer técnico para que el módulo de Gestión de estados tenga responsables disponibles.</p>
                </div>
            @endif
        </section>

        <section class="card users-module-shell users-module-shell--head config-section" id="configuracion-reversion">
            <div class="users-toolbar users-toolbar--integrated">
                <div>
                    <p class="section-kicker">Gestión de estados</p>
                    <h2 class="panel-section-title">Gestionar estado del mantenimiento</h2>
                    <p class="muted panel-section-subtitle">Busca un usuario o equipo, revisa su estado vigente y aplica solo transiciones seguras del semestre actual.</p>
                </div>
            </div>

            <div class="config-stepbar" aria-label="Flujo de gestión de estados">
                <span class="config-step {{ $pasoActual === 1 ? 'config-step--current' : 'config-step--done' }}" @if ($pasoActual === 1) aria-current="step" @endif>1. Buscar usuario</span>
                <span class="config-step {{ $pasoActual === 2 ? 'config-step--current' : 'config-step--pending' }}" @if ($pasoActual === 2) aria-current="step" @endif>2. Confirmar objetivo</span>
                <span class="config-step {{ $pasoActual === 3 ? 'config-step--current' : 'config-step--pending' }}" @if ($pasoActual === 3) aria-current="step" @endif>3. Confirmar cambio</span>
            </div>

            <form
                method="GET"
                action="{{ route('panel.configuracion') }}"
                class="panel-live-search state-management-live-search"
                data-config-search-form
                data-config-search-endpoint="{{ route('panel.configuracion.buscar') }}"
                data-config-search-debounce="400"
            >
                <input type="hidden" name="page" value="1">
                <input type="hidden" name="codigo_activo" value="{{ $codigoActivo ?? 0 }}" data-config-search-selected-code>

                <label class="panel-filter-field panel-filter-field--search panel-live-search-field" for="busqueda_usuario">
                    <span>Buscar usuario</span>
                    <div class="panel-live-search-input-wrap">
                        <input
                            id="busqueda_usuario"
                            type="text"
                            name="busqueda_usuario"
                            value="{{ $busquedaUsuario }}"
                            placeholder="Ej: Natalia o 845"
                            autocomplete="off"
                            data-config-search-input
                            role="combobox"
                            aria-autocomplete="list"
                            aria-expanded="false"
                            aria-controls="configuracion-reversion-body"
                        >
                        <span class="panel-live-search-loading" data-config-search-loading hidden>Buscando...</span>
                    </div>
                </label>
            </form>

            <template data-config-search-initial-template>
                @include('panel.partials.configuracion-gestion-estado-body', [
                    'busquedaUsuario' => '',
                    'estadoDestino' => '',
                    'usuarioResuelto' => null,
                    'reversionAnalisis' => null,
                    'reversionPreview' => null,
                    'configSearchState' => 'initial',
                    'configSearchMatches' => [],
                    'tecnicos' => $tecnicos,
                ])
            </template>

            @include('panel.partials.configuracion-gestion-estado-body')

        </section>
    </main>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const form = document.querySelector('[data-config-search-form]');
            if (!form) {
                return;
            }

            const endpoint = form.dataset.configSearchEndpoint || '';
            const input = form.querySelector('[data-config-search-input]');
            const loading = form.querySelector('[data-config-search-loading]');
            const selectedCodeInput = form.querySelector('[data-config-search-selected-code]');
            const initialTemplate = document.querySelector('[data-config-search-initial-template]');
            let body = document.querySelector('[data-config-search-body]');
            let controller = null;
            let debounceTimer = null;
            let sequence = 0;

            if (!input || !body || endpoint === '') {
                return;
            }

            const setLoading = (isLoading) => {
                if (loading) {
                    loading.hidden = !isLoading;
                }
            };

            const restoreInitialBody = () => {
                if (!initialTemplate) {
                    return;
                }

                const wrapper = document.createElement('div');
                wrapper.innerHTML = initialTemplate.innerHTML.trim();
                const nextBody = wrapper.firstElementChild;
                if (!nextBody) {
                    return;
                }

                body.outerHTML = nextBody.outerHTML;
                body = document.querySelector('[data-config-search-body]');
                input.setAttribute('aria-expanded', 'false');
            };

            const shouldSearch = (value) => {
                const trimmed = value.trim();
                if (trimmed === '') {
                    return false;
                }

                if (/^\d+$/.test(trimmed)) {
                    return trimmed.length >= 3;
                }

                return trimmed.length >= 2;
            };

            const replaceBody = (html) => {
                const wrapper = document.createElement('div');
                wrapper.innerHTML = html.trim();
                const nextBody = wrapper.querySelector('[data-config-search-body]') || wrapper.firstElementChild;
                if (!nextBody) {
                    return false;
                }

                body.outerHTML = nextBody.outerHTML;
                body = document.querySelector('[data-config-search-body]');
                input.setAttribute('aria-expanded', body.querySelector('[data-config-search-option]') ? 'true' : 'false');
                return true;
            };

            const focusOptionAt = (index) => {
                if (!body) {
                    return;
                }

                const options = Array.from(body.querySelectorAll('[data-config-search-option]'));
                const target = options[index];
                if (target) {
                    target.focus();
                }
            };

            const runSearch = async ({ query, code = '' }) => {
                const trimmedQuery = query.trim();
                const currentSequence = ++sequence;

                if (controller) {
                    controller.abort();
                }

                controller = new AbortController();
                setLoading(true);

                try {
                    const url = new URL(endpoint, window.location.origin);
                    if (trimmedQuery !== '') {
                        url.searchParams.set('q', trimmedQuery);
                    }
                    if (code !== '') {
                        url.searchParams.set('codigo_activo', code);
                    }

                    const response = await fetch(url.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json',
                            'Cache-Control': 'no-cache',
                        },
                        signal: controller.signal,
                    });

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}`);
                    }

                    const payload = await response.json();
                    if (currentSequence !== sequence) {
                        return;
                    }

                    if (selectedCodeInput) {
                        selectedCodeInput.value = payload.mode === 'resolved'
                            ? String(payload.codigo_activo || '')
                            : '';
                    }

                    replaceBody(String(payload.html || ''));
                } catch (error) {
                    if (error && error.name === 'AbortError') {
                        return;
                    }

                    console.error('No se pudo actualizar la búsqueda de Configuración.', error);
                } finally {
                    if (currentSequence === sequence) {
                        setLoading(false);
                    }
                }
            };

            const scheduleSearch = () => {
                window.clearTimeout(debounceTimer);
                debounceTimer = window.setTimeout(() => {
                    const value = input.value || '';

                    if (!shouldSearch(value)) {
                        if (controller) {
                            controller.abort();
                        }

                        if (selectedCodeInput) {
                            selectedCodeInput.value = '';
                        }

                        restoreInitialBody();
                        setLoading(false);
                        return;
                    }

                    runSearch({ query: value });
                }, Math.max(300, parseInt(form.dataset.configSearchDebounce || '400', 10) || 400));
            };

            input.addEventListener('input', () => {
                if (selectedCodeInput) {
                    selectedCodeInput.value = '';
                }

                scheduleSearch();
            });

            input.addEventListener('keydown', (event) => {
                if (event.key === 'ArrowDown') {
                    const firstOption = body.querySelector('[data-config-search-option]');
                    if (firstOption) {
                        event.preventDefault();
                        firstOption.focus();
                    }
                    return;
                }

                if (event.key === 'Enter') {
                    event.preventDefault();
                    window.clearTimeout(debounceTimer);

                    if (shouldSearch(input.value || '')) {
                        runSearch({ query: input.value || '' });
                    }
                }
            });

            form.addEventListener('submit', (event) => {
                if (!shouldSearch(input.value || '') && !(selectedCodeInput && selectedCodeInput.value !== '')) {
                    return;
                }

                event.preventDefault();
                window.clearTimeout(debounceTimer);

                if (selectedCodeInput && selectedCodeInput.value !== '') {
                    runSearch({ query: input.value || '', code: selectedCodeInput.value });
                    return;
                }

                runSearch({ query: input.value || '' });
            });

            document.addEventListener('click', (event) => {
                const option = event.target.closest('[data-config-search-option]');
                if (!option || !body || !body.contains(option)) {
                    return;
                }

                event.preventDefault();
                const codigoActivo = String(option.dataset.codigoActivo || '').trim();
                const busquedaLabel = String(option.dataset.busquedaLabel || '').trim();

                if (busquedaLabel !== '') {
                    input.value = busquedaLabel;
                }

                if (selectedCodeInput) {
                    selectedCodeInput.value = codigoActivo;
                }

                window.clearTimeout(debounceTimer);
                runSearch({ query: input.value || busquedaLabel, code: codigoActivo });
            });

            document.addEventListener('keydown', (event) => {
                const option = event.target.closest('[data-config-search-option]');
                if (!option || !body || !body.contains(option)) {
                    return;
                }

                const options = Array.from(body.querySelectorAll('[data-config-search-option]'));
                const index = options.indexOf(option);

                if (event.key === 'ArrowDown') {
                    event.preventDefault();
                    focusOptionAt((index + 1) % options.length);
                } else if (event.key === 'ArrowUp') {
                    event.preventDefault();
                    focusOptionAt((index - 1 + options.length) % options.length);
                } else if (event.key === 'Escape') {
                    event.preventDefault();
                    input.focus();
                    input.setAttribute('aria-expanded', 'false');
                } else if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    option.click();
                }
            });
        });
    </script>
</body>
</html>
