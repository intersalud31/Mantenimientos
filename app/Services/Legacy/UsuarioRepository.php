<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\DB;

class UsuarioRepository
{
    public function __construct(
        private readonly SerialEquipoNormalizer $serialNormalizer,
        private readonly UsuarioMantenimientoResumenService $resumenService,
        private readonly PeriodicidadMantenimientoService $periodicidadService,
        private readonly EstadoMantenimientoVigenteService $estadoVigenteService
    ) {
    }

    public function listarTodos(?string $busqueda = '', ?int $estadoUsuario = null): array
    {
        $query = DB::table('usuarios as u')
            ->leftJoin('areas as a', 'a.id', '=', 'u.area_id')
            ->select([
                'u.id',
                'u.nombre',
                'u.codigo_activo',
                'u.serial_equipo',
                'u.tipo_equipo',
                'u.fecha_ultimo_mantenimiento_base',
                'u.area_id',
                'u.estado_usuario',
                DB::raw("COALESCE(a.nombre, 'SIN AREA') AS area_nombre"),
            ])
            ->orderBy('area_nombre')
            ->orderBy('u.nombre');

        if ($estadoUsuario === 0 || $estadoUsuario === 1) {
            $query->where('u.estado_usuario', $estadoUsuario);
        }

        $busqueda = trim((string) $busqueda);
        if ($busqueda !== '') {
            $busquedaNormalizada = mb_strtolower($busqueda, 'UTF-8');
            $query->where(function ($subquery) use ($busqueda): void {
                $busquedaNormalizada = mb_strtolower($busqueda, 'UTF-8');

                $subquery
                    ->whereRaw('LOWER(u.nombre) LIKE ?', ['%' . $busquedaNormalizada . '%'])
                    ->orWhereRaw('LOWER(u.serial_equipo) LIKE ?', ['%' . $busquedaNormalizada . '%'])
                    ->orWhereRaw("LOWER(COALESCE(a.nombre, 'SIN AREA')) LIKE ?", ['%' . $busquedaNormalizada . '%']);

                if (ctype_digit($busqueda)) {
                    $subquery->orWhereRaw('CAST(u.codigo_activo AS CHAR) LIKE ?', ['%' . $busqueda . '%']);
                } else {
                    $subquery->orWhereRaw('CAST(u.codigo_activo AS CHAR) LIKE ?', ['%' . $busquedaNormalizada . '%']);
                }
            });
        }

        $filas = $query->get()->map(function ($row) {
            $fila = (array) $row;
            $fila['serial_equipo'] = $this->serialNormalizer->normalizar($fila['serial_equipo'] ?? null);

            return $fila;
        })->all();

        return $this->estadoVigenteService->anexarEstadoVigente($filas, null, 'id');
    }

    public function listarAreasActivas(): array
    {
        return DB::table('areas')
            ->select('id', 'nombre')
            ->where('estado', 1)
            ->orderBy('nombre')
            ->get()
            ->map(static fn ($row) => (array) $row)
            ->all();
    }

    public function obtenerPorId(int $usuarioId): ?array
    {
        $fila = DB::table('usuarios')
            ->select(
                'id',
                'nombre',
                'codigo_activo',
                'serial_equipo',
                'tipo_equipo',
                'fecha_ultimo_mantenimiento_base',
                'ultima_fecha_mantenimiento',
                'ultima_fecha_ejecucion',
                'proxima_fecha_mantenimiento',
                'area_id',
                'estado_usuario'
            )
            ->where('id', $usuarioId)
            ->first();

        if (!$fila) {
            return null;
        }

        $usuario = (array) $fila;
        $usuario['serial_equipo'] = $this->serialNormalizer->normalizar($usuario['serial_equipo'] ?? null);

        return $usuario;
    }

