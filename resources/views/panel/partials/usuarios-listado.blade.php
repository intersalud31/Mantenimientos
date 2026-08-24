@php
    $origenEquipoPresenter = $origenEquipoPresenter ?? app(\App\Services\Legacy\OrigenEquipoPresenter::class);
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
    $estadoUsuarioFiltro = isset($estadoUsuarioFiltro) && ($estadoUsuarioFiltro === 0 || $estadoUsuarioFiltro === 1)
        ? (int) $estadoUsuarioFiltro
        : null;
@endphp

<section class="card users-module-shell users-module-shell--table" id="usuarios-listado" data-panel-filter-live-target>
    <div class="panel-card-header panel-topline-spaced">
        <div class="panel-title-block">
            <p class="section-kicker">Listado</p>
            <h2 class="panel-section-title">Usuarios por &aacute;rea</h2>
            <p class="muted panel-section-subtitle">La agrupaci&oacute;n es visual y no altera el comportamiento del backend.</p>
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
                    <th>C&oacute;digo</th>
                    <th>Serial</th>
                    <th>&Aacute;rea</th>
                    <th>{{ $origenEquipoPresenter->fieldLabel() }}</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            @forelse ($usuariosAgrupados as $areaNombre => $grupoUsuarios)
                <tbody class="users-group-shell" data-users-group-shell data-open="true">
                    @php $grupoCount = count($grupoUsuarios); @endphp
                    <tr class="group-row">
                        <td colspan="6">
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
                            $areaFila = $presentarTexto($fila['area_nombre'] ?? $sinArea);
                            $nombreFila = $presentarTexto($fila['nombre'] ?? '');
                            $codigoFila = (string) ($fila['codigo_activo'] ?? '');
                            $serialFila = trim((string) ($fila['serial_equipo'] ?? ''));
                            $serialFila = $serialFila !== '' ? $serialFila : '-';
                            $accionesFila = [
                                [
                                    'type' => 'link',
                                    'label' => 'Editar usuario',
                                    'href' => route('panel.usuarios', array_filter([
                                        'q_usuarios' => $busquedaActual,
                                        'editar_usuario_id' => $fila['id'],
                                        'estado_usuario' => $estadoUsuarioFiltro,
                                    ], static fn ($valor) => $valor !== null && $valor !== '')),
                                ],
                                [
                                    'type' => 'form',
                                    'label' => $estadoActivo ? 'Desactivar usuario' : 'Activar usuario',
                                    'action' => route('panel.usuarios.estado'),
                                    'fields' => [
                                        'usuario_id' => $fila['id'],
                                        'nuevo_estado' => $estadoActivo ? 0 : 1,
                                        'filtro_q_usuarios' => $busquedaActual,
                                        'estado_usuario' => $estadoUsuarioFiltro,
                                        'return_url' => request()->fullUrl(),
                                    ],
                                ],
                                [
                                    'type' => 'form',
                                    'label' => 'Eliminar usuario',
                                    'action' => route('panel.usuarios.eliminar'),
                                    'variant' => 'danger',
                                    'confirm' => '¿Seguro que deseas eliminar este usuario?',
                                    'fields' => [
                                        'usuario_id' => $fila['id'],
                                        'filtro_q_usuarios' => $busquedaActual,
                                        'estado_usuario' => $estadoUsuarioFiltro,
                                        'return_url' => request()->fullUrl(),
                                    ],
                                ],
                            ];
                        @endphp
                        <tr class="user-record">
                            <td>
                                <span class="user-row-name">{{ $nombreFila }}</span>
                            </td>
                            <td>{{ $codigoFila }}</td>
                            <td>{{ $serialFila }}</td>
                            <td><span class="user-badge-area">{{ $areaFila }}</span></td>
                            <td>
                                <span class="user-badge-type user-badge-type--{{ $origenEquipoPresenter->badgeVariant($tipoEquipo) }}">{{ $origenEquipoPresenter->label($tipoEquipo) }}</span>
                            </td>
                            <td>
                                <x-panel.action-menu
                                    menu-id="usuario-acciones-{{ $fila['id'] }}"
                                    label="Acciones de {{ $nombreFila }}"
                                    :actions="$accionesFila"
                                />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            @empty
                <tbody>
                    <tr>
                        <td colspan="6" class="muted panel-table-empty">No hay usuarios para mostrar.</td>
                    </tr>
                </tbody>
            @endforelse
        </table>
    </div>

    @if (is_object($usuariosObjeto) && method_exists($usuariosObjeto, 'links'))
        <div class="pager panel-spaced">
            {{ $usuariosObjeto->appends(array_filter([
                'q_usuarios' => $busquedaActual,
                'editar_usuario_id' => $editarId ?: null,
                'estado_usuario' => $estadoUsuarioFiltro,
            ], static fn ($valor) => $valor !== null && $valor !== ''))->links() }}
        </div>
    @endif
</section>
