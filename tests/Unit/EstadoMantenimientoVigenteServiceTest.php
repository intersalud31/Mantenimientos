<?php

namespace Tests\Unit;

use App\Services\Legacy\EstadoMantenimientoVigenteService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EstadoMantenimientoVigenteServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_resuelve_no_programado_desde_un_pendiente_vigente(): void
    {
        $usuarioId = $this->crearUsuarioActivo('Usuario Pendiente', 997001);
        DB::table('mantenimientos')->insert([
            'usuario_id' => $usuarioId,
            'fecha_programada' => '2026-08-20',
            'hora_programada' => null,
            'estado' => 'pendiente',
            'fecha_ejecucion' => null,
            'observaciones' => 'Pendiente vigente.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $estado = app(EstadoMantenimientoVigenteService::class)->resolverParaUsuario($usuarioId, [
            'anio' => 2026,
            'semestre' => 2,
            'inicio' => '2026-07-01',
            'fin' => '2026-12-31',
        ]);

        $this->assertSame('no_programado', $estado['estado'] ?? null);
        $this->assertSame('No programado', $estado['etiqueta'] ?? null);
    }

    public function test_usuario_activo_sin_registros_en_el_semestre_vigente_usa_proxima_fecha_y_queda_no_programado(): void
    {
        $usuarioId = $this->crearUsuarioActivo('Usuario Sin Registros Vigentes', 997012, 1, '2026-08-25');

        DB::table('mantenimientos')->insert([
            'usuario_id' => $usuarioId,
            'fecha_programada' => '2026-03-10',
            'hora_programada' => null,
            'estado' => 'realizado',
            'fecha_ejecucion' => '2026-03-10',
            'observaciones' => 'Solo historial S1.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $estado = app(EstadoMantenimientoVigenteService::class)->resolverParaUsuario($usuarioId, [
            'anio' => 2026,
            'semestre' => 2,
            'inicio' => '2026-07-01',
            'fin' => '2026-12-31',
        ]);

        $this->assertSame('no_programado', $estado['estado'] ?? null);
        $this->assertSame(1, $estado['prioridad'] ?? 0);
    }

    public function test_resuelve_programado_para_programado_y_reprogramado(): void
    {
        $programadoId = $this->crearUsuarioActivo('Usuario Programado', 997002);
        $reprogramadoId = $this->crearUsuarioActivo('Usuario Reprogramado', 997003);

        DB::table('mantenimientos')->insert([
            [
                'usuario_id' => $programadoId,
                'fecha_programada' => '2026-08-21',
                'hora_programada' => null,
                'estado' => 'programado',
                'fecha_ejecucion' => null,
                'observaciones' => 'Programado vigente.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'usuario_id' => $reprogramadoId,
                'fecha_programada' => '2026-08-22',
                'hora_programada' => null,
                'estado' => 'reprogramado',
                'fecha_ejecucion' => null,
                'observaciones' => 'Reprogramado vigente.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $estados = app(EstadoMantenimientoVigenteService::class)->resolverParaUsuarios([$programadoId, $reprogramadoId], [
            'anio' => 2026,
            'semestre' => 2,
            'inicio' => '2026-07-01',
            'fin' => '2026-12-31',
        ]);

        $this->assertSame('programado', $estados[$programadoId]['estado'] ?? null);
        $this->assertSame('programado', $estados[$reprogramadoId]['estado'] ?? null);
    }

    public function test_programado_anterior_abierto_se_mantiene_como_programado_en_el_estado_vigente(): void
    {
        $usuarioId = $this->crearUsuarioActivo('Usuario Programado Anterior Abierto', 997017, 1, '2026-04-27');

        DB::table('mantenimientos')->insert([
            'usuario_id' => $usuarioId,
            'fecha_programada' => '2026-04-27',
            'hora_programada' => null,
            'estado' => 'programado',
            'fecha_ejecucion' => null,
            'observaciones' => 'Programado del semestre anterior sin cierre.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $estado = app(EstadoMantenimientoVigenteService::class)->resolverParaUsuario($usuarioId, [
            'anio' => 2026,
            'semestre' => 2,
            'inicio' => '2026-07-01',
            'fin' => '2026-12-31',
        ]);

        $this->assertSame('programado', $estado['estado'] ?? null);
        $this->assertSame('Programado', $estado['etiqueta'] ?? null);
        $this->assertSame(2, $estado['prioridad'] ?? 0);
    }

    public function test_prioriza_ejecutado_sobre_programado_y_pendiente(): void
    {
        $usuarioId = $this->crearUsuarioActivo('Usuario Prioridad', 997004);

        DB::table('mantenimientos')->insert([
            [
                'usuario_id' => $usuarioId,
                'fecha_programada' => '2026-08-10',
                'hora_programada' => null,
                'estado' => 'pendiente',
                'fecha_ejecucion' => null,
                'observaciones' => 'Pendiente vigente.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'usuario_id' => $usuarioId,
                'fecha_programada' => '2026-08-11',
                'hora_programada' => null,
                'estado' => 'programado',
                'fecha_ejecucion' => null,
                'observaciones' => 'Programado vigente.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'usuario_id' => $usuarioId,
                'fecha_programada' => '2026-08-12',
                'hora_programada' => null,
                'estado' => 'realizado',
                'fecha_ejecucion' => '2026-08-12',
                'observaciones' => 'Realizado vigente.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $estado = app(EstadoMantenimientoVigenteService::class)->resolverParaUsuario($usuarioId, [
            'anio' => 2026,
            'semestre' => 2,
            'inicio' => '2026-07-01',
            'fin' => '2026-12-31',
        ]);

        $this->assertSame('ejecutado', $estado['estado'] ?? null);
        $this->assertSame(3, $estado['prioridad'] ?? 0);
    }

    public function test_catalogo_legacy_mantiene_hecho_como_alias_de_ejecutado(): void
    {
        $this->assertContains('hecho', \App\Services\Legacy\MantenimientoEstados::estadosVisiblesEnHechos());
    }

    public function test_ignora_cancelados_y_usuarios_inactivos(): void
    {
        $usuarioActivo = $this->crearUsuarioActivo('Usuario Cancelado', 997005);
        $usuarioInactivo = $this->crearUsuarioActivo('Usuario Inactivo', 997006, 0);

        DB::table('mantenimientos')->insert([
            [
                'usuario_id' => $usuarioActivo,
                'fecha_programada' => '2026-08-15',
                'hora_programada' => null,
                'estado' => 'cancelado',
                'fecha_ejecucion' => null,
                'observaciones' => 'Cancelado vigente.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'usuario_id' => $usuarioInactivo,
                'fecha_programada' => '2026-08-16',
                'hora_programada' => null,
                'estado' => 'programado',
                'fecha_ejecucion' => null,
                'observaciones' => 'Programado de usuario inactivo.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $estados = app(EstadoMantenimientoVigenteService::class)->resolverParaUsuarios([$usuarioActivo, $usuarioInactivo], [
            'anio' => 2026,
            'semestre' => 2,
            'inicio' => '2026-07-01',
            'fin' => '2026-12-31',
        ]);

        $this->assertNull($estados[$usuarioActivo]['estado'] ?? null);
        $this->assertArrayNotHasKey($usuarioInactivo, $estados);
    }

    public function test_ejecutado_en_s1_no_impide_no_programado_en_s2(): void
    {
        $usuarioId = $this->crearUsuarioActivo('Usuario Ciclo Semestral', 997007);

        DB::table('mantenimientos')->insert([
            [
                'usuario_id' => $usuarioId,
                'fecha_programada' => '2026-03-15',
                'hora_programada' => null,
                'estado' => 'realizado',
                'fecha_ejecucion' => '2026-03-15',
                'observaciones' => 'Ejecutado S1.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'usuario_id' => $usuarioId,
                'fecha_programada' => '2026-09-15',
                'hora_programada' => null,
                'estado' => 'pendiente',
                'fecha_ejecucion' => null,
                'observaciones' => 'Pendiente S2.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $service = app(EstadoMantenimientoVigenteService::class);

        $estadoS1 = $service->resolverParaUsuario($usuarioId, [
            'anio' => 2026,
            'semestre' => 1,
            'inicio' => '2026-01-01',
            'fin' => '2026-06-30',
        ]);
        $estadoS2 = $service->resolverParaUsuario($usuarioId, [
            'anio' => 2026,
            'semestre' => 2,
            'inicio' => '2026-07-01',
            'fin' => '2026-12-31',
        ]);

        $this->assertSame('ejecutado', $estadoS1['estado'] ?? null);
        $this->assertSame('no_programado', $estadoS2['estado'] ?? null);
    }

    public function test_resuelve_correctamente_los_limites_de_semestre(): void
    {
        $junioId = $this->crearUsuarioActivo('Usuario Junio', 997008);
        $julioId = $this->crearUsuarioActivo('Usuario Julio', 997009);
        $diciembreId = $this->crearUsuarioActivo('Usuario Diciembre', 997010);
        $eneroId = $this->crearUsuarioActivo('Usuario Enero', 997011);

        DB::table('mantenimientos')->insert([
            [
                'usuario_id' => $junioId,
                'fecha_programada' => '2026-06-30',
                'hora_programada' => null,
                'estado' => 'programado',
                'fecha_ejecucion' => null,
                'observaciones' => 'Límite S1.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'usuario_id' => $julioId,
                'fecha_programada' => '2026-07-01',
                'hora_programada' => null,
                'estado' => 'programado',
                'fecha_ejecucion' => null,
                'observaciones' => 'Límite S2.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'usuario_id' => $diciembreId,
                'fecha_programada' => '2026-12-31',
                'hora_programada' => null,
                'estado' => 'pendiente',
                'fecha_ejecucion' => null,
                'observaciones' => 'Fin de año.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'usuario_id' => $eneroId,
                'fecha_programada' => '2027-01-01',
                'hora_programada' => null,
                'estado' => 'pendiente',
                'fecha_ejecucion' => null,
                'observaciones' => 'Inicio de año.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $service = app(EstadoMantenimientoVigenteService::class);

        $estadoJunio = $service->resolverParaUsuario($junioId, ['anio' => 2026, 'semestre' => 1, 'inicio' => '2026-01-01', 'fin' => '2026-06-30']);
        $estadoJulio = $service->resolverParaUsuario($julioId, ['anio' => 2026, 'semestre' => 2, 'inicio' => '2026-07-01', 'fin' => '2026-12-31']);
        $estadoDiciembre = $service->resolverParaUsuario($diciembreId, ['anio' => 2026, 'semestre' => 2, 'inicio' => '2026-07-01', 'fin' => '2026-12-31']);
        $estadoEnero = $service->resolverParaUsuario($eneroId, ['anio' => 2027, 'semestre' => 1, 'inicio' => '2027-01-01', 'fin' => '2027-06-30']);

        $this->assertSame('programado', $estadoJunio['estado'] ?? null);
        $this->assertSame('programado', $estadoJulio['estado'] ?? null);
        $this->assertSame('no_programado', $estadoDiciembre['estado'] ?? null);
        $this->assertSame('no_programado', $estadoEnero['estado'] ?? null);
    }

    public function test_programacion_en_s1_y_ejecucion_en_s2_se_clasifica_solo_en_s2(): void
    {
        $usuarioId = $this->crearUsuarioActivo('Usuario Cruce S1 S2', 997013);

        DB::table('mantenimientos')->insert([
            'usuario_id' => $usuarioId,
            'fecha_programada' => '2026-06-30',
            'hora_programada' => null,
            'estado' => 'realizado',
            'fecha_ejecucion' => '2026-07-01',
            'observaciones' => 'Cruce entre semestres.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(EstadoMantenimientoVigenteService::class);

        $estadoS1 = $service->resolverParaUsuario($usuarioId, ['anio' => 2026, 'semestre' => 1, 'inicio' => '2026-01-01', 'fin' => '2026-06-30']);
        $estadoS2 = $service->resolverParaUsuario($usuarioId, ['anio' => 2026, 'semestre' => 2, 'inicio' => '2026-07-01', 'fin' => '2026-12-31']);

        $this->assertNull($estadoS1['estado'] ?? null);
        $this->assertSame('ejecutado', $estadoS2['estado'] ?? null);
    }

    public function test_programacion_en_s2_y_ejecucion_vacia_permanece_programado_en_s2(): void
    {
        $usuarioId = $this->crearUsuarioActivo('Usuario Programado S2', 997014);

        DB::table('mantenimientos')->insert([
            'usuario_id' => $usuarioId,
            'fecha_programada' => '2026-08-18',
            'hora_programada' => null,
            'estado' => 'programado',
            'fecha_ejecucion' => null,
            'observaciones' => 'Programado sin ejecutar.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $estado = app(EstadoMantenimientoVigenteService::class)->resolverParaUsuario($usuarioId, [
            'anio' => 2026,
            'semestre' => 2,
            'inicio' => '2026-07-01',
            'fin' => '2026-12-31',
        ]);

        $this->assertSame('programado', $estado['estado'] ?? null);
    }

    public function test_ejecucion_en_s1_no_se_arrastra_automaticamente_al_iniciar_s2(): void
    {
        $usuarioId = $this->crearUsuarioActivo('Usuario Ejecutado S1', 997015);

        DB::table('mantenimientos')->insert([
            'usuario_id' => $usuarioId,
            'fecha_programada' => '2026-06-20',
            'hora_programada' => null,
            'estado' => 'realizado',
            'fecha_ejecucion' => '2026-06-25',
            'observaciones' => 'Realizado al cierre de S1.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $service = app(EstadoMantenimientoVigenteService::class);

        $estadoS1 = $service->resolverParaUsuario($usuarioId, ['anio' => 2026, 'semestre' => 1, 'inicio' => '2026-01-01', 'fin' => '2026-06-30']);
        $estadoS2 = $service->resolverParaUsuario($usuarioId, ['anio' => 2026, 'semestre' => 2, 'inicio' => '2026-07-01', 'fin' => '2026-12-31']);

        $this->assertSame('ejecutado', $estadoS1['estado'] ?? null);
        $this->assertNull($estadoS2['estado'] ?? null);
    }

    public function test_usuario_sin_filas_vigentes_y_sin_proxima_fecha_no_recibe_estado(): void
    {
        $usuarioId = $this->crearUsuarioActivo('Usuario Sin Fechas', 997016);

        $estado = app(EstadoMantenimientoVigenteService::class)->resolverParaUsuario($usuarioId, [
            'anio' => 2026,
            'semestre' => 2,
            'inicio' => '2026-07-01',
            'fin' => '2026-12-31',
        ]);

        $this->assertNull($estado['estado'] ?? null);
    }

    private function crearUsuarioActivo(string $nombre, int $codigoActivo, int $estado = 1, ?string $proximaFecha = null): int
    {
        return (int) DB::table('usuarios')->insertGetId([
            'area_id' => null,
            'nombre' => $nombre,
            'codigo_activo' => $codigoActivo,
            'serial_equipo' => 'SER-' . $codigoActivo,
            'tipo_equipo' => 'normal',
            'estado_usuario' => $estado,
            'ultima_fecha_ejecucion' => null,
            'ultima_fecha_mantenimiento' => null,
            'proxima_fecha_mantenimiento' => $proximaFecha,
            'ultimo_mantenimiento_id' => null,
            'ultimo_mantenimiento_estado' => null,
            'mantenimiento_resumen_actualizado_at' => null,
            'created_at' => now()->subMonth(),
            'updated_at' => now(),
        ]);
    }
}
