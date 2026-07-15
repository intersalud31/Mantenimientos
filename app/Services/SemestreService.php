<?php

namespace App\Services;

use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Http\Request;

class SemestreService
{
    public function actual(): array
    {
        $ahora = new DateTimeImmutable('today');

        return $this->rango((int) $ahora->format('Y'), (int) $ahora->format('n') <= 6 ? 1 : 2);
    }

    public function rango(int $anio, int $semestre): array
    {
        $semestre = $semestre === 2 ? 2 : 1;
        $inicio = $semestre === 1
            ? sprintf('%04d-01-01', $anio)
            : sprintf('%04d-07-01', $anio);
        $fin = $semestre === 1
            ? sprintf('%04d-06-30', $anio)
            : sprintf('%04d-12-31', $anio);

        return [
            'anio' => $anio,
            'semestre' => $semestre,
            'inicio' => $inicio,
            'fin' => $fin,
            'inicio_at' => $inicio . ' 00:00:00',
            'fin_at' => $fin . ' 23:59:59',
            'etiqueta' => sprintf('Semestre %d de %d', $semestre, $anio),
        ];
    }

    public function desdeRequest(Request $request): array
    {
        $actual = $this->actual();
        $anio = (int) $request->query('anio', $actual['anio']);
        $semestre = (int) $request->query('semestre', $actual['semestre']);

        if ($anio <= 0) {
            $anio = (int) $actual['anio'];
        }

        if (!in_array($semestre, [1, 2], true)) {
            $semestre = (int) $actual['semestre'];
        }

        return $this->rango($anio, $semestre);
    }

    public function semestreDeFecha(string|DateTimeInterface $fecha): array
    {
        $fecha = $fecha instanceof DateTimeInterface
            ? new DateTimeImmutable($fecha->format('Y-m-d'))
            : new DateTimeImmutable((string) $fecha);

        return $this->rango((int) $fecha->format('Y'), (int) $fecha->format('n') <= 6 ? 1 : 2);
    }

    public function contieneFecha(string|DateTimeInterface $fecha, int $anio, int $semestre): bool
    {
        try {
            $fecha = $fecha instanceof DateTimeInterface
                ? new DateTimeImmutable($fecha->format('Y-m-d'))
                : new DateTimeImmutable((string) $fecha);
        } catch (\Throwable $e) {
            return false;
        }

        $periodo = $this->rango($anio, $semestre);
        $fechaTexto = $fecha->format('Y-m-d');

        return $fechaTexto >= $periodo['inicio'] && $fechaTexto <= $periodo['fin'];
    }
}
