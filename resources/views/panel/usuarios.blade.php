<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Usuarios</title>
    <style>[x-cloak]{display:none!important;}</style>
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

    <main class="wrap" x-data="{ crearUsuarioOpen: false }" @keydown.escape.window="crearUsuarioOpen = false">
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
            $areaFiltroActual = $areaId ?? 0;
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
                    <h2 style="margin: 0 0 6px; color: #0f172a; font-size: 1.15rem; line-height: 1.25; font-weight: 800;">Buscar y filtrar</h2>
                    <p class="muted" style="margin: 0; color: #64748b;">Busca por nombre, código activo o serial y filtra por área.</p>
                </div>
                <div class="users-toolbar-actions">
                    <button class="btn btn-primary" type="button" @click="crearUsuarioOpen = true">+ Crear usuario</button>
                    <a class="btn-link btn-ghost" href="{{ route('panel.usuarios') }}">Restablecer</a>
                </div>
            </div>

            <form class="users-filter-strip" method="GET" action="{{ route('panel.usuarios') }}">
                <div class="row">
                    <div>
                        <label for="area_id">Área</label>
                        <select id="area_id" name="area_id">
                            <option value="0">Todas</option>
                            @foreach ($areas as $area)
                                <option value="{{ $area['id'] }}" @selected((int) $areaFiltroActual === (int) $area['id'])>
                                    {{ $presentarTexto($area['nombre']) }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="q_usuarios">Búsqueda</label>
                        <input id="q_usuarios" name="q_usuarios" value="{{ $busquedaActual }}" placeholder="Nombre, código activo o serial">
                    </div>
                    <div>
                        <label>&nbsp;</label>
                        <button class="btn" type="submit">Aplicar filtros</button>
                    </div>
                </div>
            </form>
        </section>

        <div class="user-modal-shell" x-cloak x-show="crearUsuarioOpen" x-transition.opacity>
            <div class="user-modal-backdrop" @click="crearUsuarioOpen = false"></div>
            <div class="user-modal-dialog" role="dialog" aria-modal="true" aria-labelledby="crear-usuario-title">
                <div class="user-modal-header">
                    <div>
                        <p class="section-kicker">Nuevo registro</p>
                        <h2 id="crear-usuario-title" style="margin: 0 0 6px; color: #0f172a; font-size: 1.2rem; line-height: 1.2; font-weight: 800;">Crear usuario</h2>
                        <p class="muted" style="margin: 0; color: #64748b;">El formulario conserva exactamente los mismos campos, nombres y acción del backend.</p>
                    </div>
                    <button type="button" class="btn btn-ghost" @click="crearUsuarioOpen = false">Cerrar</button>
                </div>

                <form method="POST" action="{{ route('panel.usuarios.crear') }}">
                    @csrf
                    <input type="hidden" name="filtro_area_id" value="{{ $areaFiltroActual }}">
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
            <section class="card">
                <div class="users-toolbar">
                    <div>
                        <p class="section-kicker">Edición</p>
                        <h2 style="margin: 0 0 6px; color: #0f172a; font-size: 1.15rem; line-height: 1.25; font-weight: 800;">Editar usuario</h2>
                        <p class="muted" style="margin: 0; color: #64748b;">Mantén los datos del usuario y guarda los cambios sin perder el contexto actual.</p>
                    </div>
                    <a class="btn-link btn-ghost" href="{{ route('panel.usuarios', ['area_id' => $areaFiltroActual, 'q_usuarios' => $busquedaActual]) }}">Cerrar edición</a>
                </div>

                <form method="POST" action="{{ route('panel.usuarios.actualizar') }}">
                    @csrf
                    <input type="hidden" name="usuario_id" value="{{ $usuarioEdicion['id'] }}">
                    <input type="hidden" name="filtro_area_id" value="{{ $areaFiltroActual }}">
                    <input type="hidden" name="filtro_q_usuarios" value="{{ $busquedaActual }}">
                    <input type="hidden" name="editar_usuario_id" value="{{ $usuarioEdicion['id'] }}">
                    <input type="hidden" name="return_url" value="{{ request()->fullUrl() }}">

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
            </section>
        @endif

        <section class="card users-module-shell users-module-shell--table">
            <div class="topline" style="margin-bottom: 12px;">
                <div>
                    <p class="section-kicker">Listado</p>
                    <h2 style="margin: 0 0 6px; color: #0f172a; font-size: 1.15rem; line-height: 1.25; font-weight: 800;">Usuarios por área</h2>
                    <p class="muted" style="margin: 0; color: #64748b;">La agrupación es visual y no altera el comportamiento del backend.</p>
                </div>
                <div class="users-group-count">{{ $totalUsuarios }} registro(s)</div>
            </div>

            <div class="tabla-premium-wrap">
                <table class="tabla-premium user-table">
                    <thead>
                        <tr>
                            <th>Usuario</th>
                            <th>Código</th>
                            <th>Serial</th>
                            <th>Área</th>
                            <th>Tipo</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    @forelse ($usuariosAgrupados as $areaNombre => $grupoUsuarios)
                        <tbody class="users-group-shell" x-data="{ open: true }" :data-open="open">
                            @php $grupoCount = count($grupoUsuarios); @endphp
                            <tr class="group-row">
                                <td colspan="7">
                                    <button type="button" class="users-group-toggle" @click="open = !open" :aria-expanded="open">
                                        <span class="users-group-toggle-main">
                                            <span class="users-group-caret"></span>
                                            <span>
                                                <h3 class="users-group-title">{{ $presentarTexto($areaNombre) }}</h3>
                                                <span class="users-group-count">{{ $grupoCount }} usuario(s)</span>
                                            </span>
                                        </span>
                                    </button>
                                </td>
                            </tr>
                            @foreach ($grupoUsuarios as $fila)
                                @php
                                    $estadoActivo = (int) ($fila['estado_usuario'] ?? 0) === 1;
                                    $tipoEquipo = (string) ($fila['tipo_equipo'] ?? 'normal');
                                    $areaFila = $presentarTexto($fila['area_nombre'] ?? 'Sin área');
                                    $nombreFila = $presentarTexto($fila['nombre'] ?? '');
                                    $codigoFila = (string) ($fila['codigo_activo'] ?? '');
                                    $serialFila = $presentarTexto($fila['serial_equipo'] ?? '');
                                @endphp
                                <tr class="user-record" x-show="open" x-transition.opacity.duration.150ms>
                                    <td>
                                        <span class="user-row-name">{{ $nombreFila }}</span>
                                    </td>
                                    <td>{{ $codigoFila }}</td>
                                    <td>{{ $serialFila }}</td>
                                    <td><span class="user-badge-area">{{ $areaFila }}</span></td>
                                    <td>
                                        <span class="user-badge-type user-badge-type--{{ $tipoEquipo === 'nuevo' ? 'nuevo' : 'normal' }}">{{ $presentarTexto($tipoEquipo) }}</span>
                                    </td>
                                    <td>
                                        <span class="user-badge-state user-badge-state--{{ $estadoActivo ? 'activo' : 'inactivo' }}">{{ $estadoActivo ? 'Activo' : 'Inactivo' }}</span>
                                    </td>
                                    <td>
                                        <div class="user-actions" x-data="{ open: false }" @click.outside="open = false">
                                            <button
                                                class="user-actions-trigger"
                                                type="button"
                                                @click="open = !open"
                                                :aria-expanded="open"
                                                aria-label="Abrir acciones"
                                            >
                                                <span></span>
                                                <span></span>
                                                <span></span>
                                            </button>
                                            <div class="user-actions-panel" x-cloak x-show="open" x-transition.opacity.scale.95>
                                                <a class="user-action-item" href="{{ route('panel.usuarios', ['area_id' => $areaFiltroActual, 'q_usuarios' => $busquedaActual, 'editar_usuario_id' => $fila['id']]) }}">Editar usuario</a>
                                                <form method="POST" action="{{ route('panel.usuarios.estado') }}">
                                                    @csrf
                                                    <input type="hidden" name="usuario_id" value="{{ $fila['id'] }}">
                                                    <input type="hidden" name="nuevo_estado" value="{{ $estadoActivo ? 0 : 1 }}">
                                                    <input type="hidden" name="filtro_area_id" value="{{ $areaFiltroActual }}">
                                                    <input type="hidden" name="filtro_q_usuarios" value="{{ $busquedaActual }}">
                                                    <input type="hidden" name="return_url" value="{{ request()->fullUrl() }}">
                                                    <button class="user-action-item" type="submit">{{ $estadoActivo ? 'Desactivar usuario' : 'Activar usuario' }}</button>
                                                </form>
                                                <form method="POST" action="{{ route('panel.usuarios.eliminar') }}" onsubmit="return confirm('¿Seguro que deseas eliminar este usuario?');">
                                                    @csrf
                                                    <input type="hidden" name="usuario_id" value="{{ $fila['id'] }}">
                                                    <input type="hidden" name="filtro_area_id" value="{{ $areaFiltroActual }}">
                                                    <input type="hidden" name="filtro_q_usuarios" value="{{ $busquedaActual }}">
                                                    <input type="hidden" name="return_url" value="{{ request()->fullUrl() }}">
                                                    <button class="user-action-item user-action-item--danger" type="submit">Eliminar usuario</button>
                                                </form>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    @empty
                        <tbody>
                            <tr>
                                <td colspan="7" class="muted" style="padding: 18px 10px; color: #64748b;">No hay usuarios para mostrar.</td>
                            </tr>
                        </tbody>
                    @endforelse
                </table>
            </div>

            @if (is_object($usuariosObjeto) && method_exists($usuariosObjeto, 'links'))
                <div class="pager" style="margin-top: 18px;">
                    {{ $usuariosObjeto->appends(['area_id' => $areaFiltroActual, 'q_usuarios' => $busquedaActual, 'editar_usuario_id' => $editarId ?: null])->links() }}
                </div>
            @endif
        </section>
    </main>
</body>
</html>
