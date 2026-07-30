<?php

namespace Tests\Feature;

use App\Services\Legacy\MantenimientoRepository;
use App\Services\Legacy\PeriodicidadMantenimientoService;
use App\Services\Legacy\UsuarioMantenimientoResumenService;
use App\Services\SemestreService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PeriodicidadMantenimientoTest extends TestCase
{
    use DatabaseTransactions;

    private function servicioPeriodicidad(): PeriodicidadMantenimientoService
    {
        $this->app->forgetInstance(PeriodicidadMantenimientoService::class);

        return $this->app->make(PeriodicidadMantenimientoService::class);
    }

    public function test_configuracion_valida_retorna_valores_reales(): void
    {
        $servicio = $this->servicioPeriodicidad();

        $this->assertSame(6, $servicio->cicloMantenimientoMeses());
        $this->assertSame(12, $servicio->primerMantenimientoNuevoMeses());
        $this->assertSame(7, $servicio->recordatorioDiasAntes());
    }

    public function test_configuracion_ausente_usa_predeterminados_seguro(): void
    {
        DB::table('configuracion_sistema')
            ->whereIn('clave', [
                'ciclo_mantenimiento_meses',
                'primer_mantenimiento_nuevo_meses',
                'recordatorio_dias_antes',
            ])
            ->delete();

        $servicio = $this->servicioPeriodicidad();

        $this->assertSame(6, $servicio->cicloMantenimientoMeses());
        $this->assertSame(12, $servicio->primerMantenimientoNuevoMeses());
        $this->assertSame(7, $servicio->recordatorioDiasAntes());
    }

    public function test_configuracion_invalida_usa_predeterminados_seguro(): void
    {
        DB::table('configuracion_sistema')
            ->where('clave', 'ciclo_mantenimiento_meses')
            ->update(['valor' => '0', 'tipo' => 'entero']);

        DB::table('configuracion_sistema')
            ->where('clave', 'primer_mantenimiento_nuevo_meses')
            ->update(['valor' => '-3', 'tipo' => 'entero']);

        DB::table('configuracion_sistema')
            ->where('clave', 'recordatorio_dias_antes')
            ->update(['valor' => 'abc', 'tipo' => 'entero']);

        $servicio = $this->servicioPeriodicidad();

        $this->assertSame(6, $servicio->cicloMantenimientoMeses());
        $this->assertSame(12, $servicio->primerMantenimientoNuevoMeses());
        $this->assertSame(7, $servicio->recordatorioDiasAntes());
    }

    public function test_equivalencia_con_comportamiento_anterior(): void
    {
        $servicio = $this->servicioPeriodicidad();

        $this->assertSame('2026-07-15', $servicio->fechaProximaDesde('2026-01-15', $servicio->cicloMantenimientoMeses()));
        $this->assertSame('2027-01-15', $servicio->fechaProximaParaTipoEquipo('2026-01-15', 'nuevo'));
        $this->assertSame('1 mes', $servicio->etiquetaMeses(1));
        $this->assertSame('6 meses', $servicio->etiquetaMeses(6));
        $this->assertSame('1 anio', $servicio->etiquetaMeses(12));
    }

    public function test_resumen_prioriza_historial_real_sobre_fecha_base(): void
    {
        $periodo = app(SemestreService::class)->actual();
        $usuarioId = DB::table('usuarios')->insertGetId([
            'area_id' => null,
            'nombre' => 'Usuario Prioridad Historial',
            'codigo_activo' => 994001,
            'serial_equipo' => 'SER-PRI-001',
            'tipo_equipo' => 'normal',
            'fecha_ultimo_mantenimiento_base' => '2026-01-10',
            'estado_usuario' => 1,
            'ultima_fecha_ejecucion' => null,
            'ultima_fecha_mantenimiento' => null,
            'proxima_fecha_mantenimiento' => null,
            'ultimo_mantenimiento_id' => null,
            'ultimo_mantenimiento_estado' => null,
            'mantenimiento_resumen_actualizado_at' => null,
            'created_at' => now()->subYear(),
            'updated_at' => now(),
        ]);

        DB::table('mantenimientos')->insert([
            'usuario_id' => $usuarioId,
            'fecha_programada' => '2026-03-18',
            'hora_programada' => null,
            'estado' => 'realizado',
            'fecha_ejecucion' => '2026-03-18',
            'tecnico_responsable' => 'Tecnico Base',
            'observaciones' => 'Mantenimiento real de prueba.',
            'evidencia_pdf' => null,
            'created_at' => now()->subMonths(4),
            'updated_at' => now()->subMonths(4),
        ]);

        app(UsuarioMantenimientoResumenService::class)->sincronizarResumenUsuario($usuarioId, $periodo);

        $usuario = DB::table('usuarios')
            ->select('ultima_fecha_mantenimiento', 'proxima_fecha_mantenimiento')
            ->where('id', $usuarioId)
            ->first();

        $this->assertSame('2026-03-18', (string) ($usuario->ultima_fecha_mantenimiento ?? ''));
        $this->assertSame('2026-09-18', (string) ($usuario->proxima_fecha_mantenimiento ?? ''));
    }

    public function test_sincronizacion_de_proximos_es_idempotente_y_no_duplica_pendientes(): void
    {
        $periodo = app(SemestreService::class)->actual();
        $usuarioId = DB::table('usuarios')->insertGetId([
            'area_id' => null,
            'nombre' => 'Usuario Idempotencia',
            'codigo_activo' => 994002,
            'serial_equipo' => 'SER-IDO-001',
            'tipo_equipo' => 'normal',
            'fecha_ultimo_mantenimiento_base' => null,
            'estado_usuario' => 1,
            'ultima_fecha_ejecucion' => '2026-01-15',
            'ultima_fecha_mantenimiento' => '2026-01-15',
            'proxima_fecha_mantenimiento' => '2026-07-15',
            'ultimo_mantenimiento_id' => null,
            'ultimo_mantenimiento_estado' => 'realizado',
            'mantenimiento_resumen_actualizado_at' => now(),
            'created_at' => now()->subYear(),
            'updated_at' => now(),
        ]);

        DB::table('mantenimientos')->insert([
            'usuario_id' => $usuarioId,
            'fecha_programada' => '2026-01-15',
            'hora_programada' => null,
            'estado' => 'realizado',
            'fecha_ejecucion' => '2026-01-15',
            'tecnico_responsable' => 'Tecnico Base',
            'observaciones' => 'Mantenimiento real base.',
            'evidencia_pdf' => null,
            'created_at' => now()->subMonths(7),
            'updated_at' => now()->subMonths(7),
        ]);

        $repo = app(MantenimientoRepository::class);
        $repo->sincronizarPendientesProximosPorPeriodo($periodo);
        $repo->sincronizarPendientesProximosPorPeriodo($periodo);

        $pendientes = DB::table('mantenimientos')
            ->where('usuario_id', $usuarioId)
            ->where('estado', 'pendiente')
            ->count();

        $this->assertSame(1, $pendientes);
    }
}
