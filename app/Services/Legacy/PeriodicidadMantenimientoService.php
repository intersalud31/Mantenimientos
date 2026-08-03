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

    /** @var array<int, array<string, bool>> */
    private array $festivosCache = [];

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

        while (!$this->esDiaHabil($fecha->format('Y-m-d'))) {
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

    public function esDiaHabil(string $fecha): bool
    {
        $fecha = trim($fecha);
        if (!$this->esFechaValida($fecha)) {
            return false;
        }

        $fechaParsed = new DateTimeImmutable($fecha);
        if ((int) $fechaParsed->format('N') >= 6) {
            return false;
        }

        return !$this->esFestivo($fecha);
    }

    /**
     * @return array<int, string>
     */
    public function festivosEntre(int $anioInicial, int $anioFinal): array
    {
        if ($anioInicial > $anioFinal) {
            [$anioInicial, $anioFinal] = [$anioFinal, $anioInicial];
        }

        $festivos = [];
        for ($anio = $anioInicial; $anio <= $anioFinal; $anio++) {
            foreach (array_keys($this->festivosDelAnio($anio)) as $fecha) {
                $festivos[$fecha] = true;
            }
        }

        $fechas = array_keys($festivos);
        sort($fechas);

        return $fechas;
    }

    public function siguienteDiaHabil(string $fecha): ?string
    {
        $fecha = trim($fecha);
        if (!$this->esFechaValida($fecha)) {
            return null;
        }

        $actual = new DateTimeImmutable($fecha);
        while (!$this->esDiaHabil($actual->format('Y-m-d'))) {
            $actual = $actual->add(new DateInterval('P1D'));
        }

        return $actual->format('Y-m-d');
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

    private function esFestivo(string $fecha): bool
    {
        $anio = (int) substr($fecha, 0, 4);
        return isset($this->festivosDelAnio($anio)[$fecha]);
    }

    /**
     * @return array<string, bool>
     */
    private function festivosDelAnio(int $anio): array
    {
        if (isset($this->festivosCache[$anio])) {
            return $this->festivosCache[$anio];
        }

        $pascua = $this->calcularPascua($anio);
        $festivos = [
            sprintf('%04d-01-01', $anio) => true,
            $this->moverALunes(sprintf('%04d-01-06', $anio)) => true,
            $this->moverALunes(sprintf('%04d-03-19', $anio)) => true,
            $pascua->sub(new DateInterval('P3D'))->format('Y-m-d') => true,
            $pascua->sub(new DateInterval('P2D'))->format('Y-m-d') => true,
            sprintf('%04d-05-01', $anio) => true,
            $this->moverALunes($pascua->add(new DateInterval('P43D'))->format('Y-m-d')) => true,
            $this->moverALunes($pascua->add(new DateInterval('P64D'))->format('Y-m-d')) => true,
            $this->moverALunes($pascua->add(new DateInterval('P71D'))->format('Y-m-d')) => true,
            $this->moverALunes(sprintf('%04d-06-29', $anio)) => true,
            sprintf('%04d-07-20', $anio) => true,
            sprintf('%04d-08-07', $anio) => true,
            $this->moverALunes(sprintf('%04d-08-15', $anio)) => true,
            $this->moverALunes(sprintf('%04d-10-12', $anio)) => true,
            $this->moverALunes(sprintf('%04d-11-01', $anio)) => true,
            $this->moverALunes(sprintf('%04d-11-11', $anio)) => true,
            sprintf('%04d-12-08', $anio) => true,
            sprintf('%04d-12-25', $anio) => true,
        ];

        $festivos = array_filter(
            $festivos,
            static fn (bool $valor, string $fecha): bool => $valor && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) === 1,
            ARRAY_FILTER_USE_BOTH
        );

        $this->festivosCache[$anio] = $festivos;

        return $this->festivosCache[$anio];
    }

    private function moverALunes(string $fecha): string
    {
        $actual = new DateTimeImmutable($fecha);
        while ((int) $actual->format('N') !== 1) {
            $actual = $actual->add(new DateInterval('P1D'));
        }

        return $actual->format('Y-m-d');
    }

    private function calcularPascua(int $anio): DateTimeImmutable
    {
        $a = $anio % 19;
        $b = intdiv($anio, 100);
        $c = $anio % 100;
        $d = intdiv($b, 4);
        $e = $b % 4;
        $f = intdiv($b + 8, 25);
        $g = intdiv($b - $f + 1, 3);
        $h = (19 * $a + $b - $d - $g + 15) % 30;
        $i = intdiv($c, 4);
        $k = $c % 4;
        $l = (32 + 2 * $e + 2 * $i - $h - $k) % 7;
        $m = intdiv($a + 11 * $h + 22 * $l, 451);
        $mes = intdiv($h + $l - 7 * $m + 114, 31);
        $dia = (($h + $l - 7 * $m + 114) % 31) + 1;

        return new DateTimeImmutable(sprintf('%04d-%02d-%02d', $anio, $mes, $dia));
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
