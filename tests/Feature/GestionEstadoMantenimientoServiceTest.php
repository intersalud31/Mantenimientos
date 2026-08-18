<?php

namespace Tests\Feature;

use App\Services\Legacy\GestionEstadoMantenimientoService;
use App\Services\Legacy\EstadoMantenimientoVigenteService;
use App\Services\SemestreService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GestionEstadoMantenimientoServiceTest extends TestCase
{
    use DatabaseTransactions;

    public function test_no_programado_a_programado_materializa_y_programa_el_mantenimiento(): void
    {
        $periodo = app(SemestreService::class)->actual();
        $usuarioId = $this->crearUsuario(996001, 'Usuario NP a P', $this->fechaFuturaHabil(3));
        $fechaProgramada = $this->fechaFuturaHabil(5);

        $service = app(GestionEstadoMantenimientoService::class);
        $preview = $service->previsualizarPorCodigoActivo(996001, EstadoMantenimientoVigenteService::PROGRAMADO, [
            'fecha_programada' => $fechaProgramada,
        ]);

        $this->assertTrue($preview['ok']);
        $this->assertSame(1, count($preview['preview']['registros_creados'] ?? []));

        $resultado = $service->ejecutarPorCodigoActivo(
            996001,
            EstadoMantenimientoVigenteService::PROGRAMADO,
            ['fecha_programada' => $fechaProgramada],
            'Programar desde configuración.',
            (string) ($preview['preview']['hash'] ?? ''),
            ['id' => 9001, 'nombre' => 'Admin Test']
        );

        $this->assertTrue($resultado['ok']);
        $this->assertSame('programado', DB::table('mantenimientos')->where('usuario_id', $usuarioId)->value('estado'));
        $this->assertSame($fechaProgramada, DB::table('mantenimientos')->where('usuario_id', $usuarioId)->value('fecha_programada'));
    }

    public function test_programado_a_no_programado_regresa_el_estado_a_pendiente(): void
    {
        $periodo = app(SemestreService::class)->actual();
        $usuarioId = $this->crearUsuario(996002, 'Usuario P a NP', null);
        $mantenimientoId = $this->crearMantenimiento($usuarioId, [
            'estado' => 'programado',
            'fecha_programada' => $this->fechaFuturaHabil(6),
            'observaciones' => 'Programado desde modulo Proximos.',
        ]);

        $service = app(GestionEstadoMantenimientoService::class);
        $preview = $service->previsualizarPorCodigoActivo(996002, EstadoMantenimientoVigenteService::NO_PROGRAMADO);

        $this->assertTrue($preview['ok']);
        $this->assertSame($mantenimientoId, (int) ($preview['preview']['mantenimiento_objetivo']['id'] ?? 0));

        $resultado = $service->ejecutarPorCodigoActivo(
            996002,
            EstadoMantenimientoVigenteService::NO_PROGRAMADO,
            [],
            'Regresar a pendiente.',
            (string) ($preview['preview']['hash'] ?? ''),
            ['id' => 9002, 'nombre' => 'Admin Test']
        );

        $this->assertTrue($resultado['ok']);
        $this->assertSame('pendiente', DB::table('mantenimientos')->where('id', $mantenimientoId)->value('estado'));
    }

    public function test_programado_a_ejecutado_reutiliza_flujo_de_hecho_y_genera_siguiente_pendiente(): void
    {
        $periodo = app(SemestreService::class)->actual();
        $usuarioId = $this->crearUsuario(996003, 'Usuario P a E', null);
        $fechaProgramada = '2026-08-19';
        $fechaEjecucion = '2026-08-17';
        $mantenimientoId = $this->crearMantenimiento($usuarioId, [
            'estado' => 'programado',
            'fecha_programada' => $fechaProgramada,
            'observaciones' => 'Programado desde modulo Proximos.',
        ]);
        $tecnicoId = $this->crearTecnico('Tecnico Gestion');

        $service = app(GestionEstadoMantenimientoService::class);
        $preview = $service->previsualizarPorCodigoActivo(996003, EstadoMantenimientoVigenteService::EJECUTADO, [
            'fecha_ejecucion' => $fechaEjecucion,
            'tecnico_id' => $tecnicoId,
            'observaciones' => 'Ejecución controlada.',
        ]);

        $this->assertTrue($preview['ok']);

        $resultado = $service->ejecutarPorCodigoActivo(
            996003,
            EstadoMantenimientoVigenteService::EJECUTADO,
            [
                'fecha_ejecucion' => $fechaEjecucion,
                'tecnico_id' => $tecnicoId,
                'observaciones' => 'Ejecución controlada.',
                'confirmar_ejecucion_antes_programacion' => true,
                'constancia_pdf_path' => 'constancias/prueba.pdf',
            ],
            'Ejecutar desde configuración.',
            (string) ($preview['preview']['hash'] ?? ''),
            ['id' => 9003, 'nombre' => 'Admin Test']
        );

        $this->assertTrue($resultado['ok']);
        $this->assertSame('realizado', DB::table('mantenimientos')->where('id', $mantenimientoId)->value('estado'));
        $this->assertSame($fechaEjecucion, DB::table('mantenimientos')->where('id', $mantenimientoId)->value('fecha_ejecucion'));

        $pendientes = DB::table('mantenimientos')
            ->where('usuario_id', $usuarioId)
            ->where('estado', 'pendiente')
            ->get();

        $this->assertCount(1, $pendientes);
        $this->assertSame('2027-02-17', $pendientes[0]->fecha_programada);
        $this->assertSame($fechaEjecucion, DB::table('usuarios')->where('id', $usuarioId)->value('ultima_fecha_ejecucion'));
    }

    public function test_ejecutado_a_programado_elimina_pendiente_automatico_relacionado(): void
    {
        $periodo = app(SemestreService::class)->actual();
        $usuarioId = $this->crearUsuario(996004, 'Usuario E a P', null);
        $fechaProgramada = $this->fechaFuturaHabil(4);
        $fechaEjecucion = $this->fechaFuturaHabil(5);
        $mantenimientoId = $this->crearMantenimiento($usuarioId, [
            'estado' => 'realizado',
            'fecha_programada' => $fechaProgramada,
            'fecha_ejecucion' => $fechaEjecucion,
            'observaciones' => 'Hecho desde modulo Programados.',
            'tecnico_responsable' => 'Tecnico Base',
            'evidencia_pdf' => 'constancias/base.pdf',
        ]);

        $siguiente = app(\App\Services\Legacy\PeriodicidadMantenimientoService::class)->fechaProximaDesde($fechaEjecucion, 6);
        $pendienteId = $this->crearMantenimiento($usuarioId, [
            'estado' => 'pendiente',
            'fecha_programada' => $siguiente,
            'observaciones' => 'Generado automatico despues de marcar Hecho.',
        ]);

        $service = app(GestionEstadoMantenimientoService::class);
        $preview = $service->previsualizarPorCodigoActivo(996004, EstadoMantenimientoVigenteService::PROGRAMADO);

        $this->assertTrue($preview['ok']);
        $this->assertSame($pendienteId, (int) ($preview['preview']['pendiente_automatico']['id'] ?? 0));

        $resultado = $service->ejecutarPorCodigoActivo(
            996004,
            EstadoMantenimientoVigenteService::PROGRAMADO,
            [],
            'Regresar la ejecución a programado.',
            (string) ($preview['preview']['hash'] ?? ''),
            ['id' => 9004, 'nombre' => 'Admin Test']
        );

        $this->assertTrue($resultado['ok']);
        $this->assertSame('programado', DB::table('mantenimientos')->where('id', $mantenimientoId)->value('estado'));
        $this->assertNull(DB::table('mantenimientos')->where('id', $mantenimientoId)->value('fecha_ejecucion'));
        $this->assertNull(DB::table('mantenimientos')->where('id', $pendienteId)->value('id'));
    }

    public function test_ejecutado_a_no_programado_regresa_a_pendiente_y_elimina_el_automatico(): void
    {
        $usuarioId = $this->crearUsuario(996005, 'Usuario E a NP', null);
        $fechaProgramada = $this->fechaFuturaHabil(9);
        $fechaEjecucion = $this->fechaFuturaHabil(10);
        $mantenimientoId = $this->crearMantenimiento($usuarioId, [
            'estado' => 'realizado',
            'fecha_programada' => $fechaProgramada,
            'fecha_ejecucion' => $fechaEjecucion,
            'observaciones' => 'Hecho desde modulo Programados.',
            'tecnico_responsable' => 'Tecnico Base',
            'evidencia_pdf' => 'constancias/base.pdf',
        ]);

        $siguiente = app(\App\Services\Legacy\PeriodicidadMantenimientoService::class)->fechaProximaDesde($fechaEjecucion, 6);
        $pendienteId = $this->crearMantenimiento($usuarioId, [
            'estado' => 'pendiente',
            'fecha_programada' => $siguiente,
            'observaciones' => 'Generado automatico despues de marcar Hecho.',
        ]);

        $service = app(GestionEstadoMantenimientoService::class);
        $preview = $service->previsualizarPorCodigoActivo(996005, EstadoMantenimientoVigenteService::NO_PROGRAMADO);

        $this->assertTrue($preview['ok']);

        $resultado = $service->ejecutarPorCodigoActivo(
            996005,
            EstadoMantenimientoVigenteService::NO_PROGRAMADO,
            [],
            'Regresar el mantenimiento a pendiente.',
            (string) ($preview['preview']['hash'] ?? ''),
            ['id' => 9005, 'nombre' => 'Admin Test']
        );

        $this->assertTrue($resultado['ok']);
        $this->assertSame('pendiente', DB::table('mantenimientos')->where('id', $mantenimientoId)->value('estado'));
        $this->assertNull(DB::table('mantenimientos')->where('id', $pendienteId)->value('id'));
    }

    public function test_no_programado_a_ejecutado_realiza_la_operacion_compuesta(): void
    {
        $fechaBase = '2026-08-18';
        $usuarioId = $this->crearUsuario(996006, 'Usuario NP a E', $fechaBase);
        $tecnicoId = $this->crearTecnico('Tecnico Doble');
        $fechaProgramada = '2026-08-18';
        $fechaEjecucion = '2026-08-11';

        $service = app(GestionEstadoMantenimientoService::class);
        $preview = $service->previsualizarPorCodigoActivo(996006, EstadoMantenimientoVigenteService::EJECUTADO, [
            'fecha_programada' => $fechaProgramada,
            'fecha_ejecucion' => $fechaEjecucion,
            'tecnico_id' => $tecnicoId,
            'observaciones' => 'Operación compuesta.',
        ]);

        $this->assertTrue($preview['ok']);
        $this->assertSame(1, count($preview['preview']['registros_creados'] ?? []));
        $this->assertNotEmpty($preview['preview']['advertencias'] ?? []);

        $resultado = $service->ejecutarPorCodigoActivo(
            996006,
            EstadoMantenimientoVigenteService::EJECUTADO,
            [
                'fecha_programada' => $fechaProgramada,
                'fecha_ejecucion' => $fechaEjecucion,
                'tecnico_id' => $tecnicoId,
                'observaciones' => 'Operación compuesta.',
                'confirmar_ejecucion_antes_programacion' => true,
                'constancia_pdf_path' => 'constancias/compuesta.pdf',
            ],
            'Ejecutar sin programado previo.',
            (string) ($preview['preview']['hash'] ?? ''),
            ['id' => 9006, 'nombre' => 'Admin Test']
        );

        $this->assertTrue($resultado['ok']);
        $this->assertSame(1, DB::table('mantenimientos')->where('usuario_id', $usuarioId)->where('estado', 'realizado')->count());
        $this->assertSame(
            $fechaEjecucion,
            DB::table('mantenimientos')->where('usuario_id', $usuarioId)->where('estado', 'realizado')->value('fecha_ejecucion')
        );
    }

    public function test_bloquea_usuario_inactivo_y_candidato_programado_ambiguo(): void
    {
        $service = app(GestionEstadoMantenimientoService::class);

        $usuarioInactivo = $this->crearUsuario(996007, 'Usuario Inactivo', null, 0);
        $bloqueado = $service->previsualizarPorCodigoActivo(996007, EstadoMantenimientoVigenteService::PROGRAMADO, [
            'fecha_programada' => $this->fechaFuturaHabil(14),
        ]);

        $this->assertFalse($bloqueado['ok']);
        $this->assertStringContainsString('inactivos', (string) ($bloqueado['mensaje'] ?? ''));

        $usuarioAmbiguo = $this->crearUsuario(996008, 'Usuario Ambiguo', null);
        $this->crearMantenimiento($usuarioAmbiguo, [
            'estado' => 'programado',
            'fecha_programada' => $this->fechaFuturaHabil(15),
            'observaciones' => 'Programado desde modulo Proximos.',
        ]);
        $this->crearMantenimiento($usuarioAmbiguo, [
            'estado' => 'programado',
            'fecha_programada' => $this->fechaFuturaHabil(16),
            'observaciones' => 'Programado desde modulo Proximos.',
        ]);

        $ambiguo = $service->previsualizarPorCodigoActivo(996008, EstadoMantenimientoVigenteService::NO_PROGRAMADO);

        $this->assertFalse($ambiguo['ok']);
        $this->assertStringContainsString('varios mantenimientos programados', (string) ($ambiguo['mensaje'] ?? ''));
    }

    public function test_previsualizacion_no_escribe_registros_y_bloquea_mismo_estado(): void
    {
        $fechaBase = $this->fechaFuturaHabil(17);
        $this->crearUsuario(996009, 'Usuario Preview Limpia', $fechaBase);

        $service = app(GestionEstadoMantenimientoService::class);
        $preview = $service->previsualizarPorCodigoActivo(996009, EstadoMantenimientoVigenteService::PROGRAMADO, [
            'fecha_programada' => $this->fechaFuturaHabil(18),
        ]);

        $this->assertTrue($preview['ok']);
        $this->assertSame(0, DB::table('mantenimientos')->where('usuario_id', DB::table('usuarios')->where('codigo_activo', 996009)->value('id'))->count());

        $mismoEstado = $service->previsualizarPorCodigoActivo(996009, EstadoMantenimientoVigenteService::NO_PROGRAMADO, [
            'fecha_programada' => $this->fechaFuturaHabil(18),
        ]);

        $this->assertFalse($mismoEstado['ok']);
        $this->assertStringContainsString('mismo estado actual', (string) ($mismoEstado['mensaje'] ?? ''));
    }

    public function test_bloquea_ejecutado_a_programado_si_falta_el_pendiente_automatico(): void
    {
        $usuarioId = $this->crearUsuario(996010, 'Usuario Sin Pendiente Auto', null);
        $this->crearMantenimiento($usuarioId, [
            'estado' => 'realizado',
            'fecha_programada' => $this->fechaFuturaHabil(19),
            'fecha_ejecucion' => $this->fechaFuturaHabil(20),
            'observaciones' => 'Hecho desde modulo Programados.',
            'tecnico_responsable' => 'Tecnico Base',
            'evidencia_pdf' => 'constancias/base.pdf',
        ]);

        $service = app(GestionEstadoMantenimientoService::class);
        $preview = $service->previsualizarPorCodigoActivo(996010, EstadoMantenimientoVigenteService::PROGRAMADO);

        $this->assertFalse($preview['ok']);
        $this->assertStringContainsString('pendiente automatico', (string) ($preview['mensaje'] ?? ''));
    }

    public function test_programado_a_ejecutado_rechaza_fecha_futura(): void
    {
        $usuarioId = $this->crearUsuario(996011, 'Usuario Fecha Futura', null);
        $this->crearMantenimiento($usuarioId, [
            'estado' => 'programado',
            'fecha_programada' => '2026-08-19',
            'observaciones' => 'Programado desde modulo Proximos.',
        ]);

        $service = app(GestionEstadoMantenimientoService::class);
        $preview = $service->previsualizarPorCodigoActivo(996011, EstadoMantenimientoVigenteService::EJECUTADO, [
            'fecha_ejecucion' => '2026-08-19',
            'tecnico_id' => $this->crearTecnico('Tecnico Futura'),
            'observaciones' => 'Debe bloquear futuro.',
        ]);

        $this->assertFalse($preview['ok']);
        $this->assertStringContainsString('no sea posterior a hoy', (string) ($preview['mensaje'] ?? ''));
    }

    public function test_no_programado_a_ejecutado_requiere_confirmacion_si_ejecucion_es_anterior_a_programacion(): void
    {
        $usuarioId = $this->crearUsuario(996012, 'Usuario Confirmacion Gestion', '2026-08-18');
        $tecnicoId = $this->crearTecnico('Tecnico Confirmacion Gestion');

        $service = app(GestionEstadoMantenimientoService::class);
        $preview = $service->previsualizarPorCodigoActivo(996012, EstadoMantenimientoVigenteService::EJECUTADO, [
            'fecha_programada' => '2026-08-18',
            'fecha_ejecucion' => '2026-08-17',
            'tecnico_id' => $tecnicoId,
            'observaciones' => 'Con advertencia.',
        ]);

        $this->assertTrue($preview['ok']);
        $this->assertNotEmpty($preview['preview']['advertencias'] ?? []);
        $this->assertTrue((bool) ($preview['preview']['metadata']['requiere_confirmacion_fecha_ejecucion_antes_programacion'] ?? false));

        $resultado = $service->ejecutarPorCodigoActivo(
            996012,
            EstadoMantenimientoVigenteService::EJECUTADO,
            [
                'fecha_programada' => '2026-08-18',
                'fecha_ejecucion' => '2026-08-17',
                'tecnico_id' => $tecnicoId,
                'observaciones' => 'Sin confirmacion.',
                'constancia_pdf_path' => 'constancias/confirmacion.pdf',
            ],
            'Debe pedir confirmacion.',
            (string) ($preview['preview']['hash'] ?? ''),
            ['id' => 9012, 'nombre' => 'Admin Test']
        );

        $this->assertFalse($resultado['ok']);
        $this->assertStringContainsString('Confirma que la informacion sea correcta', (string) ($resultado['mensaje'] ?? ''));
    }

    public function test_no_programado_a_ejecutado_con_fecha_del_mes_anterior_calcula_siguiente_desde_la_fecha_real_y_no_duplica_pendientes(): void
    {
        $usuarioId = $this->crearUsuario(996013, 'Usuario Mes Anterior', '2026-08-18');
        $tecnicoId = $this->crearTecnico('Tecnico Mes Anterior');
        $service = app(GestionEstadoMantenimientoService::class);

        $preview = $service->previsualizarPorCodigoActivo(996013, EstadoMantenimientoVigenteService::EJECUTADO, [
            'fecha_programada' => '2026-08-18',
            'fecha_ejecucion' => '2026-07-18',
            'tecnico_id' => $tecnicoId,
            'observaciones' => 'Registro posterior con fecha real del mes anterior.',
        ]);

        $this->assertTrue($preview['ok']);

        $resultado = $service->ejecutarPorCodigoActivo(
            996013,
            EstadoMantenimientoVigenteService::EJECUTADO,
            [
                'fecha_programada' => '2026-08-18',
                'fecha_ejecucion' => '2026-07-18',
                'tecnico_id' => $tecnicoId,
                'observaciones' => 'Registro posterior con fecha real del mes anterior.',
                'confirmar_ejecucion_antes_programacion' => true,
                'constancia_pdf_path' => 'constancias/mes-anterior.pdf',
            ],
            'Registrar mantenimiento historico.',
            (string) ($preview['preview']['hash'] ?? ''),
            ['id' => 9013, 'nombre' => 'Admin Test']
        );

        $this->assertTrue($resultado['ok']);
        $this->assertSame('2026-07-18', DB::table('usuarios')->where('id', $usuarioId)->value('ultima_fecha_ejecucion'));
        $this->assertSame(1, DB::table('mantenimientos')->where('usuario_id', $usuarioId)->where('estado', 'pendiente')->count());
        $this->assertSame('2027-01-18', DB::table('mantenimientos')->where('usuario_id', $usuarioId)->where('estado', 'pendiente')->value('fecha_programada'));
        $estadoActual = app(EstadoMantenimientoVigenteService::class)->resolverParaUsuario($usuarioId, app(SemestreService::class)->actual());
        $this->assertSame(EstadoMantenimientoVigenteService::EJECUTADO, $estadoActual['estado'] ?? null);
    }

    public function test_no_programado_a_ejecutado_con_fecha_del_30_de_junio_permanece_en_s1_y_recalcula_el_estado_vigente_en_s2(): void
    {
        $usuarioId = $this->crearUsuario(996014, 'Usuario Limite Junio', '2026-08-18');
        $tecnicoId = $this->crearTecnico('Tecnico Junio');
        $service = app(GestionEstadoMantenimientoService::class);

        $preview = $service->previsualizarPorCodigoActivo(996014, EstadoMantenimientoVigenteService::EJECUTADO, [
            'fecha_programada' => '2026-08-18',
            'fecha_ejecucion' => '2026-06-30',
            'tecnico_id' => $tecnicoId,
            'observaciones' => 'Registro de S1 durante S2.',
        ]);

        $this->assertTrue($preview['ok']);

        $resultado = $service->ejecutarPorCodigoActivo(
            996014,
            EstadoMantenimientoVigenteService::EJECUTADO,
            [
                'fecha_programada' => '2026-08-18',
                'fecha_ejecucion' => '2026-06-30',
                'tecnico_id' => $tecnicoId,
                'observaciones' => 'Registro de S1 durante S2.',
                'confirmar_ejecucion_antes_programacion' => true,
                'constancia_pdf_path' => 'constancias/junio.pdf',
            ],
            'Registrar ejecucion del semestre anterior.',
            (string) ($preview['preview']['hash'] ?? ''),
            ['id' => 9014, 'nombre' => 'Admin Test']
        );

        $this->assertTrue($resultado['ok']);
        $this->assertSame('2026-12-30', DB::table('mantenimientos')->where('usuario_id', $usuarioId)->where('estado', 'pendiente')->value('fecha_programada'));
        $estadoActual = app(EstadoMantenimientoVigenteService::class)->resolverParaUsuario($usuarioId, app(SemestreService::class)->actual());
        $this->assertSame(EstadoMantenimientoVigenteService::NO_PROGRAMADO, $estadoActual['estado'] ?? null);
    }

    public function test_no_programado_a_ejecutado_con_fecha_del_1_de_julio_se_clasifica_en_s2(): void
    {
        $usuarioId = $this->crearUsuario(996015, 'Usuario Limite Julio', '2026-08-18');
        $tecnicoId = $this->crearTecnico('Tecnico Julio');
        $service = app(GestionEstadoMantenimientoService::class);

        $preview = $service->previsualizarPorCodigoActivo(996015, EstadoMantenimientoVigenteService::EJECUTADO, [
            'fecha_programada' => '2026-08-18',
            'fecha_ejecucion' => '2026-07-01',
            'tecnico_id' => $tecnicoId,
            'observaciones' => 'Registro en limite de S2.',
        ]);

        $this->assertTrue($preview['ok']);

        $resultado = $service->ejecutarPorCodigoActivo(
            996015,
            EstadoMantenimientoVigenteService::EJECUTADO,
            [
                'fecha_programada' => '2026-08-18',
                'fecha_ejecucion' => '2026-07-01',
                'tecnico_id' => $tecnicoId,
                'observaciones' => 'Registro en limite de S2.',
                'confirmar_ejecucion_antes_programacion' => true,
                'constancia_pdf_path' => 'constancias/julio.pdf',
            ],
            'Registrar ejecucion del semestre vigente.',
            (string) ($preview['preview']['hash'] ?? ''),
            ['id' => 9015, 'nombre' => 'Admin Test']
        );

        $this->assertTrue($resultado['ok']);
        $estadoActual = app(EstadoMantenimientoVigenteService::class)->resolverParaUsuario($usuarioId, app(SemestreService::class)->actual());
        $this->assertSame(EstadoMantenimientoVigenteService::EJECUTADO, $estadoActual['estado'] ?? null);
        $this->assertSame('2027-01-04', DB::table('mantenimientos')->where('usuario_id', $usuarioId)->where('estado', 'pendiente')->value('fecha_programada'));
    }

    private function crearUsuario(int $codigoActivo, string $nombre, ?string $proximaFecha, int $estado = 1): int
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
            'created_at' => now()->subMonths(8),
            'updated_at' => now(),
        ]);
    }

    private function crearMantenimiento(int $usuarioId, array $override): int
    {
        return (int) DB::table('mantenimientos')->insertGetId(array_merge([
            'usuario_id' => $usuarioId,
            'fecha_programada' => $this->fechaFuturaHabil(2),
            'hora_programada' => null,
            'estado' => 'pendiente',
            'fecha_ejecucion' => null,
            'tecnico_responsable' => null,
            'observaciones' => '',
            'evidencia_pdf' => null,
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ], $override));
    }

    private function crearTecnico(string $nombre): int
    {
        return (int) DB::table('tecnicos')->insertGetId([
            'nombre' => $nombre,
            'estado' => 1,
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(3),
        ]);
    }

    private function fechaFuturaHabil(int $dias): string
    {
        $fecha = now()->startOfDay()->addDays($dias);

        while ((int) $fecha->format('N') >= 6) {
            $fecha = $fecha->addDay();
        }

        return $fecha->format('Y-m-d');
    }
}
