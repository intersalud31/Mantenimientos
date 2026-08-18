<?php

namespace App\Services\Legacy;

class SerialEquipoNormalizer
{
    private const VALOR_ESPECIAL_NO_DISPONIBLE = 'No disponible';

    public function normalizar(?string $serial): ?string
    {
        if ($serial === null) {
            return null;
        }

        $serial = trim($serial);

        if ($serial === '') {
            return '';
        }

        if ($this->esValorEspecial($serial)) {
            return self::VALOR_ESPECIAL_NO_DISPONIBLE;
        }

        return mb_strtoupper($serial, 'UTF-8');
    }

    public function esValorEspecial(?string $serial): bool
    {
        if ($serial === null) {
            return false;
        }

        return mb_strtolower(trim($serial), 'UTF-8') === mb_strtolower(self::VALOR_ESPECIAL_NO_DISPONIBLE, 'UTF-8');
    }
}
