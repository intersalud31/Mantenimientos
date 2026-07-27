<?php

namespace App\Services\Legacy;

class TextoLegacyNormalizer
{
    private const MARCAS_SISTEMA = [
        '| Programado desde módulo Próximos.',
        '| Hecho desde módulo Programados.',
        '| Revertido desde módulo Reversiones (HECHO).',
        '| Revertido desde módulo Reversiones (PROGRAMAR).',
        '| Cancelado automáticamente por normalización de pendientes duplicados.',
        'Programado desde módulo Próximos.',
        'Hecho desde módulo Programados.',
        'Revertido desde módulo Reversiones (HECHO).',
        'Revertido desde módulo Reversiones (PROGRAMAR).',
        'Cancelado automáticamente por normalización de pendientes duplicados.',
        'Generado automático después de marcar Hecho.',
    ];

    public function texto(?string $valor, string $default = ''): string
    {
        $texto = $this->repararEncoding(trim((string) $valor));
        $texto = preg_replace('/[ \t]+/u', ' ', $texto) ?? $texto;
        $texto = preg_replace("/\R{3,}/u", "\n\n", $texto) ?? $texto;

        return $texto !== '' ? $texto : $default;
    }

    public function observacion(?string $valor): string
    {
        $texto = $this->texto($valor, '');
        if ($texto === '') {
            return '';
        }

        $texto = str_replace(self::MARCAS_SISTEMA, '', $texto);
        $texto = preg_replace('/\s*\|\s*\|+/u', ' | ', $texto) ?? $texto;
        $texto = preg_replace('/(?:\s*\|\s*){2,}/u', ' | ', $texto) ?? $texto;
        $texto = preg_replace('/[ \t]{2,}/u', ' ', $texto) ?? $texto;
        $texto = preg_replace('/\n{3,}/u', "\n\n", $texto) ?? $texto;

        return trim($texto, " |\t\n\r\0\x0B");
    }

    public function anadirMarcaSistema(?string $valor, string $marcaSistema): string
    {
        $texto = $this->observacion($valor);
        $marcaSistema = $this->texto($marcaSistema, '');

        if ($marcaSistema === '') {
            return $texto;
        }

        if ($texto === '') {
            return $marcaSistema;
        }

        if (str_contains($texto, $marcaSistema)) {
            return $texto;
        }

        return trim($texto . ' | ' . $marcaSistema);
    }

    /**
     * Devuelve una copia normalizada de varios campos sin mutar la fuente original.
     *
     * @param array<string, mixed> $campos
     * @return array<string, mixed>
     */
    public function normalizarCampos(array $campos): array
    {
        foreach ($campos as $clave => $valor) {
            if (is_string($valor)) {
                $campos[$clave] = $this->texto($valor, '');
            }
        }

        return $campos;
    }

    private function repararEncoding(string $texto): string
    {
        if ($texto === '') {
            return '';
        }

        if (! $this->pareceMojibake($texto)) {
            return $texto;
        }

        $corregido = @mb_convert_encoding($texto, 'UTF-8', 'ISO-8859-1');
        if (is_string($corregido) && $corregido !== '') {
            return $corregido;
        }

        return $texto;
    }

    private function pareceMojibake(string $texto): bool
    {
        return preg_match('/(?:\xC3\x83\xC6\x92|\xC3\x83\xC2\x82|\xC3\x83\xC2\xA2\xC3\xA2\xC2\x82\xC2\xAC|\xC3\xAF\xC2\xBF\xC2\xBD)/', $texto) === 1;
    }
}