    public function crearUsuario(string $nombre, int $codigoActivo, string $serialEquipo, ?int $areaId, string $tipoEquipo = 'normal', ?string $fechaUltimoMantenimientoBase = null): array
    {
        $tipoEquipo = $this->normalizarTipoEquipo($tipoEquipo);
        $fechaUltimoMantenimientoBase = $this->normalizarFechaBase($fechaUltimoMantenimientoBase);
        $serialEquipo = (string) $this->serialNormalizer->normalizar($serialEquipo);

        try {
            DB::transaction(function () use ($nombre, $codigoActivo, $serialEquipo, $areaId, $tipoEquipo, $fechaUltimoMantenimientoBase): void {
                $usuarioId = DB::table('usuarios')->insertGetId([
                    'nombre' => $nombre,
                    'codigo_activo' => $codigoActivo,
                    'serial_equipo' => $serialEquipo,
                    'tipo_equipo' => $tipoEquipo,
                    'fecha_ultimo_mantenimiento_base' => $fechaUltimoMantenimientoBase,
                    'area_id' => $areaId,
                    'estado_usuario' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $this->resumenService->sincronizarResumenUsuario($usuarioId);

                $fechaInicial = $this->obtenerProximaFechaMantenimientoUsuario($usuarioId);
                $observacionInicial = $tipoEquipo === 'nuevo'
                    ? 'Equipo nuevo registrado. Primer mantenimiento automatico programado a ' . $this->periodicidadService->textoCicloParaTipoEquipo($tipoEquipo) . '.'
                    : 'Mantenimiento inicial generado automaticamente al crear el usuario.';

                if ($fechaInicial !== null) {
                    DB::table('mantenimientos')->insert([
                        'usuario_id' => $usuarioId,
                        'fecha_programada' => $fechaInicial,
                        'hora_programada' => null,
                        'estado' => 'pendiente',
                        'fecha_ejecucion' => null,
                        'observaciones' => $observacionInicial,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                $this->resumenService->sincronizarResumenUsuario($usuarioId);
            });
        } catch (\Throwable $e) {
            return ['ok' => false, 'mensaje' => 'No se pudo crear el usuario. Verifica si el codigo activo o el serial ya existen.'];
        }

        $mensaje = $tipoEquipo === 'nuevo'
            ? 'Usuario creado correctamente. El primer mantenimiento quedo programado automaticamente para dentro de ' . $this->periodicidadService->textoCicloParaTipoEquipo($tipoEquipo) . '.'
            : 'Usuario creado correctamente. El primer mantenimiento quedo programado automaticamente para dentro de ' . $this->periodicidadService->textoCicloParaTipoEquipo($tipoEquipo) . '.';

        return ['ok' => true, 'mensaje' => $mensaje];
    }

    public function cambiarEstadoUsuario(int $usuarioId, bool $activo): bool
    {
        return DB::table('usuarios')
            ->where('id', $usuarioId)
            ->update([
                'estado_usuario' => $activo ? 1 : 0,
                'updated_at' => now(),
            ]) > 0;
    }

    public function actualizarUsuario(int $usuarioId, string $nombre, int $codigoActivo, string $serialEquipo, ?int $areaId, string $tipoEquipo = 'normal', ?string $fechaUltimoMantenimientoBase = null, bool $actualizarFechaBase = true, bool $priorizarFechaBase = false): bool
    {
        $tipoEquipo = $this->normalizarTipoEquipo($tipoEquipo);
        $fechaUltimoMantenimientoBase = $this->normalizarFechaBase($fechaUltimoMantenimientoBase);
        $serialEquipo = (string) $this->serialNormalizer->normalizar($serialEquipo);

        return DB::transaction(function () use ($usuarioId, $nombre, $codigoActivo, $serialEquipo, $areaId, $tipoEquipo, $fechaUltimoMantenimientoBase, $actualizarFechaBase, $priorizarFechaBase): bool {
            $usuarioExiste = DB::table('usuarios')
                ->where('id', $usuarioId)
                ->exists();

            if (!$usuarioExiste) {
                return false;
            }

            $valores = [
                'nombre' => $nombre,
                'codigo_activo' => $codigoActivo,
                'serial_equipo' => $serialEquipo,
                'tipo_equipo' => $tipoEquipo,
                'area_id' => $areaId,
                'updated_at' => now(),
            ];

            if ($actualizarFechaBase) {
                $valores['fecha_ultimo_mantenimiento_base'] = $fechaUltimoMantenimientoBase;
            }

            $actualizado = DB::table('usuarios')
                ->where('id', $usuarioId)
                ->limit(1)
                ->update($valores) > 0;

            $historialAjustado = false;
            if ($actualizarFechaBase && $fechaUltimoMantenimientoBase !== null) {
                $historialAjustado = $this->ajustarUltimoMantenimientoReal($usuarioId, $fechaUltimoMantenimientoBase);
            }

            if ($actualizado || $historialAjustado || $actualizarFechaBase) {
                $this->resumenService->sincronizarResumenUsuario($usuarioId, null, $priorizarFechaBase);
            }

            return true;
        });
    }

    public function eliminarUsuarioSeguro(int $usuarioId): array
    {
        $totalMantenimientos = (int) DB::table('mantenimientos')
            ->where('usuario_id', $usuarioId)
            ->count();

        if ($totalMantenimientos > 0) {
            return [
                'ok' => false,
                'mensaje' => 'No se puede eliminar este usuario porque tiene mantenimientos asociados. Usa Desactivar.',
            ];
        }

        $eliminado = DB::table('usuarios')
            ->where('id', $usuarioId)
            ->limit(1)
            ->delete();

        if ($eliminado <= 0) {
            return ['ok' => false, 'mensaje' => 'No se encontro el usuario para eliminar.'];
        }

        return ['ok' => true, 'mensaje' => 'Usuario eliminado correctamente.'];
    }

    public function resolverCodigoActivoPorBusqueda(string $busqueda): array
    {
        $busqueda = trim($busqueda);
        if ($busqueda === '') {
            return ['ok' => false, 'mensaje' => 'Debes escribir el nombre o codigo activo del usuario.'];
        }

        $query = DB::table('usuarios')
            ->select('id', 'nombre', 'codigo_activo', 'estado_usuario')
            ->orderBy('nombre');

        if (ctype_digit($busqueda)) {
            $exacto = (clone $query)
                ->where('codigo_activo', (int) $busqueda)
                ->first();

            if ($exacto) {
                return [
                    'ok' => true,
                    'usuario_id' => (int) $exacto->id,
                    'nombre' => (string) $exacto->nombre,
                    'codigo_activo' => (int) $exacto->codigo_activo,
                ];
            }
        }

        $normalizado = mb_strtolower($busqueda, 'UTF-8');
        $coincidencia = (clone $query)
            ->whereRaw('LOWER(nombre) LIKE ?', ['%' . $normalizado . '%'])
            ->first();

        if (!$coincidencia && !ctype_digit($busqueda)) {
            $coincidencia = (clone $query)
                ->whereRaw('LOWER(nombre) = ?', [$normalizado])
                ->first();
        }

        if (!$coincidencia) {
            return ['ok' => false, 'mensaje' => 'No se encontro un usuario que coincida con la busqueda.'];
        }

        return [
            'ok' => true,
            'usuario_id' => (int) $coincidencia->id,
            'nombre' => (string) $coincidencia->nombre,
            'codigo_activo' => (int) $coincidencia->codigo_activo,
        ];
    }

    public function buscarCoincidenciasConfiguracion(string $busqueda, int $limit = 8): array
    {
        $busqueda = trim($busqueda);
        if ($busqueda === '') {
            return [];
        }

        $limit = max(1, min(15, $limit));
        $normalizado = mb_strtolower($busqueda, 'UTF-8');

        $query = DB::table('usuarios')
            ->select('id', 'nombre', 'codigo_activo', 'estado_usuario')
            ->where(function ($subquery) use ($normalizado, $busqueda): void {
                $subquery->whereRaw('LOWER(nombre) LIKE ?', ['%' . $normalizado . '%']);

                if (ctype_digit($busqueda)) {
                    $subquery->orWhere('codigo_activo', (int) $busqueda);
                    $subquery->orWhereRaw('CAST(codigo_activo AS CHAR) LIKE ?', ['%' . $busqueda . '%']);
                }
            })
            ->orderByRaw('CASE WHEN LOWER(nombre) = ? THEN 0 ELSE 1 END', [$normalizado])
            ->orderByRaw('CASE WHEN LOWER(nombre) LIKE ? THEN 0 ELSE 1 END', [$normalizado . '%'])
            ->orderBy('nombre')
            ->limit($limit)
            ->get();

        return $query->map(static fn ($row) => [
            'usuario_id' => (int) $row->id,
            'nombre' => (string) $row->nombre,
            'codigo_activo' => (int) $row->codigo_activo,
            'estado_usuario' => (int) $row->estado_usuario,
            'activo' => (int) $row->estado_usuario === 1,
        ])->all();
    }

    public function listarTecnicosActivos(): array
    {
        return DB::table('tecnicos')
            ->select('id', 'nombre', 'estado')
            ->where('estado', 1)
            ->orderBy('nombre')
            ->get()
            ->map(static fn ($row) => (array) $row)
            ->all();
    }

    public function usuarioTieneHistorialReal(int $usuarioId): bool
    {
        return DB::table('mantenimientos')
            ->where('usuario_id', $usuarioId)
            ->whereIn('estado', MantenimientoEstados::estadosVisiblesEnHechos())
            ->exists();
    }

    private function normalizarTipoEquipo(string $tipoEquipo): string
    {
        return $tipoEquipo === 'nuevo' ? 'nuevo' : 'normal';
    }

    private function normalizarFechaBase(?string $fecha): ?string
    {
        $fecha = trim((string) $fecha);

        return $fecha !== '' ? $fecha : null;
    }

    private function obtenerProximaFechaMantenimientoUsuario(int $usuarioId): ?string
    {
        $fecha = DB::table('usuarios')
            ->where('id', $usuarioId)
            ->value('proxima_fecha_mantenimiento');

        $fecha = is_string($fecha) ? trim($fecha) : '';

        return $fecha !== '' ? $fecha : null;
    }

    private function ajustarUltimoMantenimientoReal(int $usuarioId, string $fechaUltimoMantenimientoBase): bool
    {
        $ultimoRealizado = DB::table('mantenimientos')
            ->select('id', 'fecha_programada', 'fecha_ejecucion')
            ->where('usuario_id', $usuarioId)
            ->whereIn('estado', MantenimientoEstados::estadosVisiblesEnHechos())
            ->orderByRaw('COALESCE(fecha_ejecucion, fecha_programada, created_at) DESC')
            ->orderByDesc('id')
            ->first();

        if (!$ultimoRealizado) {
            return false;
        }

        $valores = [
            'fecha_programada' => $fechaUltimoMantenimientoBase,
            'updated_at' => now(),
        ];

        if (trim((string) ($ultimoRealizado->fecha_ejecucion ?? '')) !== '') {
            $valores['fecha_ejecucion'] = $fechaUltimoMantenimientoBase;
        }

        return DB::table('mantenimientos')
            ->where('id', (int) $ultimoRealizado->id)
            ->update($valores) > 0;
    }

}
