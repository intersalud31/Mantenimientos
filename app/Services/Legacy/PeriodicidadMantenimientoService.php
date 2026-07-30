<?php

namespace App\Services\Legacy;

use DateInterval;
use DateTimeImmutable;
use Illuminate\Support\Facades\DB;

class PeriodicidadMantenimientoService
{
    /**
     * Límites conservadores para evitar configuraciones accidentales.
     * Se mantienen amplios para no cambiar el comportamiento actual.
     */
    private const MIN_MESES = 1;
    private const MAX_MESES = 120;
    private const MIN_DIAS = 1;
    private const MAX_DIAS = 365;

    private const CLAVE_CICLO = 'ciclo_mantenimiento_meses';
    private const CLAVE_PRIMER_NUEVO = 'primer_mantenimiento_nuevo_meses';
    private const CLAVE_RECORDATORIO = 'recordatorio_dias_antes';

    private const DEFAULT_CICLO = 6;
    private const DEFAULT_PRIMER_NUEVO = 12;
    private const DEFAULT_RECORDATORIO = 7;

    /** @var array<string, int>|null */
    private ?array $cache = null;

    public function configuracion(): array
    {
        return [
            self::CLAVE_CICLO => $this->cicloMantenimientoMeses(),
            self::CLAVE_PRIMER_NUEVO => $this->primerMantenimientoNuevoMeses(),
            self::CLAVE_RECORDATORIO => $this->recordatorioDiasAntes(),
        ];
    }

    public function cicloMantenimientoMeses(): int
    {
        return $this->entero(self::CLAVE_CICLO, self::DEFAULT_CICLO, self::MIN_MESES, self::MAX_MESES);
    }

    public function primerMantenimientoNuevoMeses(): int
    {
        return $this->entero(self::CLAVE_PRIMER_NUEVO, self::DEFAULT_PRIMER_NUEVO, self::MIN_MESES, self::MAX_MESES);
    }

    public function recordatorioDiasAntes(): int
    {
        return $this->entero(self::CLAVE_RECORDATORIO, self::DEFAULT_RECORDATORIO, self::MIN_DIAS, self::MAX_DIAS);
    }

    public function mesesSegunTipoEquipo(string $tipoEquipo): int
    {
        return $this->normalizarTipoEquipo($tipoEquipo) === 'nuevo'
            ? $this->primerMantenimientoNuevoMeses()
            : $this->cicloMantenimientoMeses();
    }

    public function etiquetaMeses(int $meses): string
    {
        $meses = max(self::MIN_MESES, $meses);

        if ($meses === 1) {
            return '1 mes';
        }

        if ($meses >= 12 && $meses % 12 === 0) {
            $anios = intdiv($meses, 12);
            return $anios === 1 ? '1 anio' : $anios . ' anios';
        }

        return $meses . ' meses';
    }

    public function fechaProximaDesde(string $fechaBase, int $meses): ?string
    {
        if (!$this->esFechaValida($fechaBase)) {
            return null;
        }

        $meses = $this->normalizarMeses($meses);
        $intervalo = $meses >= 12 && $meses % 12 === 0
            ? 'P' . intdiv($meses, 12) . 'Y'
            : 'P' . $meses . 'M';

        $fecha = (new DateTimeImmutable($fechaBase))->add(new DateInterval($intervalo));

        while ((int) $fecha->format('N') >= 6) {
            $fecha = $fecha->add(new DateInterval('P1D'));
        }

        return $fecha->format('Y-m-d');
    }

    public function fechaProximaParaTipoEquipo(string $fechaBase, string $tipoEquipo): ?string
    {
        return $this->fechaProximaDesde($fechaBase, $this->mesesSegunTipoEquipo($tipoEquipo));
    }

    public function textoCicloParaTipoEquipo(string $tipoEquipo): string
    {
        return $this->etiquetaMeses($this->mesesSegunTipoEquipo($tipoEquipo));
    }

    private function entero(string $clave, int $default, int $minimo, int $maximo): int
    {
        $valores = $this->cargarConfiguracion();
        $valor = $valores[$clave] ?? $default;

        if (!is_int($valor)) {
            return $default;
        }

        if ($valor < $minimo || $valor > $maximo) {
            return $default;
        }

        return $valor;
    }

    /**
     * @return array<string, int>
     */
    private function cargarConfiguracion(): array
    {
        if (is_array($this->cache)) {
            return $this->cache;
        }

        $valores = [];
        $filas = DB::table('configuracion_sistema')
            ->select('clave', 'valor', 'tipo')
            ->whereIn('clave', [
                self::CLAVE_CICLO,
                self::CLAVE_PRIMER_NUEVO,
                self::CLAVE_RECORDATORIO,
            ])
            ->get();

        foreach ($filas as $fila) {
            $clave = trim((string) ($fila->clave ?? ''));
            if ($clave === '') {
                continue;
            }

            if ((string) ($fila->tipo ?? '') !== 'entero') {
                continue;
            }

            $valor = filter_var($fila->valor ?? null, FILTER_VALIDATE_INT, FILTER_NULL_ON_FAILURE);
            if ($valor === null || $valor === false) {
                continue;
            }

            $valores[$clave] = (int) $valor;
        }

        $this->cache = $valores;

        return $this->cache;
    }

    private function normalizarMeses(int $meses): int
    {
        return max(self::MIN_MESES, min(self::MAX_MESES, $meses));
    }

    private function normalizarTipoEquipo(string $tipoEquipo): string
    {
        return trim($tipoEquipo) === 'nuevo' ? 'nuevo' : 'normal';
    }

    private function esFechaValida(string $fecha): bool
    {
        $fecha = trim($fecha);
        if ($fecha === '') {
            return false;
        }

        $parsed = DateTimeImmutable::createFromFormat('Y-m-d', $fecha);
        return $parsed !== false && $parsed->format('Y-m-d') === $fecha;
    }
}
