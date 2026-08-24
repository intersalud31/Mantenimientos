<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Legacy\NotificationSummaryService;
use App\Services\SemestreService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class NotificationSummaryTopbarTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();

        Carbon::setTestNow('2026-08-21 09:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_la_campana_muestra_conteos_reales_y_se_ve_en_varias_pantallas_del_panel(): void
    {
        $this->actingAs($this->makeLegacyUser(15001, 'Admin Notificaciones', 'notificaciones@example.com', 'admin'));

        $periodo = app(SemestreService::class)->actual();
        $fin = (string) $periodo['fin'];
        $hoy = today()->toDateString();
        $baseline = app(NotificationSummaryService::class)->resumen($periodo);

        $this->crearUsuario(
            'Equipo Vencido',
            990101,
            'normal',
            1,
            '2020-01-01'
        );

        $this->crearUsuario(
            'Equipo Proximo',
            990103,
            'normal',
            1,
            '2026-08-25'
        );

        $usuarioProgramadoHoy = $this->crearUsuario(
            'Equipo Programado Hoy',
            990102,
            'normal',
            1,
            date('Y-m-d', strtotime($fin . ' +15 day'))
        );

        DB::table('mantenimientos')->insert([
            'usuario_id' => $usuarioProgramadoHoy,
            'fecha_programada' => $hoy,
            'hora_programada' => null,
            'estado' => 'programado',
            'fecha_ejecucion' => null,
            'observaciones' => 'Programado para hoy.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $summary = app(NotificationSummaryService::class)->resumen($periodo);
        $this->assertSame(($baseline['mantenimientos_vencidos'] ?? 0) + 1, $summary['mantenimientos_vencidos'] ?? 0);
        $this->assertSame(($baseline['mantenimientos_proximos'] ?? 0) + 1, $summary['mantenimientos_proximos'] ?? 0);
        $this->assertSame(($baseline['mantenimientos_hoy'] ?? 0) + 1, $summary['mantenimientos_hoy'] ?? 0);
        $this->assertSame(($baseline['total'] ?? 0) + 3, $summary['total'] ?? 0);
        $this->assertCount(3, $summary['items'] ?? []);

        foreach ([
            'panel.proximos',
            'panel.usuarios',
            'panel.reportes',
            'panel.configuracion',
        ] as $routeName) {
            $response = $this->get(route($routeName, [
                'anio' => (int) $periodo['anio'],
                'semestre' => (int) $periodo['semestre'],
            ]));

            $expectedSummary = app(NotificationSummaryService::class)->resumen($periodo);
            $expectedTotal = (int) ($expectedSummary['total'] ?? 0);
            $expectedVencidos = (int) ($expectedSummary['mantenimientos_vencidos'] ?? 0);
            $expectedProximos = (int) ($expectedSummary['mantenimientos_proximos'] ?? 0);
            $expectedHoy = (int) ($expectedSummary['mantenimientos_hoy'] ?? 0);

            $response->assertOk();
            $response->assertSee('Notificaciones', false);
            $response->assertSee(((string) $expectedTotal) . ' notificaciones pendientes', false);
            $response->assertSee('Vencidos', false);
            $response->assertSee('Proximos 7 dias', false);
            $response->assertSee('Mantenimientos de hoy', false);
            $response->assertSee(((string) $expectedVencidos) . ' mantenimientos atrasados.', false);
            $response->assertSee(((string) $expectedProximos) . ' mantenimientos en los proximos 7 dias.', false);
            $response->assertSee(((string) $expectedHoy) . ' mantenimiento' . ($expectedHoy === 1 ? '' : 's') . ' programado' . ($expectedHoy === 1 ? '' : 's') . ' para hoy.', false);
            $response->assertSee(route('panel.notificaciones.vencidos'), false);
            $response->assertSee(route('panel.notificaciones.proximos'), false);
            $response->assertSee(route('panel.notificaciones.hoy'), false);
        }

        $this->get(route('panel.notificaciones.vencidos', ['anio' => 2026, 'semestre' => 2]))
            ->assertOk()
            ->assertSee('Mantenimientos vencidos', false)
            ->assertSee('Dias de atraso', false);

        $this->get(route('panel.notificaciones.proximos', ['anio' => 2026, 'semestre' => 2]))
            ->assertOk()
            ->assertSee('Proximos 7 dias', false)
            ->assertSee('Dias restantes', false);

        $this->get(route('panel.notificaciones.hoy', ['anio' => 2026, 'semestre' => 2]))
            ->assertOk()
            ->assertSee('Programados para hoy', false)
            ->assertSee('Tecnico asignado', false);
    }

    public function test_no_muestra_insignia_cuando_no_hay_notificaciones_y_ignora_ejecutados_y_cancelados(): void
    {
        $this->actingAs($this->makeLegacyUser(15002, 'Admin Sin Notificaciones', 'sin-notificaciones@example.com', 'admin'));

        $periodo = app(SemestreService::class)->actual();
        $hoy = today()->toDateString();
        $baseline = app(NotificationSummaryService::class)->resumen($periodo);

        $usuarioEjecutado = $this->crearUsuario('Equipo Ejecutado', 990201, 'normal', 1, '2026-08-20');
        $usuarioCancelado = $this->crearUsuario('Equipo Cancelado', 990202, 'normal', 1, $hoy);

        DB::table('mantenimientos')->insert([
            [
                'usuario_id' => $usuarioEjecutado,
                'fecha_programada' => $hoy,
                'hora_programada' => null,
                'estado' => 'realizado',
                'fecha_ejecucion' => $hoy,
                'observaciones' => 'Ejecutado hoy.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'usuario_id' => $usuarioCancelado,
                'fecha_programada' => $hoy,
                'hora_programada' => null,
                'estado' => 'cancelado',
                'fecha_ejecucion' => null,
                'observaciones' => 'Cancelado.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $summary = app(NotificationSummaryService::class)->resumen($periodo);
        $this->assertSame($baseline['mantenimientos_vencidos'] ?? -1, $summary['mantenimientos_vencidos'] ?? -1);
        $this->assertSame($baseline['mantenimientos_proximos'] ?? -1, $summary['mantenimientos_proximos'] ?? -1);
        $this->assertSame($baseline['mantenimientos_hoy'] ?? -1, $summary['mantenimientos_hoy'] ?? -1);
        $this->assertSame($baseline['total'] ?? -1, $summary['total'] ?? -1);

        app()->instance(NotificationSummaryService::class, new class () {
            public function resumen(?array $periodo = null): array
            {
                return [
                    'pendientes_programacion' => 0,
                    'mantenimientos_hoy' => 0,
                    'total' => 0,
                    'empty_message' => 'No tienes notificaciones pendientes.',
                    'items' => [
                        [
                            'title' => 'Pendientes de programaciÃ³n',
                            'description' => '0 equipos pendientes de programaciÃ³n.',
                            'link_label' => 'Ver prÃ³ximos',
                            'route' => 'panel.proximos',
                            'count' => 0,
                        ],
                        [
                            'title' => 'Mantenimientos de hoy',
                            'description' => '0 mantenimientos programados para hoy.',
                            'link_label' => 'Ver programados',
                            'route' => 'panel.programados',
                            'count' => 0,
                        ],
                    ],
                ];
            }
        });

        $response = $this->get(route('panel.usuarios', [
            'anio' => (int) $periodo['anio'],
            'semestre' => (int) $periodo['semestre'],
        ]));

        $response->assertOk();
        $response->assertSee('No tienes notificaciones pendientes.', false);
        $response->assertDontSee('app-notifications-count', false);
    }

    private function crearUsuario(
        string $nombre,
        int $codigoActivo,
        string $tipoEquipo,
        int $estado,
        ?string $proximaFecha
    ): int {
        return (int) DB::table('usuarios')->insertGetId([
            'area_id' => null,
            'nombre' => $nombre,
            'codigo_activo' => $codigoActivo,
            'serial_equipo' => 'SER-' . $codigoActivo,
            'tipo_equipo' => $tipoEquipo,
            'estado_usuario' => $estado,
            'fecha_ultimo_mantenimiento_base' => null,
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

