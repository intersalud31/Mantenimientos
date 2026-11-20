<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class AuditarEvidenciasCommandTest extends TestCase
{
    public function test_audita_referencias_archivos_faltantes_y_huerfanos_sin_escribir(): void
    {
        Storage::fake('public');

        $antes = DB::table('mantenimientos')->count();
        $mantenimientoExistente = $this->crearMantenimiento('constancias/existente.pdf');
        $mantenimientoFaltante = $this->crearMantenimiento('constancias/faltante.pdf');
        try {
            Storage::disk('public')->put('constancias/existente.pdf', '%PDF-1.4');
            Storage::disk('public')->put('constancias/huerfano.pdf', '%PDF-1.4');

            $exitCode = Artisan::call('evidencias:auditar');
            $this->assertSame(0, $exitCode);
            $salida = json_decode(Artisan::output(), true, 512, JSON_THROW_ON_ERROR);

            $this->assertSame($antes + 2, DB::table('mantenimientos')->count());
            $existente = collect($salida['evidencias'])->firstWhere('ruta_registrada', 'constancias/existente.pdf');
            $faltante = collect($salida['evidencias'])->firstWhere('ruta_registrada', 'constancias/faltante.pdf');
            $this->assertNotNull($existente);
            $this->assertNotNull($faltante);
            $this->assertSame(1, $salida['resumen']['huerfanos']);
            $this->assertSame($antes + 2, DB::table('mantenimientos')->count());
            $this->assertSame($mantenimientoExistente, $existente['mantenimiento_id']);
            $this->assertSame(8, $existente['tamano_bytes']);
            $this->assertSame($mantenimientoFaltante, $faltante['mantenimiento_id']);
        } finally {
            DB::table('mantenimientos')->whereIn('id', [$mantenimientoExistente, $mantenimientoFaltante])->delete();
        }
    }

    private function crearMantenimiento(string $ruta): int
    {
        $usuarioId = (int) DB::table('usuarios')->insertGetId([
            'area_id' => null,
            'nombre' => 'Auditoria evidencia ' . uniqid(),
            'codigo_activo' => random_int(100000, 999999),
            'serial_equipo' => 'AUD-' . uniqid(),
            'tipo_equipo' => 'normal',
            'estado_usuario' => 1,
            'ultima_fecha_ejecucion' => null,
            'ultima_fecha_mantenimiento' => null,
            'proxima_fecha_mantenimiento' => null,
            'ultimo_mantenimiento_id' => null,
            'ultimo_mantenimiento_estado' => 'realizado',
            'mantenimiento_resumen_actualizado_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) DB::table('mantenimientos')->insertGetId([
            'usuario_id' => $usuarioId,
            'fecha_programada' => now()->toDateString(),
            'hora_programada' => null,
            'estado' => 'realizado',
            'fecha_ejecucion' => now()->toDateString(),
            'tecnico_responsable' => null,
            'evidencia_pdf' => $ruta,
            'observaciones' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
