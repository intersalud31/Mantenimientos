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
        $usuario = DB::table('usuarios')
            ->select('id', 'tipo_equipo', 'created_at')
            ->where('id', $usuarioId)
            ->first();

        $ultimoMantenimiento = DB::table('mantenimientos')
            ->select('id', 'estado', 'fecha_programada', 'fecha_ejecucion', 'created_at')
            ->where('usuario_id', $usuarioId)
            ->whereNotIn('estado', [MantenimientoEstados::CANCELADO])
            ->orderByRaw('COALESCE(fecha_ejecucion, fecha_programada, created_at) DESC')
            ->orderByDesc('id')
            ->first();

        $ultimoRealizado = DB::table('mantenimientos')
            ->select('id', 'estado', 'fecha_programada', 'fecha_ejecucion', 'created_at')
            ->where('usuario_id', $usuarioId)
            ->whereIn('estado', MantenimientoEstados::estadosVisiblesEnHechos())
            ->orderByRaw('COALESCE(fecha_ejecucion, fecha_programada, created_at) DESC')
            ->orderByDesc('id')
            ->first();

        $ultimaFechaMantenimiento = null;
        if ($ultimoRealizado) {
            $ultimaFechaMantenimiento = $this->normalizarFecha(
                $ultimoRealizado->fecha_ejecucion
                ?? $ultimoRealizado->fecha_programada
            );
        }

        $ultimaFechaEjecucion = $this->normalizarFecha(
            $ultimoRealizado?->fecha_ejecucion
            ?? $ultimoRealizado?->fecha_programada
        );
        $proximaFechaMantenimiento = null;

        if ($ultimoMantenimiento) {
            $estadoUltimo = MantenimientoEstados::normalizar((string) $ultimoMantenimiento->estado);
            $fechaReferencia = trim((string) ($ultimoMantenimiento->fecha_programada ?? $ultimoMantenimiento->fecha_ejecucion ?? ''));

            if (in_array($estadoUltimo, MantenimientoEstados::estadosVisiblesEnProgramados(), true) || $estadoUltimo === MantenimientoEstados::PENDIENTE) {
                $proximaFechaMantenimiento = $this->normalizarFecha($fechaReferencia);
            } elseif ($this->esFechaValida($fechaReferencia)) {
                $proximaFechaMantenimiento = $this->calcularProximaFechaDesde($fechaReferencia, 6);
            }
        }

        if ($proximaFechaMantenimiento === null && $usuario) {
            $fechaBase = $usuario->created_at ?? null;
            $tipoEquipo = trim((string) ($usuario->tipo_equipo ?? 'normal'));

            if ($this->esFechaValida($fechaBase)) {
                $meses = $tipoEquipo === 'nuevo' ? 12 : 6;
                $proximaFechaMantenimiento = $this->calcularProximaFechaDesde((string) $fechaBase, $meses);
            }
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

    private function calcularProximaFechaDesde(string $fechaBase, int $meses = 6): ?string
    {
        if (!$this->esFechaValida($fechaBase)) {
            return null;
        }

        $intervalo = $meses >= 12 ? 'P1Y' : 'P' . max(1, $meses) . 'M';
        $fecha = (new DateTimeImmutable($fechaBase))->add(new DateInterval($intervalo));

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
