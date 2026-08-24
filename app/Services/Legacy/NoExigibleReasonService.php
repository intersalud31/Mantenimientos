<?php

namespace App\Services\Legacy;

class NoExigibleReasonService
{
    public const TODOS = '';
    public const EQUIPO_NUEVO = 'equipo_nuevo';
    public const FUERA_PERIODO = 'fuera_periodo';

    /**
     * @return array<int, array{value:string,label:string}>
     */
    public function options(): array
    {
        return [
            ['value' => self::TODOS, 'label' => 'Todos'],
            ['value' => self::EQUIPO_NUEVO, 'label' => 'Equipo nuevo'],
            ['value' => self::FUERA_PERIODO, 'label' => 'Proximo mantenimiento fuera del semestre'],
        ];
    }

    public function label(string $motivo): string
    {
        return match ($motivo) {
            self::EQUIPO_NUEVO => 'Equipo nuevo',
            self::FUERA_PERIODO => 'Proximo mantenimiento fuera del semestre',
            default => 'No exigible',
        };
    }
}
