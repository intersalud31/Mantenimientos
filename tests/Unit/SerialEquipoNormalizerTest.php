<?php

namespace Tests\Unit;

use App\Services\Legacy\SerialEquipoNormalizer;
use PHPUnit\Framework\TestCase;

class SerialEquipoNormalizerTest extends TestCase
{
    public function test_normaliza_serial_real_a_mayusculas_y_recorta_espacios(): void
    {
        $normalizer = new SerialEquipoNormalizer();

        $this->assertSame('RBN0CX02D846462', $normalizer->normalizar('  Rbn0Cx02D846462  '));
    }

    public function test_preserva_no_disponible_con_presentacion_canonica(): void
    {
        $normalizer = new SerialEquipoNormalizer();

        $this->assertSame('No disponible', $normalizer->normalizar(' no disponible '));
    }

    public function test_no_modifica_valores_nulos_ni_vacios(): void
    {
        $normalizer = new SerialEquipoNormalizer();

        $this->assertNull($normalizer->normalizar(null));
        $this->assertSame('', $normalizer->normalizar('   '));
    }

    public function test_system_serial_number_se_considera_serial_real(): void
    {
        $normalizer = new SerialEquipoNormalizer();

        $this->assertSame('SYSTEM SERIAL NUMBER2', $normalizer->normalizar('System Serial Number2'));
    }
}
