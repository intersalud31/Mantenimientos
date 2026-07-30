<?php

namespace Tests\Feature;

use App\Services\Legacy\UsuarioMantenimientoResumenService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MantenimientoResumenesCommandTest extends TestCase
{
    use DatabaseTransactions;

    public function test_audita_en_dry_run_sin_modificar_resumenes(): void
    {
        [$usuarioId, $esperado] = $this->crearUsuarioConResumenCorrecto();

        DB::table('usuarios')->where('id', $usuarioId)->update([
            'proxima_fecha_mantenimiento' => null,
            'ultimo_mantenimiento_estado' => 'pendiente',
            'updated_at' => now(),
        ]);

        $antes = DB::table('usuarios')
            ->where('id', $usuarioId)
            ->first();

        $exitCode = Artisan::call('mantenimientos:resumenes', [
            '--usuario' => $usuarioId,
        ]);

        $this->assertSame(0, $exitCode);
        $salida = Artisan::output();
        $this->assertStringContainsString('modo=dry-run', $salida);
        $this->assertStringContainsString('resumenes_desactualizados=1', $salida);
        $this->assertStringContainsString('resumenes_actualizados=0', $salida);

        $despues = DB::table('usuarios')
            ->where('id', $usuarioId)
            ->first();

        $this->assertSame((string) ($antes->proxima_fecha_mantenimiento ?? ''), (string) ($despues->proxima_fecha_mantenimiento ?? ''));
        $this->assertSame((string) ($antes->ultimo_mantenimiento_estado ?? ''), (string) ($despues->ultimo_mantenimiento_estado ?? ''));
        $this->assertNotEmpty($esperado['proxima_fecha_mantenimiento'] ?? null);
    }

    public function test_reconstruye_resumenes_con_apply(): void
    {
        [$usuarioId, $esperado] = $this->crearUsuarioConResumenCorrecto();

        DB::table('usuarios')->where('id', $usuarioId)->update([
            'ultima_fecha_ejecucion' => null,
            'ultima_fecha_mantenimiento' => null,
            'proxima_fecha_mantenimiento' => null,
            'ultimo_mantenimiento_id' => null,
            'ultimo_mantenimiento_estado' => 'pendiente',
            'updated_at' => now(),
        ]);

        $exitCode = Artisan::call('mantenimientos:resumenes', [
            '--usuario' => $usuarioId,
            '--apply' => true,
        ]);

        $this->assertSame(0, $exitCode);
        $salida = Artisan::output();
        $this->assertStringContainsString('modo=apply', $salida);
        $this->assertStringContainsString('resumenes_actualizados=1', $salida);

        $despues = DB::table('usuarios')
            ->where('id', $usuarioId)
            ->first();

        $this->assertSame((string) ($esperado['ultima_fecha_ejecucion'] ?? ''), (string) ($despues->ultima_fecha_ejecucion ?? ''));
        $this->assertSame((string) ($esperado['ultima_fecha_mantenimiento'] ?? ''), (string) ($despues->ultima_fecha_mantenimiento ?? ''));
        $this->assertSame((string) ($esperado['proxima_fecha_mantenimiento'] ?? ''), (string) ($despues->proxima_fecha_mantenimiento ?? ''));
        $this->assertSame((int) ($esperado['ultimo_mantenimiento_id'] ?? 0), (int) ($despues->ultimo_mantenimiento_id ?? 0));
        $this->assertSame((string) ($esperado['ultimo_mantenimiento_estado'] ?? ''), (string) ($despues->ultimo_mantenimiento_estado ?? ''));
        $this->assertNotNull($despues->mantenimiento_resumen_actualizado_at);
    }

    /**
     * @return array{0:int,1:array<string,mixed>}
     */
    private function crearUsuarioConResumenCorrecto(): array
    {
        $usuarioId = DB::table('usuarios')->insertGetId([
            'area_id' => null,
            'nombre' => 'Usuario Resumen Prueba',
            'codigo_activo' => 990001,
            'serial_equipo' => 'SER-RESUMEN-001',
            'tipo_equipo' => 'normal',
            'fecha_ultimo_mantenimiento_base' => '2026-01-10',
            'estado_usuario' => 1,
            'created_at' => now()->subYear(),
            'updated_at' => now(),
        ]);

        DB::table('mantenimientos')->insert([
            'usuario_id' => $usuarioId,
            'fecha_programada' => '2026-03-18',
            'hora_programada' => null,
            'estado' => 'realizado',
            'fecha_ejecucion' => '2026-03-18',
            'tecnico_responsable' => 'Tecnico Resumen',
            'observaciones' => 'Mantenimiento base para resumen.',
            'evidencia_pdf' => null,
            'created_at' => now()->subMonths(6),
            'updated_at' => now()->subMonths(6),
        ]);

        app(UsuarioMantenimientoResumenService::class)->sincronizarResumenUsuario($usuarioId);

        $esperado = (array) DB::table('usuarios')
            ->select(
                'ultima_fecha_ejecucion',
                'ultima_fecha_mantenimiento',
                'proxima_fecha_mantenimiento',
                'ultimo_mantenimiento_id',
                'ultimo_mantenimiento_estado',
                'mantenimiento_resumen_actualizado_at'
            )
            ->where('id', $usuarioId)
            ->first();

        return [$usuarioId, $esperado];
    }
}
