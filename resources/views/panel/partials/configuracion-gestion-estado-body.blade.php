@php
    $busquedaUsuario = trim((string) ($busquedaUsuario ?? ''));
    $estadoDestinoSeleccionado = trim((string) old('estado_destino', $estadoDestino ?? ''));
    $usuarioResueltoNombre = trim((string) ($usuarioResuelto['nombre'] ?? ''));
    $usuarioResueltoCodigo = (int) ($usuarioResuelto['codigo_activo'] ?? 0);
    $usuarioConfirmado = $usuarioResueltoNombre !== '' && $usuarioResueltoCodigo > 0;
    $preview = is_array($reversionPreview ?? null) ? $reversionPreview : null;
    $pasoActual = !$usuarioConfirmado ? 1 : ($preview ? 3 : 2);
    $estadoActual = is_array($reversionAnalisis ?? null) ? ($reversionAnalisis['estado_actual']['valor'] ?? null) : null;
    $estadoActualEtiqueta = is_array($reversionAnalisis ?? null) ? ($reversionAnalisis['estado_actual']['etiqueta'] ?? 'Sin estado') : 'Sin estado';
    $periodoActualEtiqueta = is_array($reversionAnalisis ?? null) ? ($reversionAnalisis['periodo']['etiqueta'] ?? '') : '';
    $destinos = collect($reversionAnalisis['destinos'] ?? []);
    $requiereProgramacion = in_array($estadoDestinoSeleccionado, ['programado', 'ejecutado'], true);
    $requiereEjecucion = $estadoDestinoSeleccionado === 'ejecutado';
    $motivoAnterior = old('motivo', '');
    $tecnicosActivos = collect($tecnicos ?? []);
    $configSearchState = (string) ($configSearchState ?? 'initial');
    $configSearchMatches = collect($configSearchMatches ?? []);
    $fechaEjecucionInicial = old('fecha_ejecucion', \Illuminate\Support\Carbon::today()->toDateString());
    $fechaEjecucionEtiqueta = \Illuminate\Support\Carbon::createFromFormat('Y-m-d', $fechaEjecucionInicial)->format('d/m/Y');
    $estadoActualClase = match ($estadoActual) {
        'ejecutado' => 'estado-tabla ejecutado',
        'programado' => 'estado-tabla programado',
        default => 'estado-tabla pendiente',
    };
@endphp

