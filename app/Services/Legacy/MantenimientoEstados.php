<?php

namespace App\Services\Legacy;

final class MantenimientoEstados
{
    public const PENDIENTE = 'pendiente';
    public const PROGRAMADO = 'programado';
    public const REALIZADO = 'realizado';
    public const CANCELADO = 'cancelado';
    public const REPROGRAMADO = 'reprogramado';
    public const HECHO = 'hecho';

    /**
     * @return array<int, string>
     */
    public static function estadosVisiblesEnProximos(): array
    {
        return [self::PENDIENTE];
    }

    /**
     * @return array<int, string>
     */
    public static function estadosVisiblesEnProgramados(): array
    {
        return [self::PROGRAMADO, self::REPROGRAMADO];
    }

    /**
     * @return array<int, string>
     */
    public static function estadosVisiblesEnHechos(): array
    {
        return [self::REALIZADO, self::HECHO];
    }

    /**
     * @return array<int, string>
     */
    public static function estadosParaConsulta(string $estado): array
    {
        $estado = self::normalizar($estado);

        return match ($estado) {
            self::PROGRAMADO => [self::PROGRAMADO, self::REPROGRAMADO],
            self::REALIZADO => [self::REALIZADO, self::HECHO],
            self::PENDIENTE, self::CANCELADO => [$estado],
            default => [$estado],
        };
    }

    public static function normalizar(string $estado): string
    {
        $estado = trim(mb_strtolower($estado));

        return match ($estado) {
            self::HECHO => self::REALIZADO,
            self::REPROGRAMADO => self::PROGRAMADO,
            default => $estado,
        };
    }

    public static function etiqueta(string $estado): string
    {
        $estado = trim(mb_strtolower($estado));

        return match ($estado) {
            self::PENDIENTE => 'Pendiente',
            self::PROGRAMADO => 'Programado',
            self::REALIZADO, self::HECHO => 'Hecho',
            self::CANCELADO => 'Cancelado',
            self::REPROGRAMADO => 'Reprogramado',
            default => ucfirst($estado ?: 'Sin estado'),
        };
    }

    public static function badgeClase(string $estado): string
    {
        $estado = trim(mb_strtolower($estado));

        return match ($estado) {
            self::PENDIENTE, self::PROGRAMADO => 'estado-tabla-info',
            self::REPROGRAMADO => 'estado-tabla-alerta',
            self::REALIZADO, self::HECHO => 'estado-tabla-exito',
            self::CANCELADO => 'estado-tabla-neutral',
            default => 'estado-tabla-neutral',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function estadosProgramables(): array
    {
        return [self::PENDIENTE];
    }

    /**
     * @return array<int, string>
     */
    public static function estadosReversiblesComoProgramado(): array
    {
        return [self::PROGRAMADO, self::REPROGRAMADO];
    }

    /**
     * @return array<int, string>
     */
    public static function estadosReversiblesComoHecho(): array
    {
        return [self::REALIZADO, self::HECHO];
    }
}
