<!doctype html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Usuarios</title>
    @include('panel.partials.dashboard-modern-styles')
    @include('panel.partials.hechos-saas-overrides')
    @include('panel.partials.usuarios-saas-overrides')
</head>
<body class="dashboard-modern">
    @include('panel.partials.topbar-saas', [
        'activeRoute' => 'panel.usuarios',
        'periodoConsulta' => $periodoConsulta ?? null,
        'showModuleContext' => false,
    ])

    <main class="wrap">
        <section class="module-header" aria-label="Usuarios">
            <div class="module-header-copy">
                <p class="module-header-kicker">Administraci&oacute;n</p>
                <h1 class="module-header-title">Usuarios</h1>
                <p class="module-header-subtitle">Administraci&oacute;n de usuarios, activos y gesti&oacute;n de estados.</p>
            </div>
        </section>

        @php
            $usuariosObjeto = $usuarios ?? null;

            $usuariosFuente = is_object($usuariosObjeto) && method_exists($usuariosObjeto, 'items')
                ? $usuariosObjeto->items()
                : (is_array($usuariosObjeto) ? $usuariosObjeto : []);

            $sinArea = html_entity_decode('Sin &aacute;rea', ENT_QUOTES, 'UTF-8');
            $usuariosAgrupados = collect($usuariosFuente)
                ->groupBy(function ($fila) use ($sinArea) {
                    $area = trim((string) ($fila['area_nombre'] ?? $sinArea));

                    return $area !== '' ? $area : $sinArea;
                })
                ->sortKeys(SORT_NATURAL | SORT_FLAG_CASE);

            $presentarTexto = static function (?string $valor): string {
                $valor = trim((string) $valor);

                if ($valor === '') {
                    return '-';
                }

                if (preg_match('/^[A-Z0-9\s]+$/u', $valor) && mb_strlen($valor, 'UTF-8') <= 5) {
                    return $valor;
                }

                return mb_convert_case(mb_strtolower($valor, 'UTF-8'), MB_CASE_TITLE, 'UTF-8');
            };

            $totalUsuarios = is_object($usuariosObjeto) && method_exists($usuariosObjeto, 'total')
                ? (int) $usuariosObjeto->total()
                : count($usuariosFuente);

            $editarId = (int) ($usuarioEdicion['id'] ?? 0);
            $busquedaActual = $busqueda ?? '';
            $placeholderUsuarios = html_entity_decode('Nombre, c&oacute;digo de activo, serial o &aacute;rea', ENT_QUOTES, 'UTF-8');
            $abrirCrearUsuario = old('form_context') === 'crear_usuario';
            $fechaBaseCrear = old('fecha_ultimo_mantenimiento_base', '');
            $fechaBaseEditarAlmacenada = trim((string) ($usuarioEdicion['fecha_ultimo_mantenimiento_base'] ?? ''));
            $fechaUltimaResumenEditar = trim((string) ($usuarioEdicion['ultima_fecha_mantenimiento'] ?? ''));
            $fechaBaseEditar = old(
                'fecha_ultimo_mantenimiento_base',
                $fechaBaseEditarAlmacenada !== '' ? $fechaBaseEditarAlmacenada : $fechaUltimaResumenEditar
            );
            $mostrarFechaCrear = old('tipo_equipo', 'normal') === 'normal';
        @endphp

        @if (session('panel_message'))
            <section class="card">
                <div class="notice">{{ session('panel_message') }}</div>
            </section>
        @endif

        @if (session('panel_error'))
            <section class="card">
                <div class="notice error">{{ session('panel_error') }}</div>
            </section>
        @endif

        <section class="card users-module-shell users-module-shell--head">
            <div class="users-toolbar users-toolbar--integrated">
                <div>
                    <p class="section-kicker">Gesti&oacute;n</p>
                    <h2 class="panel-section-title">Buscar usuarios</h2>
                    <p class="muted panel-section-subtitle">Busca por nombre, c&oacute;digo de activo, serial o &aacute;rea.</p>
                </div>
                <div class="users-toolbar-actions">
                    <button class="btn btn-primary" type="button" data-user-create-open>+ Crear usuario</button>
                </div>
            </div>

            <x-panel.live-search
                action="{{ route('panel.usuarios') }}"
                name="q_usuarios"
                label="Buscar usuarios"
                placeholder="{{ $placeholderUsuarios }}"
                value="{{ $busquedaActual }}"
                target="#usuarios-listado"
                debounce="500"
                loading-label="Buscando..."
            />
        </section>

        <div class="user-modal-shell" data-user-create-modal @if($abrirCrearUsuario) aria-hidden="false" @else hidden aria-hidden="true" @endif>
            <div class="user-modal-backdrop" data-user-create-close></div>
            <div class="user-modal-panel" role="dialog" aria-modal="true" aria-labelledby="crear-usuario-title" tabindex="-1">
                <div class="user-modal-header">
                    <div>
                        <p class="section-kicker">Nuevo registro</p>
                        <h2 id="crear-usuario-title" class="panel-section-title-lg">Crear usuario</h2>
                        <p class="muted panel-section-subtitle">El formulario conserva exactamente los mismos campos, nombres y acci&oacute;n del backend.</p>
                    </div>
                    <button type="button" class="btn btn-ghost" data-user-create-close>Cerrar</button>
                </div>

                <form method="POST" action="{{ route('panel.usuarios.crear') }}">
                    @csrf
                    <input type="hidden" name="filtro_q_usuarios" value="{{ $busquedaActual }}">
                    <input type="hidden" name="return_url" value="{{ request()->fullUrl() }}">
                    <input type="hidden" name="form_context" value="crear_usuario">

                    <div class="user-modal-grid">
                        <label>
                            <span>Nombre</span>
                            <input id="nombre" name="nombre" value="{{ old('nombre') }}" @error('nombre') aria-invalid="true" @enderror required>
                            @error('nombre')<span class="field-error">{{ $message }}</span>@enderror
                        </label>
                        <label>
                            <span>C&oacute;digo activo</span>
                            <input id="codigo_activo" name="codigo_activo" type="number" min="1" value="{{ old('codigo_activo') }}" @error('codigo_activo') aria-invalid="true" @enderror required>
                            @error('codigo_activo')<span class="field-error">{{ $message }}</span>@enderror
                        </label>
                        <label>
                            <span>Serial</span>
                            <input id="serial_equipo" name="serial_equipo" value="{{ old('serial_equipo') }}" @error('serial_equipo') aria-invalid="true" @enderror required>
                            @error('serial_equipo')<span class="field-error">{{ $message }}</span>@enderror
                        </label>
                        <label>
                            <span>&Aacute;rea</span>
                            <select id="area_id_create" name="area_id" @error('area_id') aria-invalid="true" @enderror>
                                <option value="">Sin &aacute;rea</option>
                                @foreach ($areas as $area)
                                    <option value="{{ $area['id'] }}" @selected((int) old('area_id') === (int) $area['id'])>{{ $presentarTexto($area['nombre']) }}</option>
                                @endforeach
                            </select>
                            @error('area_id')<span class="field-error">{{ $message }}</span>@enderror
                        </label>
                        <label class="field-wide">
                            <span>Tipo</span>
                            <select id="tipo_equipo" name="tipo_equipo" data-user-equipo-type-select @error('tipo_equipo') aria-invalid="true" @enderror>
                                <option value="normal" @selected(old('tipo_equipo', 'normal') === 'normal')>Viejo</option>
                                <option value="nuevo" @selected(old('tipo_equipo') === 'nuevo')>Nuevo</option>
                            </select>
                            @error('tipo_equipo')<span class="field-error">{{ $message }}</span>@enderror
                        </label>
                        <div class="field-wide user-maintenance-base-field" data-user-last-maintenance-field @if(!$mostrarFechaCrear) hidden aria-hidden="true" @endif>
                            <label for="fecha_ultimo_mantenimiento_base_crear">Fecha de &uacute;ltimo mantenimiento</label>
                            <input
                                type="date"
                                id="fecha_ultimo_mantenimiento_base_crear"
                                name="fecha_ultimo_mantenimiento_base"
                                value="{{ $fechaBaseCrear }}"
                                data-user-last-maintenance-input
                                @if(!$mostrarFechaCrear) disabled @endif
                                @error('fecha_ultimo_mantenimiento_base') aria-invalid="true" @enderror
                            >
                            @error('fecha_ultimo_mantenimiento_base')<span class="field-error">{{ $message }}</span>@enderror
                        </div>
                        <div class="field-actions">
                            <button class="btn btn-primary" type="submit">Crear usuario</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        @if ($usuarioEdicion)
            <div
                class="user-modal-shell"
                data-user-edit-modal
                data-modal-close-url="{{ route('panel.usuarios', ['q_usuarios' => $busquedaActual]) }}"
                aria-hidden="false"
            >
                <div class="user-modal-backdrop" data-user-edit-close></div>
                <div class="user-modal-panel" role="dialog" aria-modal="true" aria-labelledby="editar-usuario-title" tabindex="-1">
                    <div class="user-modal-header">
                        <div>
                            <p class="section-kicker">Edici&oacute;n</p>
                            <h2 id="editar-usuario-title" class="panel-section-title-lg">Editar usuario</h2>
                            <p class="muted panel-section-subtitle">Mant&eacute;n los datos del usuario y guarda los cambios sin perder el contexto actual.</p>
                        </div>
                        <button type="button" class="btn btn-ghost" data-user-edit-close>Cerrar</button>
                    </div>

                    <form method="POST" action="{{ route('panel.usuarios.actualizar') }}">
                        @csrf
                        <input type="hidden" name="usuario_id" value="{{ $usuarioEdicion['id'] }}">
                        <input type="hidden" name="filtro_q_usuarios" value="{{ $busquedaActual }}">
                        <input type="hidden" name="return_url" value="{{ route('panel.usuarios', ['q_usuarios' => $busquedaActual]) }}">
                        <input type="hidden" name="form_context" value="editar_usuario">

                        <div class="user-modal-grid">
                            <label>
                                <span>Nombre</span>
                                <input id="editar_nombre" name="nombre" value="{{ old('nombre', $usuarioEdicion['nombre']) }}" @error('nombre') aria-invalid="true" @enderror required>
                                @error('nombre')<span class="field-error">{{ $message }}</span>@enderror
                            </label>
                            <label>
                                <span>C&oacute;digo activo</span>
                                <input id="editar_codigo_activo" name="codigo_activo" type="number" min="1" value="{{ old('codigo_activo', $usuarioEdicion['codigo_activo']) }}" @error('codigo_activo') aria-invalid="true" @enderror required>
                                @error('codigo_activo')<span class="field-error">{{ $message }}</span>@enderror
                            </label>
                            <label>
                                <span>Serial</span>
                                <input id="editar_serial_equipo" name="serial_equipo" value="{{ old('serial_equipo', $usuarioEdicion['serial_equipo']) }}" @error('serial_equipo') aria-invalid="true" @enderror required>
                                @error('serial_equipo')<span class="field-error">{{ $message }}</span>@enderror
                            </label>
                            <label>
                                <span>&Aacute;rea</span>
                                <select id="editar_area_id" name="area_id" @error('area_id') aria-invalid="true" @enderror>
                                    <option value="">Sin &aacute;rea</option>
                                    @foreach ($areas as $area)
                                        <option value="{{ $area['id'] }}" @selected((int) old('area_id', $usuarioEdicion['area_id']) === (int) $area['id'])>
                                            {{ $presentarTexto($area['nombre']) }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('area_id')<span class="field-error">{{ $message }}</span>@enderror
                            </label>
                            <label class="field-wide">
                                <span>Tipo</span>
                                <select id="editar_tipo_equipo" name="tipo_equipo" data-user-equipo-type-select @error('tipo_equipo') aria-invalid="true" @enderror>
                                    <option value="normal" @selected(old('tipo_equipo', $usuarioEdicion['tipo_equipo']) === 'normal')>Viejo</option>
                                    <option value="nuevo" @selected(old('tipo_equipo', $usuarioEdicion['tipo_equipo']) === 'nuevo')>Nuevo</option>
                                </select>
                                @error('tipo_equipo')<span class="field-error">{{ $message }}</span>@enderror
                            </label>
                            <div class="field-wide user-maintenance-base-field" data-user-last-maintenance-field>
                                <label for="fecha_ultimo_mantenimiento_base_editar">Fecha de &uacute;ltimo mantenimiento</label>
                                <input
                                    type="date"
                                    id="fecha_ultimo_mantenimiento_base_editar"
                                    name="fecha_ultimo_mantenimiento_base"
                                    value="{{ $fechaBaseEditar }}"
                                    data-user-last-maintenance-input
                                    @error('fecha_ultimo_mantenimiento_base') aria-invalid="true" @enderror
                                >
                                @error('fecha_ultimo_mantenimiento_base')<span class="field-error">{{ $message }}</span>@enderror
                            </div>
                            <div class="field-actions">
                                <button class="btn btn-primary" type="submit">Guardar cambios</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        @endif

        @include('panel.partials.usuarios-listado')
    </main>
</body>
</html>