<div id="configuracion-reversion-body" data-config-search-body>
    @if ($configSearchState === 'multiple' && $configSearchMatches->isNotEmpty())
        <div class="config-empty-callout">
            <strong>Se encontraron varios usuarios.</strong>
            <p class="muted">Selecciona uno para cargar su estado vigente y los destinos disponibles sin recargar la página.</p>
        </div>

        <div class="state-management-search-results" role="listbox" aria-label="Coincidencias de usuarios">
            @foreach ($configSearchMatches as $coincidencia)
                <button
                    class="state-management-search-option"
                    type="button"
                    role="option"
                    data-config-search-option
                    data-codigo-activo="{{ (int) ($coincidencia['codigo_activo'] ?? 0) }}"
                    data-busqueda-label="{{ $coincidencia['nombre'] ?? '' }}"
                >
                    <span class="state-management-search-option-main">
                        <span class="state-management-search-option-title">{{ $coincidencia['nombre'] ?? 'Usuario' }}</span>
                        <span class="state-management-search-option-meta">Código activo {{ (int) ($coincidencia['codigo_activo'] ?? 0) }}</span>
                    </span>
                    <span class="{{ !empty($coincidencia['activo']) ? 'chip chip--neutral' : 'estado-tabla estado-tabla-neutral' }}">
                        {{ !empty($coincidencia['activo']) ? 'Activo' : 'Inactivo' }}
                    </span>
                </button>
            @endforeach
        </div>
    @elseif ($busquedaUsuario === '' && !$usuarioConfirmado)
        <div class="config-empty-callout">
            <strong>Busca un usuario para comenzar la gestión de estados.</strong>
            <p class="muted">Puedes usar el nombre o el código activo. El sistema resolverá el usuario antes de permitir cualquier transición.</p>
        </div>
    @elseif (!$usuarioConfirmado)
        <div class="config-empty-callout">
            <strong>No se encontraron usuarios.</strong>
            <p class="muted">Revisa el nombre o el código activo e inténtalo de nuevo.</p>
        </div>
    @else
        <section class="state-management-shell" aria-label="Gestión de estados">
            <section class="state-management-summary" aria-label="Usuario confirmado">
                <div class="state-management-summary-main">
                    <p class="section-kicker">Usuario confirmado</p>
                    <h3>{{ $usuarioResueltoNombre }}</h3>
                    <p class="state-management-summary-intro">Revisa el estado vigente del equipo antes de preparar cualquier transición.</p>
                </div>
                <div class="state-management-summary-meta-list">
                    <div class="state-management-summary-meta">
                        <p class="state-management-summary-label">Código activo</p>
                        <p class="state-management-summary-value">{{ $usuarioResueltoCodigo }}</p>
                        <span class="sr-only">Código activo {{ $usuarioResueltoCodigo }}</span>
                    </div>
                    <div class="state-management-summary-meta">
                        <p class="state-management-summary-label">Semestre vigente</p>
                        <p class="state-management-summary-value">{{ $periodoActualEtiqueta !== '' ? $periodoActualEtiqueta : 'Actual' }}</p>
                    </div>
                            <div class="state-management-summary-meta">
                                <p class="state-management-summary-label">Estado actual</p>
                                <span class="sr-only">Estado actual:</span>
                                <div class="state-management-status">
                                    <span class="{{ $estadoActualClase }}">{{ $estadoActualEtiqueta }}</span>
                                </div>
                    </div>
                </div>
            </section>

            <form method="POST" action="{{ route('panel.configuracion.preview') }}" class="config-revert-form">
                @csrf
                <input type="hidden" name="return_url" value="{{ request()->fullUrl() }}">
                <input type="hidden" name="busqueda_usuario" value="{{ $busquedaUsuario }}">
                <input type="hidden" name="codigo_activo" value="{{ $usuarioResueltoCodigo }}">

                <fieldset class="state-management-options" aria-label="Estados disponibles">
                    <legend class="section-kicker">Selecciona el estado objetivo</legend>
                    @foreach ($destinos as $destino)
                        @php
                            $valorDestino = (string) ($destino['estado_destino'] ?? '');
                            $esActual = (bool) ($destino['es_estado_actual'] ?? false);
                            $seleccionado = $estadoDestinoSeleccionado === $valorDestino;
                            $optionClasses = 'state-management-option';

                            if ($seleccionado) {
                                $optionClasses .= ' state-management-option--selected';
                            }

                            if ($esActual) {
                                $optionClasses .= ' state-management-option--current';
                            }
                        @endphp
                        <label class="{{ $optionClasses }}">
                            <input
                                class="state-management-radio"
                                type="radio"
                                name="estado_destino"
                                value="{{ $valorDestino }}"
                                {{ $seleccionado ? 'checked' : '' }}
                                {{ $esActual ? 'disabled' : '' }}
                            >
                            <span class="state-management-content">
                                <span class="state-management-title">{{ $destino['estado_destino_etiqueta'] ?? 'Estado' }}</span>
                                <span class="state-management-description">{{ $destino['descripcion'] ?? '' }}</span>
                                @if (!empty($destino['motivo_bloqueo']) && !$seleccionado && $esActual)
                                    <span class="field-error">{{ $destino['motivo_bloqueo'] }}</span>
                                @endif
                            </span>
                            <span class="state-management-option-badge {{ $esActual ? 'chip chip--neutral' : 'chip' }}">
                                {{ $esActual ? 'Estado actual' : 'Disponible' }}
                            </span>
                        </label>
                    @endforeach
                </fieldset>

                <div class="state-management-fields">
                    @if ($requiereProgramacion)
                        <div class="field">
                            <label for="fecha_programada">Fecha programada</label>
                            <input id="fecha_programada" type="date" name="fecha_programada" value="{{ old('fecha_programada') }}" required>
                            @error('fecha_programada')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>
                    @endif

                    @if ($requiereEjecucion)
                        <div class="field maintenance-date-field" data-maintenance-date-field>
                            <label id="fecha_ejecucion_label" for="fecha_ejecucion">Fecha de ejecución</label>
                            <input
                                type="hidden"
                                id="fecha_ejecucion"
                                name="fecha_ejecucion"
                                value="{{ $fechaEjecucionInicial }}"
                                data-maintenance-date-input
                                data-maintenance-date-max="{{ \Illuminate\Support\Carbon::today()->toDateString() }}"
                                data-maintenance-date-allow-non-business="1"
                                data-maintenance-date-holidays='@json($festivosCalendario ?? [])'
                                required
                            >
                            <button
                                type="button"
                                class="maintenance-date-trigger"
                                data-maintenance-date-trigger
                                aria-haspopup="dialog"
                                aria-expanded="false"
                                aria-controls="maintenance-date-picker-config"
                                aria-labelledby="fecha_ejecucion_label"
                            >
                                <span class="maintenance-date-trigger-text" data-maintenance-date-display>{{ $fechaEjecucionEtiqueta }}</span>
                                <span class="maintenance-date-trigger-icon" aria-hidden="true">
                                    <svg viewBox="0 0 24 24" focusable="false" aria-hidden="true">
                                        <path d="M7 3v3M17 3v3M4 8h16M5 5h14a1 1 0 0 1 1 1v13a1 1 0 0 1-1 1H5a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"></path>
                                    </svg>
                                </span>
                            </button>

                            <div class="maintenance-date-picker" id="maintenance-date-picker-config" data-maintenance-date-picker hidden>
                                <div class="maintenance-date-picker-header">
                                    <button type="button" class="maintenance-date-picker-nav" data-maintenance-date-prev aria-label="Mes anterior">&lsaquo;</button>
                                    <div class="maintenance-date-picker-title-block">
                                        <div class="maintenance-date-picker-title" data-maintenance-date-month></div>
                                        <label class="maintenance-date-picker-year-label" for="maintenance-date-year-config">Año</label>
                                        <select id="maintenance-date-year-config" class="maintenance-date-picker-year" data-maintenance-date-year></select>
                                    </div>
                                    <button type="button" class="maintenance-date-picker-nav" data-maintenance-date-next aria-label="Mes siguiente">&rsaquo;</button>
                                </div>

                                <div class="maintenance-date-picker-weekdays" aria-hidden="true">
                                    <span>L</span>
                                    <span>M</span>
                                    <span>X</span>
                                    <span>J</span>
                                    <span>V</span>
                                    <span>S</span>
                                    <span>D</span>
                                </div>

                                <div class="maintenance-date-picker-grid" data-maintenance-date-grid></div>

                                <div class="maintenance-date-picker-footer">
                                    <button type="button" class="btn btn-ghost maintenance-date-picker-today" data-maintenance-date-today>Hoy</button>
                                    <button type="button" class="btn btn-ghost maintenance-date-picker-close" data-maintenance-date-close>Listo</button>
                                </div>
                            </div>
                            <p class="field-help">Puedes registrar la fecha real en la que se realizó el mantenimiento.</p>
                            @error('fecha_ejecucion')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="field">
                            <label for="tecnico_id">Técnico responsable</label>
                            <select id="tecnico_id" name="tecnico_id" required>
                                <option value="">Selecciona un técnico</option>
                                @foreach ($tecnicosActivos as $tecnico)
                                    <option value="{{ $tecnico['id'] ?? '' }}" {{ (string) old('tecnico_id') === (string) ($tecnico['id'] ?? '') ? 'selected' : '' }}>
                                        {{ $tecnico['nombre'] ?? 'Técnico' }}
                                    </option>
                                @endforeach
                            </select>
                            @error('tecnico_id')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="field maintenance-modal-full">
                            <label for="observaciones">Observaciones</label>
                            <textarea id="observaciones" name="observaciones" rows="3" placeholder="Detalle técnico o contexto adicional">{{ old('observaciones') }}</textarea>
                            @error('observaciones')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>
                    @endif

                    <div class="field-actions state-management-actions">
                        <button class="btn btn-primary" type="submit">Generar previsualización</button>
                    </div>
                </div>

                <div class="config-revert-note">
                    El sistema solo aplicará transiciones compatibles con el semestre vigente y con los registros reales en <code>mantenimientos</code>.
                </div>
            </form>

            @if ($preview)
                <section class="card panel-card state-management-preview">
                    <div class="panel-card-header">
                        <div class="panel-title-block">
                            <p class="section-kicker">Previsualización</p>
                            <h3 class="panel-section-title">Cambio preparado</h3>
                            <p class="muted panel-section-subtitle">Revisa el impacto antes de confirmar. Esta vista aún no modifica datos.</p>
                        </div>
                    </div>

                    <div class="state-management-preview-grid">
                        <div class="state-management-preview-item"><strong>Usuario</strong><span>{{ $preview['usuario']['nombre'] ?? '-' }}</span></div>
                        <div class="state-management-preview-item"><strong>Código activo</strong><span>{{ $preview['usuario']['codigo_activo'] ?? '-' }}</span></div>
                        <div class="state-management-preview-item"><strong>Estado actual</strong><span>{{ $preview['estado_actual']['etiqueta'] ?? '-' }}</span></div>
                        <div class="state-management-preview-item"><strong>Estado destino</strong><span>{{ $preview['estado_destino']['etiqueta'] ?? '-' }}</span></div>
                        <div class="state-management-preview-item"><strong>Semestre</strong><span>{{ $preview['periodo']['etiqueta'] ?? '-' }}</span></div>
                        <div class="state-management-preview-item"><strong>Mantenimiento objetivo</strong><span>#{{ $preview['mantenimiento_objetivo']['id'] ?? 0 }}</span></div>
                    </div>

                    @if (!empty($preview['datos_ingresados']))
                        <div class="state-management-preview-list">
                            @foreach ($preview['datos_ingresados'] as $dato)
                                <div class="state-management-preview-item"><strong>{{ $dato['campo'] ?? 'Dato' }}</strong><span>{{ $dato['valor'] ?? '-' }}</span></div>
                            @endforeach
                        </div>
                    @endif

                    <div class="state-management-preview-grid">
                        <div class="state-management-preview-item"><strong>Registros creados</strong><span>{{ count($preview['registros_creados'] ?? []) }}</span></div>
                        <div class="state-management-preview-item"><strong>Registros actualizados</strong><span>{{ count($preview['registros_actualizados'] ?? []) }}</span></div>
                        <div class="state-management-preview-item"><strong>Registros eliminados</strong><span>{{ count($preview['registros_eliminados'] ?? []) }}</span></div>
                        <div class="state-management-preview-item"><strong>Resúmenes a recalcular</strong><span>{{ count($preview['resumenes_recalculados'] ?? []) }}</span></div>
                    </div>

                    <div class="config-revert-note">
                        {{ $preview['consecuencia_funcional'] ?? '' }}
                    </div>

                    @if (!empty($preview['advertencias']))
                        <div class="config-empty-callout">
                            <strong>Advertencias</strong>
                            @foreach ($preview['advertencias'] as $advertencia)
                                <p class="muted">{{ $advertencia }}</p>
                            @endforeach
                        </div>
                    @endif

                    <form method="POST" action="{{ route('panel.configuracion.gestionar') }}" enctype="multipart/form-data" class="config-revert-form">
                        @csrf
                        <input type="hidden" name="return_url" value="{{ request()->fullUrl() }}">
                        <input type="hidden" name="busqueda_usuario" value="{{ $busquedaUsuario }}">
                        <input type="hidden" name="codigo_activo" value="{{ $usuarioResueltoCodigo }}">
                        <input type="hidden" name="estado_destino" value="{{ $estadoDestinoSeleccionado }}">
                        <input type="hidden" name="preview_hash" value="{{ $preview['hash'] ?? '' }}">
                        <input type="hidden" name="fecha_programada" value="{{ old('fecha_programada') }}">
                        <input type="hidden" name="fecha_ejecucion" value="{{ old('fecha_ejecucion') }}">
                        <input type="hidden" name="tecnico_id" value="{{ old('tecnico_id') }}">
                        <input type="hidden" name="observaciones" value="{{ old('observaciones') }}">

                        @if ($requiereEjecucion)
                            <div class="field">
                                <label for="constancia_pdf">Constancia PDF</label>
                                <input id="constancia_pdf" type="file" name="constancia_pdf" accept="application/pdf" required>
                                @error('constancia_pdf')
                                    <span class="field-error">{{ $message }}</span>
                                @enderror
                            </div>
                        @endif

                        @if (($preview['metadata']['requiere_confirmacion_fecha_ejecucion_antes_programacion'] ?? false) === true)
                            <div class="field">
                                <label class="maintenance-modal-checkbox">
                                    <input type="checkbox" name="confirmar_ejecucion_antes_programacion" value="1" {{ old('confirmar_ejecucion_antes_programacion') ? 'checked' : '' }} required>
                                    <span>Confirmo que la fecha de ejecución es anterior a la fecha programada y que la información es correcta.</span>
                                </label>
                            </div>
                        @endif

                        <div class="field">
                            <label for="motivo">Motivo obligatorio</label>
                            <textarea id="motivo" name="motivo" rows="3" required placeholder="Explica por qué se realizará este cambio de estado">{{ $motivoAnterior }}</textarea>
                            @error('motivo')
                                <span class="field-error">{{ $message }}</span>
                            @enderror
                        </div>

                        <div class="field-actions state-management-actions">
                            <button class="btn btn-primary" type="submit">Confirmar cambio de estado</button>
                        </div>
                    </form>
                </section>
            @endif
        </section>
    @endif
</div>
