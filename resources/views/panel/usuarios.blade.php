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
                <p class="module-header-kicker">Administración</p>
                <h1 class="module-header-title">Usuarios</h1>
                <p class="module-header-subtitle">Administración de usuarios, activos y gestión de estados.</p>
            </div>
        </section>

        @php
            $usuariosObjeto = $usuarios ?? null;

            $usuariosFuente = is_object($usuariosObjeto) && method_exists($usuariosObjeto, 'items')
                ? $usuariosObjeto->items()
                : (is_array($usuariosObjeto) ? $usuariosObjeto : []);

            $usuariosAgrupados = collect($usuariosFuente)
                ->groupBy(function ($fila) {
                    $area = trim((string) ($fila['area_nombre'] ?? 'Sin área'));

                    return $area !== '' ? $area : 'Sin área';
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
                    <p class="section-kicker">Gestión</p>
                    <h2 class="panel-section-title">Buscar usuarios</h2>
                    <p class="muted panel-section-subtitle">Busca por nombre, código de activo, serial o área.</p>
                </div>
                <div class="users-toolbar-actions">
                    <button class="btn btn-primary" type="button" data-user-create-open>+ Crear usuario</button>
                </div>
            </div>

            <x-panel.live-search
                action="{{ route('panel.usuarios') }}"
                name="q_usuarios"
                label="Buscar usuarios"
                placeholder="Nombre, código de activo, serial o área"
                value="{{ $busquedaActual }}"
                target="#usuarios-listado"
                debounce="500"
                loading-label="Buscando..."
            />
        </section>

        <div class="user-modal-shell" data-user-create-modal hidden aria-hidden="true">
            <div class="user-modal-backdrop" data-user-create-close></div>
            <div class="user-modal-panel" role="dialog" aria-modal="true" aria-labelledby="crear-usuario-title" tabindex="-1">
                <div class="user-modal-header">
                    <div>
                        <p class="section-kicker">Nuevo registro</p>
                        <h2 id="crear-usuario-title" class="panel-section-title-lg">Crear usuario</h2>
                        <p class="muted panel-section-subtitle">El formulario conserva exactamente los mismos campos, nombres y acción del backend.</p>
                    </div>
                    <button type="button" class="btn btn-ghost" data-user-create-close>Cerrar</button>
                </div>

                <form method="POST" action="{{ route('panel.usuarios.crear') }}">
                    @csrf
                    <input type="hidden" name="filtro_q_usuarios" value="{{ $busquedaActual }}">
                    <input type="hidden" name="return_url" value="{{ request()->fullUrl() }}">

                    <div class="user-modal-grid">
                        <label>
                            <span>Nombre</span>
                            <input id="nombre" name="nombre" required>
                        </label>
                        <label>
                            <span>Código activo</span>
                            <input id="codigo_activo" name="codigo_activo" type="number" min="1" required>
                        </label>
                        <label>
                            <span>Serial</span>
                            <input id="serial_equipo" name="serial_equipo" required>
                        </label>
                        <label>
                            <span>Área</span>
                            <select id="area_id_create" name="area_id">
                                <option value="">Sin área</option>
                                @foreach ($areas as $area)
                                    <option value="{{ $area['id'] }}">{{ $presentarTexto($area['nombre']) }}</option>
                                @endforeach
                            </select>
                        </label>
                        <label class="field-wide">
                            <span>Tipo</span>
                            <select id="tipo_equipo" name="tipo_equipo">
                                <option value="normal">Normal</option>
                                <option value="nuevo">Nuevo</option>
                            </select>
                        </label>
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
                            <p class="section-kicker">Edición</p>
                            <h2 id="editar-usuario-title" class="panel-section-title-lg">Editar usuario</h2>
                            <p class="muted panel-section-subtitle">Mantén los datos del usuario y guarda los cambios sin perder el contexto actual.</p>
                        </div>
                        <button type="button" class="btn btn-ghost" data-user-edit-close>Cerrar</button>
                    </div>

                    <form method="POST" action="{{ route('panel.usuarios.actualizar') }}">
                        @csrf
                        <input type="hidden" name="usuario_id" value="{{ $usuarioEdicion['id'] }}">
                        <input type="hidden" name="filtro_q_usuarios" value="{{ $busquedaActual }}">
                        <input type="hidden" name="return_url" value="{{ route('panel.usuarios', ['q_usuarios' => $busquedaActual]) }}">

                        <div class="user-modal-grid">
                            <label>
                                <span>Nombre</span>
                                <input id="editar_nombre" name="nombre" value="{{ old('nombre', $usuarioEdicion['nombre']) }}" required>
                            </label>
                            <label>
                                <span>Código activo</span>
                                <input id="editar_codigo_activo" name="codigo_activo" type="number" min="1" value="{{ old('codigo_activo', $usuarioEdicion['codigo_activo']) }}" required>
                            </label>
                            <label>
                                <span>Serial</span>
                                <input id="editar_serial_equipo" name="serial_equipo" value="{{ old('serial_equipo', $usuarioEdicion['serial_equipo']) }}" required>
                            </label>
                            <label>
                                <span>Área</span>
                                <select id="editar_area_id" name="area_id">
                                    <option value="">Sin área</option>
                                    @foreach ($areas as $area)
                                        <option value="{{ $area['id'] }}" @selected((int) old('area_id', $usuarioEdicion['area_id']) === (int) $area['id'])>
                                            {{ $presentarTexto($area['nombre']) }}
                                        </option>
                                    @endforeach
                                </select>
                            </label>
                            <label class="field-wide">
                                <span>Tipo</span>
                                <select id="editar_tipo_equipo" name="tipo_equipo">
                                    <option value="normal" @selected(old('tipo_equipo', $usuarioEdicion['tipo_equipo']) === 'normal')>Normal</option>
                                    <option value="nuevo" @selected(old('tipo_equipo', $usuarioEdicion['tipo_equipo']) === 'nuevo')>Nuevo</option>
                                </select>
                            </label>
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

