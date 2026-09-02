<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Legacy\MantenimientoRepository;
use App\Services\SemestreService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardIndicadoresAuditablesTest extends TestCase
{
    use DatabaseTransactions;

    public function test_dashboard_enlaza_indicadores_a_detalles_auditables_del_mismo_periodo(): void
    {
        $this->actingAs($this->makeLegacyUser(14001, 'Admin Dashboard', 'dashboard@example.com', 'admin'));

        $periodo = app(SemestreService::class)->actual();
        $repo = app(MantenimientoRepository::class);
        $inicio = (string) $periodo['inicio'];
        $fechaHecho = date('Y-m-d', strtotime($inicio . ' + 5 day'));
        $fechaProgramado = date('Y-m-d', strtotime($inicio . ' + 12 day'));
        $fechaPendiente = date('Y-m-d', strtotime($inicio . ' + 19 day'));

        $usuarioHecho = $this->crearUsuarioDashboard('Usuario Hecho Dashboard', 981001, 1, $fechaHecho);
        $usuarioProgramado = $this->crearUsuarioDashboard('Usuario Programado Dashboard', 981002, 1, $fechaProgramado);
        $usuarioPendiente = $this->crearUsuarioDashboard('Usuario Pendiente Dashboard', 981003, 1, $fechaPendiente);
        $usuarioActivoSinBase = $this->crearUsuarioDashboard('Usuario Activo Sin Base Dashboard', 981004, 1, date('Y-m-d', strtotime($inicio . ' + 40 day')));
        $usuarioInactivo = $this->crearUsuarioDashboard('Usuario Inactivo Dashboard', 981005, 0, $fechaProgramado);

        DB::table('mantenimientos')->insert([
            [
                'usuario_id' => $usuarioHecho,
                'fecha_programada' => $fechaHecho,
                'hora_programada' => null,
                'estado' => 'realizado',
                'fecha_ejecucion' => $fechaHecho,
                'tecnico_responsable' => 'Tecnico Dashboard',
                'observaciones' => 'Ejecutado dentro del periodo.',
                'evidencia_pdf' => null,
                'created_at' => now()->subDays(4),
                'updated_at' => now()->subDays(4),
            ],
            [
                'usuario_id' => $usuarioProgramado,
                'fecha_programada' => $fechaProgramado,
                'hora_programada' => null,
                'estado' => 'programado',
                'fecha_ejecucion' => null,
                'tecnico_responsable' => 'Tecnico Dashboard',
                'observaciones' => 'Programado vigente.',
                'evidencia_pdf' => null,
                'created_at' => now()->subDays(3),
                'updated_at' => now()->subDays(3),
            ],
            [
                'usuario_id' => $usuarioPendiente,
                'fecha_programada' => $fechaPendiente,
                'hora_programada' => null,
                'estado' => 'pendiente',
                'fecha_ejecucion' => null,
                'tecnico_responsable' => null,
                'observaciones' => 'Pendiente del semestre.',
                'evidencia_pdf' => null,
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
            ],
            [
                'usuario_id' => $usuarioInactivo,
                'fecha_programada' => $fechaProgramado,
                'hora_programada' => null,
                'estado' => 'programado',
                'fecha_ejecucion' => null,
                'tecnico_responsable' => null,
                'observaciones' => 'No debe contarse por estar inactivo.',
                'evidencia_pdf' => null,
                'created_at' => now()->subDay(),
                'updated_at' => now()->subDay(),
            ],
        ]);

        $resumen = $repo->obtenerResumenSemestralActual($periodo);

        $dashboard = $this->get(route('panel.reportes', [
            'anio' => (int) $periodo['anio'],
            'semestre' => (int) $periodo['semestre'],
        ]));

        $dashboard->assertOk();
        $dashboard->assertSee('Equipos a gestionar', false);
        $dashboard->assertDontSee('Base de fase</span>', false);
        $dashboard->assertSee(str_replace('&', '&amp;', route('panel.hechos', [
            'anio' => (int) $periodo['anio'],
            'semestre' => (int) $periodo['semestre'],
        ])), false);
        $dashboard->assertSee(str_replace('&', '&amp;', route('panel.programados', [
            'anio' => (int) $periodo['anio'],
            'semestre' => (int) $periodo['semestre'],
        ])), false);
        $dashboard->assertSee(str_replace('&', '&amp;', route('panel.reportes.pendientes', [
            'anio' => (int) $periodo['anio'],
            'semestre' => (int) $periodo['semestre'],
        ])), false);
        $dashboard->assertSee(str_replace('&', '&amp;', route('panel.reportes.base-fase', [
            'anio' => (int) $periodo['anio'],
            'semestre' => (int) $periodo['semestre'],
        ])), false);
        $dashboard->assertSee(str_replace('&', '&amp;', route('panel.usuarios', [
            'anio' => (int) $periodo['anio'],
            'semestre' => (int) $periodo['semestre'],
            'estado_usuario' => 1,
        ])), false);
        $dashboard->assertSee((string) ($resumen['total_base_fase'] ?? 0), false);

        $hechos = $this->get(route('panel.hechos', [
            'anio' => (int) $periodo['anio'],
            'semestre' => (int) $periodo['semestre'],
            'q_realizados' => 'Usuario Hecho Dashboard',
        ]));
        $hechos->assertOk();
        $hechos->assertSee('Total: 1 | Página 1 de 1', false);
        $hechos->assertSee('Usuario Hecho Dashboard', false);

        $programados = $this->get(route('panel.programados', [
            'anio' => (int) $periodo['anio'],
            'semestre' => (int) $periodo['semestre'],
            'q_programados' => 'Usuario Programado Dashboard',
        ]));
        $programados->assertOk();
        $programados->assertSee('Total: 1 | Página 1 de 1', false);
        $programados->assertSee('Usuario Programado Dashboard', false);

        $pendientes = $this->get(route('panel.reportes.pendientes', [
            'anio' => (int) $periodo['anio'],
            'semestre' => (int) $periodo['semestre'],
            'q_dashboard_pendientes' => 'Dashboard',
        ]));
        $pendientes->assertOk();
        $pendientes->assertSee('Total: 2 | Página 1 de 1', false);
        $pendientes->assertSee('Usuario Programado Dashboard', false);
        $pendientes->assertSee('Usuario Pendiente Dashboard', false);
        $pendientes->assertDontSee('Usuario Hecho Dashboard', false);

        $baseFase = $this->get(route('panel.reportes.base-fase', [
            'anio' => (int) $periodo['anio'],
            'semestre' => (int) $periodo['semestre'],
            'q_dashboard_base_fase' => 'Dashboard',
        ]));
        $baseFase->assertOk();
        $baseFase->assertSee('Equipos a gestionar', false);
        $baseFase->assertSee('Equipos incluidos en el periodo', false);
        $baseFase->assertSee('3 equipos requieren gestión durante el', false);
        $baseFase->assertSee('Total: 3 | Página 1 de 1', false);
        $baseFase->assertSee('Usuario Hecho Dashboard', false);
        $baseFase->assertSee('Usuario Programado Dashboard', false);
        $baseFase->assertSee('Usuario Pendiente Dashboard', false);
        $baseFase->assertDontSee('Usuario Activo Sin Base', false);
        $baseFase->assertDontSee('Base de fase del Dashboard', false);

        $usuariosActivos = $this->get(route('panel.usuarios', [
            'anio' => (int) $periodo['anio'],
            'semestre' => (int) $periodo['semestre'],
            'estado_usuario' => 1,
            'q_usuarios' => 'Dashboard',
        ]));
        $usuariosActivos->assertOk();
        $usuariosActivos->assertSee('4 registro(s)', false);
        $usuariosActivos->assertSee('Usuario Activo Sin Base Dashboard', false);
        $usuariosActivos->assertDontSee('Usuario Inactivo Dashboard', false);

        $this->assertSame($repo->contarBaseFasePorPeriodo('Dashboard', $periodo), 3);
        $this->assertSame($repo->contarPendientesDashboardPorPeriodo('Dashboard', $periodo), 2);
        $this->assertSame($repo->contarPorEstado('realizado', 'Usuario Hecho Dashboard', $periodo, true), 1);
        $this->assertSame($repo->contarPorEstado('programado', 'Usuario Programado Dashboard', $periodo), 1);
    }

    public function test_dashboard_muestra_grafico_de_distribucion_con_los_mismos_valores_y_enlaces_del_periodo(): void
    {
        $this->actingAs($this->makeLegacyUser(14011, 'Admin Distribucion', 'distribucion-dashboard@example.com', 'admin'));

        $periodo = app(SemestreService::class)->actual();
        $repo = app(MantenimientoRepository::class);
        $inicio = (string) $periodo['inicio'];
        $fin = (string) $periodo['fin'];
        $fechaHecho = date('Y-m-d', strtotime($inicio . ' + 4 day'));
        $fechaProgramado = date('Y-m-d', strtotime($inicio . ' + 10 day'));
        $fechaPendiente = date('Y-m-d', strtotime($inicio . ' + 17 day'));
        $fechaNoExigible = date('Y-m-d', strtotime($fin . ' + 40 day'));

        $usuarioHecho = $this->crearUsuarioDashboard('Grafico Ejecutado', 984001, 1, $fechaHecho);
        $usuarioProgramado = $this->crearUsuarioDashboard('Grafico Programado', 984002, 1, $fechaProgramado);
        $usuarioPendiente = $this->crearUsuarioDashboard('Grafico Pendiente', 984003, 1, $fechaPendiente);
        $this->crearUsuarioDashboard('Grafico No Exigible', 984004, 1, $fechaNoExigible);

        DB::table('mantenimientos')->insert([
            [
                'usuario_id' => $usuarioHecho,
                'fecha_programada' => $fechaHecho,
                'hora_programada' => null,
                'estado' => 'realizado',
                'fecha_ejecucion' => $fechaHecho,
                'tecnico_responsable' => 'Tecnico Grafico',
                'observaciones' => 'Ejecutado para gráfico.',
                'evidencia_pdf' => null,
                'created_at' => now()->subDays(3),
                'updated_at' => now()->subDays(3),
            ],
            [
                'usuario_id' => $usuarioProgramado,
                'fecha_programada' => $fechaProgramado,
                'hora_programada' => null,
                'estado' => 'programado',
                'fecha_ejecucion' => null,
                'tecnico_responsable' => 'Tecnico Grafico',
                'observaciones' => 'Programado para gráfico.',
                'evidencia_pdf' => null,
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
            ],
            [
                'usuario_id' => $usuarioPendiente,
                'fecha_programada' => $fechaPendiente,
                'hora_programada' => null,
                'estado' => 'pendiente',
                'fecha_ejecucion' => null,
                'tecnico_responsable' => null,
                'observaciones' => 'Pendiente para gráfico.',
                'evidencia_pdf' => null,
                'created_at' => now()->subDay(),
                'updated_at' => now()->subDay(),
            ],
        ]);

        $resumen = $repo->obtenerResumenSemestralActual($periodo);
        $totalDistribucion = (int) ($resumen['total_realizados'] ?? 0)
            + (int) ($resumen['total_programados'] ?? 0)
            + (int) ($resumen['total_pendientes'] ?? 0)
            + (int) ($resumen['total_no_exigibles'] ?? 0);

        $response = $this->get(route('panel.reportes', [
            'anio' => (int) $periodo['anio'],
            'semestre' => (int) $periodo['semestre'],
        ]));

        $response->assertOk();
        $response->assertSee('Distribución del periodo', false);
        $response->assertSee('Distribución operativa del semestre', false);
        $response->assertSee('Ejecutados', false);
        $response->assertSee('Programados', false);
        $response->assertSee('Pendientes', false);
        $response->assertSee('No exigibles', false);
        $response->assertSee('>' . $totalDistribucion . '</text>', false);
        $response->assertSee('Ir al detalle de ejecutados con ' . (int) ($resumen['total_realizados'] ?? 0) . ' registros', false);
        $response->assertSee('Ir al detalle de programados con ' . (int) ($resumen['total_programados'] ?? 0) . ' registros', false);
        $response->assertSee('Ir al detalle de pendientes con ' . (int) ($resumen['total_pendientes'] ?? 0) . ' registros', false);
        $response->assertSee('Ir al detalle de no exigibles con ' . (int) ($resumen['total_no_exigibles'] ?? 0) . ' registros', false);
        $response->assertSee(str_replace('&', '&amp;', route('panel.hechos', [
            'anio' => (int) $periodo['anio'],
            'semestre' => (int) $periodo['semestre'],
        ])), false);
        $response->assertSee(str_replace('&', '&amp;', route('panel.programados', [
            'anio' => (int) $periodo['anio'],
            'semestre' => (int) $periodo['semestre'],
        ])), false);
        $response->assertSee(str_replace('&', '&amp;', route('panel.reportes.pendientes', [
            'anio' => (int) $periodo['anio'],
            'semestre' => (int) $periodo['semestre'],
        ])), false);
        $response->assertSee(str_replace('&', '&amp;', route('panel.reportes.equipos-nuevos', [
            'anio' => (int) $periodo['anio'],
            'semestre' => (int) $periodo['semestre'],
        ])), false);
    }

    public function test_equipos_a_gestionar_incluye_los_cuatro_casos_conocidos_sin_duplicarlos(): void
    {
        $this->actingAs($this->makeLegacyUser(14013, 'Admin Pendientes Anteriores', 'pendientes-anteriores@example.com', 'admin'));

        $periodo = app(SemestreService::class)->actual();
        $repo = app(MantenimientoRepository::class);
        $inicio = (string) $periodo['inicio'];
        $fin = (string) $periodo['fin'];
        $fechaAnterior = date('Y-m-d', strtotime($inicio . ' - 65 day'));
        $fechaActualUno = date('Y-m-d', strtotime($inicio . ' + 10 day'));
        $fechaActualDos = date('Y-m-d', strtotime($inicio . ' + 25 day'));
        $fechaActualTres = date('Y-m-d', strtotime($inicio . ' + 40 day'));
        $fechaPosterior = date('Y-m-d', strtotime($fin . ' + 30 day'));

        $vanessa = $this->crearUsuarioDashboard('Vanessa Valdelamar', 984482, 1, $fechaAnterior);
        $pcMesa = $this->crearUsuarioDashboard('Pc Mesa', 984804, 1, $fechaActualUno);
        $veronica = $this->crearUsuarioDashboard('Verónica Díaz', 984252, 1, $fechaActualDos);
        $idelgardo = $this->crearUsuarioDashboard('Idelgardo Buelvas', 984671, 1, $fechaPosterior);
        $inactivo = $this->crearUsuarioDashboard('Pendiente Anterior Inactivo', 672, 0, $fechaAnterior);

        DB::table('mantenimientos')->insert([
            [
                'usuario_id' => $vanessa,
                'fecha_programada' => $fechaAnterior,
                'hora_programada' => null,
                'estado' => 'programado',
                'fecha_ejecucion' => null,
                'tecnico_responsable' => null,
                'observaciones' => 'Programado anterior abierto.',
                'evidencia_pdf' => null,
                'created_at' => now()->subDays(20),
                'updated_at' => now()->subDays(20),
            ],
            [
                'usuario_id' => $pcMesa,
                'fecha_programada' => $fechaAnterior,
                'hora_programada' => null,
                'estado' => 'pendiente',
                'fecha_ejecucion' => null,
                'tecnico_responsable' => null,
                'observaciones' => 'Pendiente anterior abierto.',
                'evidencia_pdf' => null,
                'created_at' => now()->subDays(19),
                'updated_at' => now()->subDays(19),
            ],
            [
                'usuario_id' => $pcMesa,
                'fecha_programada' => $fechaActualUno,
                'hora_programada' => null,
                'estado' => 'pendiente',
                'fecha_ejecucion' => null,
                'tecnico_responsable' => null,
                'observaciones' => 'Pendiente vigente.',
                'evidencia_pdf' => null,
                'created_at' => now()->subDays(5),
                'updated_at' => now()->subDays(5),
            ],
            [
                'usuario_id' => $veronica,
                'fecha_programada' => $fechaAnterior,
                'hora_programada' => null,
                'estado' => 'pendiente',
                'fecha_ejecucion' => null,
                'tecnico_responsable' => null,
                'observaciones' => 'Pendiente anterior abierto.',
                'evidencia_pdf' => null,
                'created_at' => now()->subDays(18),
                'updated_at' => now()->subDays(18),
            ],
            [
                'usuario_id' => $veronica,
                'fecha_programada' => $fechaActualDos,
                'hora_programada' => null,
                'estado' => 'programado',
                'fecha_ejecucion' => null,
                'tecnico_responsable' => null,
                'observaciones' => 'Programado vigente.',
                'evidencia_pdf' => null,
                'created_at' => now()->subDays(4),
                'updated_at' => now()->subDays(4),
            ],
            [
                'usuario_id' => $idelgardo,
                'fecha_programada' => $fechaAnterior,
                'hora_programada' => null,
                'estado' => 'pendiente',
                'fecha_ejecucion' => null,
                'tecnico_responsable' => null,
                'observaciones' => 'Pendiente anterior abierto.',
                'evidencia_pdf' => null,
                'created_at' => now()->subDays(17),
                'updated_at' => now()->subDays(17),
            ],
            [
                'usuario_id' => $idelgardo,
                'fecha_programada' => $fechaActualTres,
                'hora_programada' => null,
                'estado' => 'pendiente',
                'fecha_ejecucion' => null,
                'tecnico_responsable' => null,
                'observaciones' => 'Pendiente vigente.',
                'evidencia_pdf' => null,
                'created_at' => now()->subDays(3),
                'updated_at' => now()->subDays(3),
            ],
            [
                'usuario_id' => $inactivo,
                'fecha_programada' => $fechaAnterior,
                'hora_programada' => null,
                'estado' => 'pendiente',
                'fecha_ejecucion' => null,
                'tecnico_responsable' => null,
                'observaciones' => 'No debe contarse por estar inactivo.',
                'evidencia_pdf' => null,
                'created_at' => now()->subDays(16),
                'updated_at' => now()->subDays(16),
            ],
        ]);

        $baseFase = collect($repo->listarBaseFasePorPeriodo(50, 0, '', $periodo))
            ->keyBy('usuario_id');
        $noExigibles = collect($repo->listarNoExigiblesPorPeriodo(50, 0, '', $periodo))
            ->pluck('usuario_id')
            ->map(static fn ($id): int => (int) $id)
            ->all();

        $esperados = [$vanessa, $pcMesa, $veronica, $idelgardo];

        foreach ($esperados as $usuarioId) {
            $this->assertArrayHasKey($usuarioId, $baseFase->all());
            $this->assertNotContains($usuarioId, $noExigibles);
        }

        $this->assertArrayNotHasKey($inactivo, $baseFase->all());
        $this->assertSame(4, $baseFase->only($esperados)->count());
        $this->assertSame(4, $baseFase->only($esperados)->keys()->unique()->count());

        $filaVanessa = $baseFase[$vanessa];
        $this->assertSame('programado', $filaVanessa['estado_vigente'] ?? null);
        $this->assertSame(1, (int) ($filaVanessa['es_pendiente_anterior'] ?? 0));
        $this->assertSame('Pendiente de periodo anterior', $filaVanessa['situacion_etiqueta'] ?? null);
    }

    public function test_no_exigibles_excluye_pendientes_anteriores_abiertos_y_mantiene_historicos_resueltos(): void
    {
        $this->actingAs($this->makeLegacyUser(14014, 'Admin No Exigibles', 'no-exigibles-dashboard@example.com', 'admin'));

        $periodo = app(SemestreService::class)->actual();
        $repo = app(MantenimientoRepository::class);
        $inicio = (string) $periodo['inicio'];
        $fin = (string) $periodo['fin'];
        $fechaAnterior = date('Y-m-d', strtotime($inicio . ' - 70 day'));
        $fechaCierreAnterior = date('Y-m-d', strtotime($inicio . ' - 30 day'));
        $fechaFuera = date('Y-m-d', strtotime($fin . ' + 45 day'));

        $vanessa = $this->crearUsuarioDashboard('Vanessa Valdelamar No Exigible', 984210, 1, $fechaAnterior);
        $resuelto = $this->crearUsuarioDashboard('Historico Resuelto No Exigible', 984211, 1, $fechaFuera);
        $limpio = $this->crearUsuarioDashboard('No Exigible Limpio', 984212, 1, $fechaFuera);

        DB::table('mantenimientos')->insert([
            [
                'usuario_id' => $vanessa,
                'fecha_programada' => $fechaAnterior,
                'hora_programada' => null,
                'estado' => 'programado',
                'fecha_ejecucion' => null,
                'tecnico_responsable' => null,
                'observaciones' => 'Programado anterior abierto.',
                'evidencia_pdf' => null,
                'created_at' => now()->subDays(12),
                'updated_at' => now()->subDays(12),
            ],
            [
                'usuario_id' => $resuelto,
                'fecha_programada' => $fechaAnterior,
                'hora_programada' => null,
                'estado' => 'pendiente',
                'fecha_ejecucion' => null,
                'tecnico_responsable' => null,
                'observaciones' => 'Pendiente anterior ya resuelto.',
                'evidencia_pdf' => null,
                'created_at' => now()->subDays(11),
                'updated_at' => now()->subDays(11),
            ],
            [
                'usuario_id' => $resuelto,
                'fecha_programada' => $fechaCierreAnterior,
                'hora_programada' => null,
                'estado' => 'realizado',
                'fecha_ejecucion' => $fechaCierreAnterior,
                'tecnico_responsable' => 'Tecnico Cierre',
                'observaciones' => 'Cierre previo al semestre actual.',
                'evidencia_pdf' => null,
                'created_at' => now()->subDays(10),
                'updated_at' => now()->subDays(10),
            ],
        ]);

        $this->assertSame(1, $repo->contarBaseFasePorPeriodo('Vanessa Valdelamar No Exigible', $periodo));
        $this->assertSame(0, $repo->contarNoExigiblesPorPeriodo('Vanessa Valdelamar No Exigible', $periodo));
        $this->assertSame(0, $repo->contarBaseFasePorPeriodo('Historico Resuelto No Exigible', $periodo));
        $this->assertSame(1, $repo->contarNoExigiblesPorPeriodo('Historico Resuelto No Exigible', $periodo));
        $this->assertSame(1, $repo->contarNoExigiblesPorPeriodo('No Exigible Limpio', $periodo));
    }

    public function test_dashboard_renderiza_grafico_de_distribucion_sin_dividir_por_cero(): void
    {
        $this->actingAs($this->makeLegacyUser(14012, 'Admin Distribucion Cero', 'distribucion-cero@example.com', 'admin'));
        $repo = app(MantenimientoRepository::class);
        $periodo = [
            'anio' => 2099,
            'semestre' => 1,
            'inicio' => '2099-01-01',
            'fin' => '2099-06-30',
        ];
        $resumen = $repo->obtenerResumenSemestralActual($periodo);
        $totalDistribucion = (int) ($resumen['total_realizados'] ?? 0)
            + (int) ($resumen['total_programados'] ?? 0)
            + (int) ($resumen['total_pendientes'] ?? 0)
            + (int) ($resumen['total_no_exigibles'] ?? 0);

        $response = $this->get(route('panel.reportes', [
            'anio' => 2099,
            'semestre' => 1,
        ]));

        $response->assertOk();
        $response->assertSee('Distribución del periodo', false);
        $response->assertSee('>' . $totalDistribucion . '</text>', false);
        $response->assertSee(
            'Ejecutados ' . (int) ($resumen['total_realizados'] ?? 0)
            . ', Programados ' . (int) ($resumen['total_programados'] ?? 0)
            . ', Pendientes ' . (int) ($resumen['total_pendientes'] ?? 0)
            . ', No exigibles ' . (int) ($resumen['total_no_exigibles'] ?? 0),
            false
        );
    }

    public function test_detalle_de_pendientes_explica_cuando_dashboard_y_proximos_no_coinciden(): void
    {
        $this->actingAs($this->makeLegacyUser(14002, 'Admin Pendientes', 'pendientes-dashboard@example.com', 'admin'));

        $periodo = app(SemestreService::class)->actual();
        $repo = app(MantenimientoRepository::class);
        $inicio = (string) $periodo['inicio'];
        $fechaProgramado = date('Y-m-d', strtotime($inicio . ' + 8 day'));
        $fechaPendiente = date('Y-m-d', strtotime($inicio . ' + 18 day'));

        $usuarioProgramado = $this->crearUsuarioDashboard('Pendiente Programado', 982001, 1, $fechaProgramado);
        $usuarioPendiente = $this->crearUsuarioDashboard('Pendiente Próximo', 982002, 1, $fechaPendiente);

        DB::table('mantenimientos')->insert([
            [
                'usuario_id' => $usuarioProgramado,
                'fecha_programada' => $fechaProgramado,
                'hora_programada' => null,
                'estado' => 'programado',
                'fecha_ejecucion' => null,
                'tecnico_responsable' => null,
                'observaciones' => 'Debe estar en pendientes del dashboard, pero no en Próximos.',
                'evidencia_pdf' => null,
                'created_at' => now()->subDay(),
                'updated_at' => now()->subDay(),
            ],
            [
                'usuario_id' => $usuarioPendiente,
                'fecha_programada' => $fechaPendiente,
                'hora_programada' => null,
                'estado' => 'pendiente',
                'fecha_ejecucion' => null,
                'tecnico_responsable' => null,
                'observaciones' => 'Debe aparecer tanto en pendientes del dashboard como en Próximos.',
                'evidencia_pdf' => null,
                'created_at' => now()->subDay(),
                'updated_at' => now()->subDay(),
            ],
        ]);

        $this->assertSame(2, $repo->contarPendientesDashboardPorPeriodo('Pendiente', $periodo));
        $this->assertSame(1, $repo->contarProximosPorPeriodo('Pendiente', $periodo));

        $response = $this->get(route('panel.reportes.pendientes', [
            'anio' => (int) $periodo['anio'],
            'semestre' => (int) $periodo['semestre'],
            'q_dashboard_pendientes' => 'Pendiente',
        ]));

        $response->assertOk();
        $response->assertSee('Total: 2 | Página 1 de 1', false);
        $response->assertSee('Pendiente Programado', false);
        $response->assertSee('Pendiente Próximo', false);
        $response->assertSee('Programado vigente sin ejecución en el semestre.', false);
        $response->assertSee('Pendiente del semestre sin ejecución registrada.', false);
    }

    public function test_equipos_a_gestionar_deriva_la_situacion_sin_crear_estados_nuevos(): void
    {
        Carbon::setTestNow('2026-08-24 09:00:00');

        try {
            $this->actingAs($this->makeLegacyUser(14003, 'Admin Situacion', 'situacion-dashboard@example.com', 'admin'));

            $periodo = app(SemestreService::class)->actual();
            $repo = app(MantenimientoRepository::class);

            $usuarioEjecutado = $this->crearUsuarioDashboard('Situacion Ejecutado Dashboard', 983001, 1, '2027-02-15');
            $usuarioProgramado = $this->crearUsuarioDashboard('Situacion Programado Dashboard', 983002, 1, '2026-08-30');
            $usuarioVencido = $this->crearUsuarioDashboard('Situacion Vencido Dashboard', 983003, 1, '2026-08-18');
            $usuarioHoy = $this->crearUsuarioDashboard('Situacion Hoy Dashboard', 983004, 1, '2026-08-24');
            $usuarioAnterior = $this->crearUsuarioDashboard('Situacion Anterior Dashboard', 983005, 1, '2026-08-10');

            DB::table('mantenimientos')->insert([
                [
                    'usuario_id' => $usuarioEjecutado,
                    'fecha_programada' => '2026-07-15',
                    'hora_programada' => null,
                    'estado' => 'realizado',
                    'fecha_ejecucion' => '2026-07-15',
                    'tecnico_responsable' => 'Tecnico Uno',
                    'observaciones' => 'Cierre realizado.',
                    'evidencia_pdf' => null,
                    'created_at' => now()->subDays(10),
                    'updated_at' => now()->subDays(10),
                ],
                [
                    'usuario_id' => $usuarioProgramado,
                    'fecha_programada' => '2026-08-30',
                    'hora_programada' => null,
                    'estado' => 'programado',
                    'fecha_ejecucion' => null,
                    'tecnico_responsable' => 'Tecnico Dos',
                    'observaciones' => 'Programado vigente.',
                    'evidencia_pdf' => null,
                    'created_at' => now()->subDays(9),
                    'updated_at' => now()->subDays(9),
                ],
                [
                    'usuario_id' => $usuarioVencido,
                    'fecha_programada' => '2026-08-18',
                    'hora_programada' => null,
                    'estado' => 'pendiente',
                    'fecha_ejecucion' => null,
                    'tecnico_responsable' => null,
                    'observaciones' => 'Pendiente vencido.',
                    'evidencia_pdf' => null,
                    'created_at' => now()->subDays(8),
                    'updated_at' => now()->subDays(8),
                ],
                [
                    'usuario_id' => $usuarioHoy,
                    'fecha_programada' => '2026-08-24',
                    'hora_programada' => null,
                    'estado' => 'pendiente',
                    'fecha_ejecucion' => null,
                    'tecnico_responsable' => null,
                    'observaciones' => 'Pendiente de hoy.',
                    'evidencia_pdf' => null,
                    'created_at' => now()->subDays(7),
                    'updated_at' => now()->subDays(7),
                ],
                [
                    'usuario_id' => $usuarioAnterior,
                    'fecha_programada' => '2026-06-20',
                    'hora_programada' => null,
                    'estado' => 'pendiente',
                    'fecha_ejecucion' => null,
                    'tecnico_responsable' => null,
                    'observaciones' => 'Pendiente del periodo anterior.',
                    'evidencia_pdf' => null,
                    'created_at' => now()->subDays(20),
                    'updated_at' => now()->subDays(20),
                ],
                [
                    'usuario_id' => $usuarioAnterior,
                    'fecha_programada' => '2026-08-10',
                    'hora_programada' => null,
                    'estado' => 'pendiente',
                    'fecha_ejecucion' => null,
                    'tecnico_responsable' => null,
                    'observaciones' => 'Pendiente vigente sin cierre previo.',
                    'evidencia_pdf' => null,
                    'created_at' => now()->subDays(6),
                    'updated_at' => now()->subDays(6),
                ],
            ]);

            $response = $this->get(route('panel.reportes.base-fase', [
                'anio' => (int) $periodo['anio'],
                'semestre' => (int) $periodo['semestre'],
                'q_dashboard_base_fase' => 'Situacion',
            ]));

            $response->assertOk();
            $response->assertSee('Situación', false);
            $response->assertSee('Mantenimiento realizado', false);
            $response->assertSee('Programado para el 30/08/2026', false);
            $response->assertSee('Vencido hace 6 días', false);
            $response->assertSee('Requiere atención hoy', false);
            $response->assertSee('Pendiente de periodo anterior', false);
            $response->assertSee('Ejecutado', false);
            $response->assertSee('Programado', false);
            $response->assertSee('No programado', false);
            $response->assertDontSee('Estado relevante', false);
            $response->assertDontSee('Motivo', false);

            $this->assertSame('pendiente', DB::table('mantenimientos')->where('usuario_id', $usuarioVencido)->value('estado'));
            $this->assertSame(0, DB::table('mantenimientos')->where('estado', 'vencido')->count());
            $this->assertSame(5, $repo->contarBaseFasePorPeriodo('Situacion', $periodo));
        } finally {
            Carbon::setTestNow();
        }
    }

    private function crearUsuarioDashboard(string $nombre, int $codigoActivo, int $estado, string $proximaFecha): int
    {
        return (int) DB::table('usuarios')->insertGetId([
            'area_id' => null,
            'nombre' => $nombre,
            'codigo_activo' => $codigoActivo,
            'serial_equipo' => 'SER-' . $codigoActivo,
            'tipo_equipo' => 'normal',
            'estado_usuario' => $estado,
            'fecha_ultimo_mantenimiento_base' => '2026-01-15',
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

    private function makeLegacyUser(int $id, string $nombre, string $correo, string $rol): User
    {
        $user = new User();
        $user->setRawAttributes([
            'id' => $id,
            'nombre' => $nombre,
            'correo' => $correo,
            'rol' => $rol,
            'estado' => 1,
            'password_hash' => 'hashed-password',
        ], true);
        $user->exists = true;

        return $user;
    }
}
