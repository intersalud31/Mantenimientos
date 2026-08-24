<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Legacy\MantenimientoRepository;
use App\Services\SemestreService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportesEquiposNuevosTest extends TestCase
{
    use DatabaseTransactions;

    public function test_reporte_muestra_no_exigibles_reclasifica_vencidos_anteriores_y_mantiene_totales(): void
    {
        $this->actingAs($this->makeLegacyUser(13001, 'Admin Reportes Nuevos', 'reportes-nuevos@example.com', 'admin'));

        $periodo = app(SemestreService::class)->actual();
        $repo = app(MantenimientoRepository::class);
        $inicio = (string) $periodo['inicio'];
        $fin = (string) $periodo['fin'];
        $fechaAnterior = date('Y-m-d', strtotime($inicio . ' - 10 day'));
        $fechaPosterior = date('Y-m-d', strtotime($fin . ' + 10 day'));

        $usuarioNuevoFuera = $this->crearUsuario('Equipo Nuevo Fuera', 990001, 'nuevo', 1, $fechaPosterior, null);
        $usuarioNormalFuera = $this->crearUsuario('Equipo Normal Fuera', 990002, 'normal', 1, date('Y-m-d', strtotime($fin . ' + 15 day')), '2026-01-15');
        $usuarioNuevoDentro = $this->crearUsuario('Equipo Nuevo Dentro', 990003, 'nuevo', 1, date('Y-m-d', strtotime($inicio . ' + 10 day')), '2026-01-15');
        $usuarioVencidoAnterior = $this->crearUsuario('Equipo Vencido Anterior', 990004, 'normal', 1, date('Y-m-d', strtotime($inicio . ' + 30 day')), '2026-01-15');
        $usuarioCicloCerrado = $this->crearUsuario('Equipo Ciclo Cerrado', 990005, 'normal', 1, date('Y-m-d', strtotime($inicio . ' + 45 day')), '2026-01-15');
        $usuarioConActividad = $this->crearUsuario('Equipo Con Actividad', 990006, 'normal', 1, date('Y-m-d', strtotime($fin . ' + 12 day')), '2026-01-15');
        $usuarioInactivo = $this->crearUsuario('Equipo Inactivo', 990007, 'nuevo', 0, date('Y-m-d', strtotime($fin . ' + 20 day')), '2026-01-15');

        DB::table('mantenimientos')->insert([
            [
                'usuario_id' => $usuarioVencidoAnterior,
                'fecha_programada' => $fechaAnterior,
                'hora_programada' => null,
                'estado' => 'pendiente',
                'fecha_ejecucion' => null,
                'tecnico_responsable' => null,
                'observaciones' => 'Pendiente de periodo anterior sin cierre.',
                'evidencia_pdf' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'usuario_id' => $usuarioCicloCerrado,
                'fecha_programada' => $fechaAnterior,
                'hora_programada' => null,
                'estado' => 'pendiente',
                'fecha_ejecucion' => null,
                'tecnico_responsable' => null,
                'observaciones' => 'Pendiente historico ya cerrado despues.',
                'evidencia_pdf' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'usuario_id' => $usuarioCicloCerrado,
                'fecha_programada' => $fechaAnterior,
                'hora_programada' => null,
                'estado' => 'realizado',
                'fecha_ejecucion' => date('Y-m-d', strtotime($fechaAnterior . ' + 3 day')),
                'tecnico_responsable' => null,
                'observaciones' => 'Ciclo ya cerrado.',
                'evidencia_pdf' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'usuario_id' => $usuarioConActividad,
                'fecha_programada' => date('Y-m-d', strtotime($inicio . ' + 20 day')),
                'hora_programada' => null,
                'estado' => 'programado',
                'fecha_ejecucion' => null,
                'tecnico_responsable' => null,
                'observaciones' => 'Programado dentro del semestre vigente.',
                'evidencia_pdf' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $reportes = $this->get(route('panel.reportes', [
            'anio' => (int) $periodo['anio'],
            'semestre' => (int) $periodo['semestre'],
        ]));

        $reportes->assertOk();
        $reportes->assertSee('No exigibles', false);
        $reportes->assertSee('panel/reportes/equipos-nuevos?anio=' . (int) $periodo['anio'] . '&amp;semestre=' . (int) $periodo['semestre'], false);
        $reportes->assertDontSee('>Equipos no exigibles<', false);

        $response = $this->get(route('panel.reportes.equipos-nuevos', [
            'anio' => (int) $periodo['anio'],
            'semestre' => (int) $periodo['semestre'],
        ]));

        $response->assertOk();
        $response->assertSee('Equipos no exigibles', false);
        $response->assertSee('Equipo nuevo', false);
        $response->assertSee('Proximo mantenimiento fuera del semestre', false);
        $response->assertSee('Equipo Nuevo Fuera', false);
        $response->assertSee('Equipo Normal Fuera', false);
        $response->assertSee('—', false);
        $response->assertDontSee('&mdash;', false);
        $response->assertDontSee('Equipo Nuevo Dentro', false);
        $response->assertDontSee('Equipo Vencido Anterior', false);
        $response->assertDontSee('Equipo Ciclo Cerrado', false);
        $response->assertDontSee('Equipo Con Actividad', false);
        $response->assertDontSee('Equipo Inactivo', false);

        $items = $repo->listarNoExigiblesPorPeriodo(20, 0, '', $periodo);

        $filaNuevo = collect($items)->firstWhere('usuario_id', $usuarioNuevoFuera);
        $filaNormal = collect($items)->firstWhere('usuario_id', $usuarioNormalFuera);
        $filaVencidoAnterior = collect($items)->firstWhere('usuario_id', $usuarioVencidoAnterior);
        $filaCicloCerrado = collect($items)->firstWhere('usuario_id', $usuarioCicloCerrado);

        $this->assertNotNull($filaNuevo);
        $this->assertNotNull($filaNormal);
        $this->assertNull($filaVencidoAnterior);
        $this->assertNull($filaCicloCerrado);
        $this->assertSame('equipo_nuevo', $filaNuevo['motivo_codigo'] ?? null);
        $this->assertSame('fuera_periodo', $filaNormal['motivo_codigo'] ?? null);

        $proximos = $this->get(route('panel.proximos', [
            'anio' => (int) $periodo['anio'],
            'semestre' => (int) $periodo['semestre'],
        ]));

        $proximos->assertOk();
        $proximos->assertSee('Equipo Vencido Anterior', false);
        $proximos->assertSee('Pendiente anterior', false);

        $itemsProximos = $repo->listarProximosPorPeriodo(20, 0, '', $periodo);
        $filaProximoVencido = collect($itemsProximos)->firstWhere('usuario_id', $usuarioVencidoAnterior);
        $filaProximoCerrado = collect($itemsProximos)->firstWhere('usuario_id', $usuarioCicloCerrado);

        $this->assertNotNull($filaProximoVencido);
        $this->assertSame(1, (int) ($filaProximoVencido['es_vencido_anterior'] ?? 0));
        if ($filaProximoCerrado !== null) {
            $this->assertSame(0, (int) ($filaProximoCerrado['es_vencido_anterior'] ?? 0));
        }

        $responseFiltrado = $this->get(route('panel.reportes.equipos-nuevos', [
            'anio' => (int) $periodo['anio'],
            'semestre' => (int) $periodo['semestre'],
            'motivo_no_exigible' => 'equipo_nuevo',
        ]));

        $responseFiltrado->assertOk();
        $responseFiltrado->assertSee('Equipo Nuevo Fuera', false);
        $responseFiltrado->assertDontSee('Equipo Normal Fuera', false);

        $resumen = $repo->obtenerResumenSemestralActual($periodo);
        $this->assertSame($repo->contarNoExigiblesPorPeriodo('', $periodo), (int) ($resumen['total_no_exigibles'] ?? -1));
    }

    private function crearUsuario(
        string $nombre,
        int $codigoActivo,
        string $tipoEquipo,
        int $estado,
        ?string $proximaFecha,
        ?string $fechaBase
    ): int {
        return (int) DB::table('usuarios')->insertGetId([
            'area_id' => null,
            'nombre' => $nombre,
            'codigo_activo' => $codigoActivo,
            'serial_equipo' => 'SER-' . $codigoActivo,
            'tipo_equipo' => $tipoEquipo,
            'estado_usuario' => $estado,
            'fecha_ultimo_mantenimiento_base' => $fechaBase,
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
