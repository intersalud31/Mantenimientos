<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class Bd3SnapshotProtectionTest extends TestCase
{
    public function test_la_base_activa_es_la_aislada_y_la_fotografia_se_genera_en_json(): void
    {
        $database = DB::connection()->getDatabaseName();

        $this->assertSame('mantenimientos_normalizacion_pruebas', $database);
        $this->assertNotSame('mantenimientos', $database);

        $output = tempnam(sys_get_temp_dir(), 'bd3_');
        $this->assertNotFalse($output);

        try {
            $exitCode = Artisan::call('bd3:snapshot', [
                '--output' => $output,
                '--force' => true,
            ]);

            $this->assertSame(0, $exitCode);

            $json = json_decode((string) file_get_contents($output), true, 512, JSON_THROW_ON_ERROR);

            $this->assertSame('mantenimientos_normalizacion_pruebas', $json['meta']['database'] ?? null);
            $this->assertSame('mantenimientos_normalizacion_pruebas', $json['meta']['base_esperada'] ?? null);
            $this->assertArrayHasKey('config_cache', $json['meta']);
            $this->assertArrayHasKey('php_version', $json['meta']);
            $this->assertArrayHasKey('laravel_version', $json['meta']);
            $this->assertArrayHasKey('db_version', $json['meta']);

            $this->assertGreaterThan(0, (int) ($json['esquema']['total_tablas'] ?? 0));
            $this->assertNotEmpty($json['esquema']['tablas'] ?? []);

            $nombresTablas = array_map(static fn (array $tabla): string => (string) ($tabla['nombre'] ?? ''), $json['esquema']['tablas']);
            foreach (['usuarios', 'usuarios_sistema', 'mantenimientos', 'configuracion_sistema'] as $tablaCritica) {
                $this->assertContains($tablaCritica, $nombresTablas);
            }

            foreach (['usuarios', 'usuarios_sistema', 'mantenimientos', 'configuracion_sistema'] as $tablaCritica) {
                $this->assertArrayHasKey($tablaCritica, $json['huellas_logicas'] ?? []);
                $this->assertArrayHasKey('strict', $json['huellas_logicas'][$tablaCritica]);
                $this->assertArrayHasKey('functional', $json['huellas_logicas'][$tablaCritica]);
                $this->assertSame(64, strlen((string) ($json['huellas_logicas'][$tablaCritica]['strict']['sha256'] ?? '')));
                $this->assertSame(64, strlen((string) ($json['huellas_logicas'][$tablaCritica]['functional']['sha256'] ?? '')));
            }

            $this->assertArrayHasKey('usuarios', $json['fotografia_funcional'] ?? []);
            $this->assertArrayHasKey('usuarios_sistema', $json['fotografia_funcional'] ?? []);
            $this->assertArrayHasKey('mantenimientos', $json['fotografia_funcional'] ?? []);
            $this->assertArrayHasKey('configuracion', $json['fotografia_funcional'] ?? []);
            $this->assertArrayHasKey('integridad', $json['fotografia_funcional'] ?? []);

            $estadosMantenimiento = array_keys($json['fotografia_funcional']['mantenimientos']['por_estado'] ?? []);
            foreach ($estadosMantenimiento as $estado) {
                $this->assertContains($estado, ['pendiente', 'programado', 'realizado', 'cancelado']);
            }

            $this->assertSame(0, (int) ($json['fotografia_funcional']['integridad']['total_huerfanos'] ?? -1));
            $this->assertSame(0, (int) ($json['fotografia_funcional']['integridad']['duplicados_propuesta_unica']['usuarios.codigo_activo'] ?? -1));
            $this->assertSame(0, (int) ($json['fotografia_funcional']['integridad']['duplicados_propuesta_unica']['usuarios.serial_equipo'] ?? -1));
            $this->assertSame(0, (int) ($json['fotografia_funcional']['integridad']['duplicados_propuesta_unica']['usuarios_sistema.correo'] ?? -1));
            $this->assertSame(0, (int) ($json['fotografia_funcional']['integridad']['duplicados_propuesta_unica']['configuracion_sistema.clave'] ?? -1));

            $this->assertIsArray($json['hallazgos_conocidos'] ?? null);
            $this->assertNotEmpty($json['hallazgos_conocidos'] ?? []);
        } finally {
            if (is_string($output) && file_exists($output)) {
                @unlink($output);
            }
        }
    }
}
