<?php

namespace App\Services\Legacy;

use DateTimeImmutable;
use Illuminate\Support\Facades\DB;
use App\Services\SemestreService;

class UsuarioMantenimientoResumenService
{
    public function __construct(
        private readonly SemestreService $semestreService,
        private readonly PeriodicidadMantenimientoService $periodicidadService
    ) {
    }

    public function sincronizarResumenUsuario(int $usuarioId, ?array $periodo = null, bool $priorizarFechaBase = false): array
    {
        if ($usuarioId <= 0) {
            return [];
        }

        $resumen = $this->calcularResumenUsuario($usuarioId, $periodo, $priorizarFechaBase);

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

    public function auditarResumenUsuario(int $usuarioId, ?array $periodo = null): array
    {
        if ($usuarioId <= 0) {
            return [
                'ok' => false,
                'coincide' => false,
                'cambios' => [],
                'actual' => [],
                'esperado' => [],
            ];
        }

        $actual = DB::table('usuarios')
            ->select(
                'id',
                'ultima_fecha_ejecucion',
                'ultima_fecha_mantenimiento',
                'proxima_fecha_mantenimiento',
                'ultimo_mantenimiento_id',
                'ultimo_mantenimiento_estado',
                'mantenimiento_resumen_actualizado_at'
            )
            ->where('id', $usuarioId)
            ->first();

        if (!$actual) {
            return [
                'ok' => false,
                'coincide' => false,
                'cambios' => [],
                'actual' => [],
                'esperado' => [],
            ];
        }

        $esperado = $this->calcularResumenUsuario($usuarioId, $periodo);
        $campos = [
            'ultima_fecha_ejecucion',
            'ultima_fecha_mantenimiento',
            'proxima_fecha_mantenimiento',
            'ultimo_mantenimiento_id',
            'ultimo_mantenimiento_estado',
        ];

        $actualNormalizado = [];
        $esperadoNormalizado = [];
        $cambios = [];

        foreach ($campos as $campo) {
            $actualValor = $actual->{$campo} ?? null;
            $esperadoValor = $esperado[$campo] ?? null;

            $actualNormalizado[$campo] = $actualValor;
            $esperadoNormalizado[$campo] = $esperadoValor;

            if ((string) ($actualValor ?? '') !== (string) ($esperadoValor ?? '')) {
                $cambios[$campo] = [
                    'actual' => $actualValor,
                    'esperado' => $esperadoValor,
                ];
            }
        }

        return [
            'ok' => true,
            'coincide' => $cambios === [],
            'cambios' => $cambios,
            'actual' => $actualNormalizado,
            'esperado' => $esperadoNormalizado,
        ];
    }

    private function calcularResumenUsuario(int $usuarioId, ?array $periodo = null, bool $priorizarFechaBase = false): array
    {
        $periodo = $periodo ?? $this->semestreService->actual();
        $usuario = DB::table('usuarios')
            ->select('id', 'tipo_equipo', 'created_at', 'fecha_ultimo_mantenimiento_base')
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

        $ultimoProgramado = DB::table('mantenimientos')
            ->select('id', 'estado', 'fecha_programada', 'fecha_ejecucion', 'created_at')
            ->where('usuario_id', $usuarioId)
            ->whereIn('estado', MantenimientoEstados::estadosVisiblesEnProgramados())
            ->orderByRaw('COALESCE(fecha_programada, fecha_ejecucion, created_at) DESC')
            ->orderByDesc('id')
            ->first();

        $ultimoPendiente = DB::table('mantenimientos')
            ->select('id', 'estado', 'fecha_programada', 'fecha_ejecucion', 'created_at')
            ->where('usuario_id', $usuarioId)
            ->where('estado', MantenimientoEstados::PENDIENTE)
            ->orderByRaw('COALESCE(fecha_programada, fecha_ejecucion, created_at) DESC')
            ->orderByDesc('id')
            ->first();

        $ultimaFechaReal = $this->normalizarFecha(
            $ultimoRealizado?->fecha_ejecucion
            ?? $ultimoRealizado?->fecha_programada
        );
        $fechaBaseUsuario = $this->normalizarFecha($usuario->fecha_ultimo_mantenimiento_base ?? null);
        $ultimaFechaMantenimiento = $ultimaFechaReal ?? $fechaBaseUsuario;

        $ultimaFechaEjecucion = $this->normalizarFecha(
            $ultimoRealizado?->fecha_ejecucion
            ?? $ultimoRealizado?->fecha_programada
        );
        $proximaFechaMantenimiento = null;
        $tipoEquipo = trim((string) ($usuario->tipo_equipo ?? 'normal'));

        if ($priorizarFechaBase && $ultimaFechaReal === null && $fechaBaseUsuario !== null) {
            $proximaFechaMantenimiento = $this->periodicidadService->fechaProximaParaTipoEquipo(
                $fechaBaseUsuario,
                $tipoEquipo
            );
        } elseif ($ultimoProgramado) {
            $proximaFechaMantenimiento = $this->normalizarFecha(
                $ultimoProgramado->fecha_programada
                ?? $ultimoProgramado->fecha_ejecucion
            );
        } elseif ($ultimaFechaMantenimiento !== null) {
            $proximaFechaMantenimiento = $ultimaFechaReal === null && $fechaBaseUsuario !== null
                ? $this->periodicidadService->fechaProximaParaTipoEquipo(
                    $ultimaFechaMantenimiento,
                    $tipoEquipo
                )
                : $this->periodicidadService->fechaProximaDesde(
                    $ultimaFechaMantenimiento,
                    $this->periodicidadService->cicloMantenimientoMeses()
                );
        } elseif ($ultimoPendiente) {
            $proximaFechaMantenimiento = $this->normalizarFecha(
                $ultimoPendiente->fecha_programada
                ?? $ultimoPendiente->fecha_ejecucion
            );
        }

        if ($proximaFechaMantenimiento === null && $usuario) {
            $fechaBase = $usuario->created_at ?? null;

            if ($this->esFechaValida($fechaBase)) {
                $proximaFechaMantenimiento = $this->periodicidadService->fechaProximaParaTipoEquipo(
                    (string) $fechaBase,
                    $tipoEquipo
                );
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
