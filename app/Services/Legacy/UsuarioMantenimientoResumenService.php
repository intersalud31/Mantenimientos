<?php

namespace App\Services\Legacy;

use DateInterval;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use App\Services\SemestreService;

class UsuarioMantenimientoResumenService
{
    public function __construct(private readonly SemestreService $semestreService)
    {
    }

    public function sincronizarResumenUsuario(int $usuarioId, ?array $periodo = null): array
    {
        if ($usuarioId <= 0) {
            return [];
        }

        $resumen = $this->calcularResumenUsuario($usuarioId, $periodo);

        DB::table('usuarios')
            ->where('id', $usuarioId)
            ->update([
                'ultima_fecha_ejecucion' => $resumen['ultima_fecha_ejecucion'],
                'ultima_fecha_mantenimiento' => $resumen['ultima_fecha_mantenimiento'],
                'proxima_fecha_mantenimiento' => $resumen['proxima_fecha_mantenimiento'],
                'ultimo_mantenimiento_id' => $resumen['ultimo_mantenimiento_id'],
                'ultimo_mantenimiento_estado' => $resumen['ultimo_mantenimiento_estado'],
                'mantenimiento_resumen_actualizado_at' => $resumen['mantenimiento_resumen_actualizado_at'],
                'updated_at' => now(),
            ]);

        return $resumen;
    }

    public function sincronizarResumenPorMantenimientoId(int $mantenimientoId): array
    {
        if ($mantenimientoId <= 0) {
            return [];
        }

        $mantenimiento = DB::table('mantenimientos')
            ->select('usuario_id', 'fecha_ejecucion', 'fecha_programada')
            ->where('id', $mantenimientoId)
            ->first();

        if (!$mantenimiento || !is_numeric($mantenimiento->usuario_id) || (int) $mantenimiento->usuario_id <= 0) {
            return [];
        }

        $fechaReferencia = trim((string) ($mantenimiento->fecha_ejecucion ?? $mantenimiento->fecha_programada ?? ''));
        $periodo = $fechaReferencia !== ''
            ? $this->semestreService->semestreDeFecha($fechaReferencia)
            : $this->semestreService->actual();

        return $this->sincronizarResumenUsuario((int) $mantenimiento->usuario_id, $periodo);
    }

    private function calcularResumenUsuario(int $usuarioId, ?array $periodo = null): array
    {
        $periodo = $periodo ?? $this->semestreService->actual();
        $inicio = (string) ($periodo['inicio'] ?? $this->semestreService->actual()['inicio']);
        $fin = (string) ($periodo['fin'] ?? $this->semestreService->actual()['fin']);

        $ultimaFechaEjecucion = DB::table('mantenimientos')
            ->where('usuario_id', $usuarioId)
            ->whereIn('estado', MantenimientoEstados::estadosVisiblesEnHechos())
            ->whereNotNull('fecha_ejecucion')
            ->whereBetween('fecha_ejecucion', [$inicio, $fin])
            ->max('fecha_ejecucion');

        $ultimaFechaMantenimiento = DB::table('mantenimientos')
            ->where('usuario_id', $usuarioId)
            ->whereRaw('COALESCE(fecha_ejecucion, fecha_programada) IS NOT NULL')
            ->whereRaw('COALESCE(fecha_ejecucion, fecha_programada) BETWEEN ? AND ?', [$inicio, $fin])
            ->max(DB::raw('COALESCE(fecha_ejecucion, fecha_programada)'));

        $proximaFechaProgramada = DB::table('mantenimientos')
            ->where('usuario_id', $usuarioId)
            ->whereIn('estado', array_merge(MantenimientoEstados::estadosVisiblesEnProximos(), MantenimientoEstados::estadosVisiblesEnProgramados()))
            ->whereNotNull('fecha_programada')
            ->whereBetween('fecha_programada', [$inicio, $fin])
            ->min('fecha_programada');

        $ultimoMantenimiento = DB::table('mantenimientos')
            ->select('id', 'estado', 'fecha_programada', 'fecha_ejecucion', 'created_at')
            ->where('usuario_id', $usuarioId)
            ->whereRaw('COALESCE(fecha_ejecucion, fecha_programada, created_at) BETWEEN ? AND ?', [$inicio . ' 00:00:00', $fin . ' 23:59:59'])
            ->orderByRaw('COALESCE(fecha_ejecucion, fecha_programada, created_at) DESC')
            ->orderByDesc('id')
            ->first();

        $ultimaFechaEjecucion = $this->normalizarFecha($ultimaFechaEjecucion);
        $ultimaFechaMantenimiento = $this->normalizarFecha($ultimaFechaMantenimiento);
        $proximaFechaMantenimiento = null;

        if ($this->esFechaValida($proximaFechaProgramada)) {
            $proximaFechaMantenimiento = $this->normalizarFecha($proximaFechaProgramada);
        } elseif ($this->esFechaValida($ultimaFechaEjecucion)) {
            $proximaFechaMantenimiento = $this->calcularProximaFechaDesde($ultimaFechaEjecucion);
        } elseif ($this->esFechaValida($ultimaFechaMantenimiento)) {
            $proximaFechaMantenimiento = $this->calcularProximaFechaDesde($ultimaFechaMantenimiento);
        }

        return [
            'anio' => (int) ($periodo['anio'] ?? (int) (new DateTimeImmutable('today'))->format('Y')),
            'semestre' => (int) ($periodo['semestre'] ?? ((int) (new DateTimeImmutable('today'))->format('n') <= 6 ? 1 : 2)),
            'ultima_fecha_ejecucion' => $ultimaFechaEjecucion,
            'ultima_fecha_mantenimiento' => $ultimaFechaMantenimiento,
            'proxima_fecha_mantenimiento' => $proximaFechaMantenimiento,
            'ultimo_mantenimiento_id' => $ultimoMantenimiento ? (int) $ultimoMantenimiento->id : null,
            'ultimo_mantenimiento_estado' => $ultimoMantenimiento ? MantenimientoEstados::normalizar((string) $ultimoMantenimiento->estado) : null,
            'mantenimiento_resumen_actualizado_at' => now(),
        ];
    }

    private function calcularProximaFechaDesde(string $fechaBase): ?string
    {
        if (!$this->esFechaValida($fechaBase)) {
            return null;
        }

        $fecha = (new DateTimeImmutable($fechaBase))->add(new DateInterval('P6M'));

        while ((int) $fecha->format('N') >= 6) {
            $fecha = $fecha->add(new DateInterval('P1D'));
        }

        return $fecha->format('Y-m-d');
    }

    private function normalizarFecha(mixed $fecha): ?string
    {
        if (!$this->esFechaValida($fecha)) {
            return null;
        }

        return (new DateTimeImmutable((string) $fecha))->format('Y-m-d');
    }

    private function esFechaValida(mixed $fecha): bool
    {
        return is_string($fecha) && trim($fecha) !== '';
    }
}
