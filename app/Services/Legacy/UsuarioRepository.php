<?php

namespace App\Services\Legacy;

use DateInterval;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

class UsuarioRepository
{
    public function __construct(private readonly UsuarioMantenimientoResumenService $resumenService)
    {
    }

    public function listarTodos(?string $busqueda = ''): array
    {
        $query = DB::table('usuarios as u')
            ->leftJoin('areas as a', 'a.id', '=', 'u.area_id')
            ->select([
                'u.id',
                'u.nombre',
                'u.codigo_activo',
                'u.serial_equipo',
                'u.tipo_equipo',
                'u.area_id',
                'u.estado_usuario',
                DB::raw("COALESCE(a.nombre, 'SIN AREA') AS area_nombre"),
            ])
            ->orderBy('area_nombre')
            ->orderBy('u.nombre');

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

        return $query->get()->map(static fn ($row) => (array) $row)->all();
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
            ->select('id', 'nombre', 'codigo_activo', 'serial_equipo', 'tipo_equipo', 'area_id', 'estado_usuario')
            ->where('id', $usuarioId)
            ->first();

        return $fila ? (array) $fila : null;
    }

    public function crearUsuario(string $nombre, int $codigoActivo, string $serialEquipo, ?int $areaId, string $tipoEquipo = 'normal'): array
    {
        $tipoEquipo = $this->normalizarTipoEquipo($tipoEquipo);

        try {
            DB::transaction(function () use ($nombre, $codigoActivo, $serialEquipo, $areaId, $tipoEquipo): void {
                $usuarioId = DB::table('usuarios')->insertGetId([
                    'nombre' => $nombre,
                    'codigo_activo' => $codigoActivo,
                    'serial_equipo' => $serialEquipo,
                    'tipo_equipo' => $tipoEquipo,
                    'area_id' => $areaId,
                    'estado_usuario' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                $fechaInicial = $this->calcularFechaInicialMantenimiento($tipoEquipo);
                $observacionInicial = $tipoEquipo === 'nuevo'
                    ? 'Equipo nuevo registrado. Primer mantenimiento automatico programado a un anio.'
                    : 'Mantenimiento inicial generado automaticamente al crear el usuario.';

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

                $this->resumenService->sincronizarResumenUsuario($usuarioId);
            });
        } catch (\Throwable $e) {
            return ['ok' => false, 'mensaje' => 'No se pudo crear el usuario. Verifica si el codigo activo o el serial ya existen.'];
        }

        $mensaje = $tipoEquipo === 'nuevo'
            ? 'Usuario creado correctamente. El primer mantenimiento quedo programado automaticamente para dentro de 1 anio.'
            : 'Usuario creado correctamente. El primer mantenimiento quedo programado automaticamente para dentro de 6 meses.';

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

    public function actualizarUsuario(int $usuarioId, string $nombre, int $codigoActivo, string $serialEquipo, ?int $areaId, string $tipoEquipo = 'normal'): bool
    {
        $tipoEquipo = $this->normalizarTipoEquipo($tipoEquipo);

        return DB::table('usuarios')
            ->where('id', $usuarioId)
            ->limit(1)
            ->update([
                'nombre' => $nombre,
                'codigo_activo' => $codigoActivo,
                'serial_equipo' => $serialEquipo,
                'tipo_equipo' => $tipoEquipo,
                'area_id' => $areaId,
                'updated_at' => now(),
            ]) > 0;
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

    private function normalizarTipoEquipo(string $tipoEquipo): string
    {
        return $tipoEquipo === 'nuevo' ? 'nuevo' : 'normal';
    }

    private function calcularFechaInicialMantenimiento(string $tipoEquipo): string
    {
        $fechaBase = new DateTimeImmutable('today');

        if ($tipoEquipo === 'nuevo') {
            $fechaBase = $fechaBase->add(new DateInterval('P1Y'));
        } else {
            $fechaBase = $fechaBase->add(new DateInterval('P6M'));
        }

        while ((int) $fechaBase->format('N') >= 6) {
            $fechaBase = $fechaBase->add(new DateInterval('P1D'));
        }

        return $fechaBase->format('Y-m-d');
    }
}
