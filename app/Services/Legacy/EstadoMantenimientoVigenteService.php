<?php

namespace App\Services\Legacy;

use App\Services\SemestreService;
use Illuminate\Support\Facades\DB;

class EstadoMantenimientoVigenteService
{
    public const NO_PROGRAMADO = 'no_programado';
    public const PROGRAMADO = 'programado';
    public const EJECUTADO = 'ejecutado';

    public function __construct(private readonly SemestreService $semestreService)
    {
    }

    public function resolverParaUsuario(int $usuarioId, ?array $periodo = null): ?array
    {
        if ($usuarioId <= 0) {
            return null;
        }

        $estados = $this->resolverParaUsuarios([$usuarioId], $periodo);

        return $estados[$usuarioId] ?? null;
    }

    /**
     * @param  array<int, int|string>  $usuarioIds
     * @return array<int, array<string, mixed>>
     */
    public function resolverParaUsuarios(array $usuarioIds, ?array $periodo = null): array
    {
        $usuarioIds = collect($usuarioIds)
            ->map(static fn ($id) => (int) $id)
            ->filter(static fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($usuarioIds === []) {
            return [];
        }

        $periodo = $periodo ?? $this->semestreService->actual();
        $inicio = (string) ($periodo['inicio'] ?? '');
        $fin = (string) ($periodo['fin'] ?? '');

        if ($inicio === '' || $fin === '') {
            return [];
        }

        $filas = DB::table('usuarios as u')
            ->leftJoin('mantenimientos as m', function ($join) use ($inicio, $fin): void {
                $join->on('m.usuario_id', '=', 'u.id')
                    ->where('m.estado', '!=', MantenimientoEstados::CANCELADO)
                    ->where(function ($query) use ($inicio, $fin): void {
                        $query
                            ->where(function ($subquery) use ($inicio, $fin): void {
                                $subquery
                                    ->whereIn('m.estado', MantenimientoEstados::estadosVisiblesEnHechos())
                                    ->whereBetween(DB::raw('COALESCE(m.fecha_ejecucion, m.fecha_programada)'), [$inicio, $fin]);
                            })
                            ->orWhere(function ($subquery) use ($inicio, $fin): void {
                                $subquery
                                    ->whereIn('m.estado', MantenimientoEstados::estadosVisiblesEnProgramados())
                                    ->whereBetween(DB::raw('COALESCE(m.fecha_programada, m.fecha_ejecucion)'), [$inicio, $fin]);
                            })
                            ->orWhere(function ($subquery) use ($inicio, $fin): void {
                                $subquery
                                    ->where('m.estado', MantenimientoEstados::PENDIENTE)
                                    ->whereBetween(DB::raw('COALESCE(m.fecha_programada, m.fecha_ejecucion)'), [$inicio, $fin]);
                            });
                    });
            })
            ->whereIn('u.id', $usuarioIds)
            ->where('u.estado_usuario', 1)
            ->groupBy('u.id')
            ->orderBy('u.id')
            ->selectRaw('u.id as usuario_id')
            ->selectRaw('MAX(u.proxima_fecha_mantenimiento) as proxima_fecha_mantenimiento')
            ->selectRaw('MAX(CASE WHEN m.estado IN (?, ?) AND COALESCE(m.fecha_ejecucion, m.fecha_programada) BETWEEN ? AND ? THEN 3 ELSE 0 END) as prioridad_ejecutado', [
                MantenimientoEstados::REALIZADO,
                MantenimientoEstados::HECHO,
                $inicio,
                $fin,
            ])
            ->selectRaw('MAX(CASE WHEN m.estado IN (?, ?) AND COALESCE(m.fecha_programada, m.fecha_ejecucion) BETWEEN ? AND ? THEN 2 ELSE 0 END) as prioridad_programado', [
                MantenimientoEstados::PROGRAMADO,
                MantenimientoEstados::REPROGRAMADO,
                $inicio,
                $fin,
            ])
            ->selectRaw('MAX(CASE WHEN m.estado = ? AND COALESCE(m.fecha_programada, m.fecha_ejecucion) BETWEEN ? AND ? THEN 1 ELSE 0 END) as prioridad_pendiente', [
                MantenimientoEstados::PENDIENTE,
                $inicio,
                $fin,
            ])
            ->get();

        $resultado = [];

        foreach ($filas as $fila) {
            $prioridad = max(
                (int) ($fila->prioridad_ejecutado ?? 0),
                (int) ($fila->prioridad_programado ?? 0),
                (int) ($fila->prioridad_pendiente ?? 0)
            );

            $proximaFecha = trim((string) ($fila->proxima_fecha_mantenimiento ?? ''));
            if ($prioridad === 0 && $proximaFecha !== '' && $proximaFecha >= $inicio && $proximaFecha <= $fin) {
                $prioridad = 1;
            }

            $resultado[(int) $fila->usuario_id] = $this->mapearEstado($prioridad);
        }

        return $resultado;
    }

    /**
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    public function anexarEstadoVigente(array $items, ?array $periodo = null, string $usuarioIdKey = 'usuario_id'): array
    {
        if ($items === []) {
            return [];
        }

        $mapa = $this->resolverParaUsuarios(
            array_map(
                static fn (array $item) => (int) ($item[$usuarioIdKey] ?? 0),
                $items
            ),
            $periodo
        );

        foreach ($items as &$item) {
            $usuarioId = (int) ($item[$usuarioIdKey] ?? 0);
            $estado = $mapa[$usuarioId] ?? null;

            $item['estado_vigente'] = $estado['estado'] ?? null;
            $item['estado_vigente_etiqueta'] = $estado['etiqueta'] ?? null;
            $item['estado_vigente_badge_clase'] = $estado['badge_clase'] ?? null;
            $item['estado_vigente_prioridad'] = $estado['prioridad'] ?? 0;
        }
        unset($item);

        return $items;
    }

    /**
     * @return array<string, mixed>
     */
    private function mapearEstado(int $prioridad): array
    {
        return match ($prioridad) {
            3 => [
                'estado' => self::EJECUTADO,
                'etiqueta' => 'Ejecutado',
                'badge_clase' => 'estado-tabla-exito',
                'prioridad' => 3,
            ],
            2 => [
                'estado' => self::PROGRAMADO,
                'etiqueta' => 'Programado',
                'badge_clase' => 'estado-tabla-info',
                'prioridad' => 2,
            ],
            1 => [
                'estado' => self::NO_PROGRAMADO,
                'etiqueta' => 'No programado',
                'badge_clase' => 'estado-tabla-alerta',
                'prioridad' => 1,
            ],
            default => [
                'estado' => null,
                'etiqueta' => null,
                'badge_clase' => 'estado-tabla-neutral',
                'prioridad' => 0,
            ],
        };
    }
}
