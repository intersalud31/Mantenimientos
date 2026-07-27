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

<section class="card users-module-shell users-module-shell--table" id="usuarios-listado" data-panel-filter-live-target>
    <div class="panel-card-header panel-topline-spaced">
        <div class="panel-title-block">
            <p class="section-kicker">Listado</p>
            <h2 class="panel-section-title">Usuarios por área</h2>
            <p class="muted panel-section-subtitle">La agrupación es visual y no altera el comportamiento del backend.</p>
        </div>
        <div class="panel-meta-row">
            <span class="chip chip--neutral">{{ $totalUsuarios }} registro(s)</span>
        </div>
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
                <tbody class="users-group-shell" data-users-group-shell data-open="true">
                    @php $grupoCount = count($grupoUsuarios); @endphp
                    <tr class="group-row">
                        <td colspan="7">
                            <button type="button" class="users-group-toggle" data-users-group-toggle aria-expanded="true">
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
                        <tr class="user-record">
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
                                <div class="user-actions" data-user-actions>
                                    <button
                                        class="user-actions-trigger"
                                        type="button"
                                        data-user-actions-trigger
                                        aria-expanded="false"
                                        aria-label="Abrir acciones"
                                    >
                                        <span></span>
                                        <span></span>
                                        <span></span>
                                    </button>
                                    <div class="user-actions-panel" data-user-actions-panel hidden>
                                        <a class="user-action-item" href="{{ route('panel.usuarios', ['q_usuarios' => $busquedaActual, 'editar_usuario_id' => $fila['id']]) }}">Editar usuario</a>
                                        <form method="POST" action="{{ route('panel.usuarios.estado') }}">
                                            @csrf
                                            <input type="hidden" name="usuario_id" value="{{ $fila['id'] }}">
                                            <input type="hidden" name="nuevo_estado" value="{{ $estadoActivo ? 0 : 1 }}">
                                            <input type="hidden" name="filtro_q_usuarios" value="{{ $busquedaActual }}">
                                            <input type="hidden" name="return_url" value="{{ request()->fullUrl() }}">
                                            <button class="user-action-item" type="submit">{{ $estadoActivo ? 'Desactivar usuario' : 'Activar usuario' }}</button>
                                        </form>
                                        <form method="POST" action="{{ route('panel.usuarios.eliminar') }}" onsubmit="return confirm('¿Seguro que deseas eliminar este usuario?');">
                                            @csrf
                                            <input type="hidden" name="usuario_id" value="{{ $fila['id'] }}">
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
                        <td colspan="7" class="muted panel-table-empty">No hay usuarios para mostrar.</td>
                    </tr>
                </tbody>
            @endforelse
        </table>
    </div>

    @if (is_object($usuariosObjeto) && method_exists($usuariosObjeto, 'links'))
        <div class="pager panel-spaced">
            {{ $usuariosObjeto->appends(['q_usuarios' => $busquedaActual, 'editar_usuario_id' => $editarId ?: null])->links() }}
        </div>
    @endif
</section>
