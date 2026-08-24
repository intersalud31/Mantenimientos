<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\SemestreService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportesEquiposNuevosTest extends TestCase
{
    use DatabaseTransactions;

    public function test_reporte_muestra_solo_equipos_nuevos_fuera_del_semestre_y_acceso_desde_reportes(): void
    {
        $this->actingAs($this->makeLegacyUser(13001, 'Admin Reportes Nuevos', 'reportes-nuevos@example.com', 'admin'));

        $periodo = app(SemestreService::class)->actual();
        $inicio = (string) $periodo['inicio'];
        $fin = (string) $periodo['fin'];

        $usuarioNuevoFuera = $this->crearUsuario('Equipo Nuevo Fuera', 990001, 'nuevo', 1, '2027-01-15', '2026-01-15');
        $usuarioNuevoDentro = $this->crearUsuario('Equipo Nuevo Dentro', 990002, 'nuevo', 1, date('Y-m-d', strtotime($inicio . ' +10 day')), '2026-01-15');
        $usuarioNormalFuera = $this->crearUsuario('Equipo Normal Fuera', 990003, 'normal', 1, date('Y-m-d', strtotime($fin . ' +10 day')), '2026-01-15');
        $usuarioNuevoInactivo = $this->crearUsuario('Equipo Nuevo Inactivo', 990004, 'nuevo', 0, date('Y-m-d', strtotime($fin . ' +15 day')), '2026-01-15');

        $reportes = $this->get(route('panel.reportes', [
            'anio' => (int) $periodo['anio'],
            'semestre' => (int) $periodo['semestre'],
        ]));

        $reportes->assertOk();
        $reportes->assertSee('Equipos nuevos sin mantenimiento', false);
        $reportes->assertSee(route('panel.reportes.equipos-nuevos'), false);
        $reportes->assertDontSee('Acceso rápido', false);
        $reportes->assertDontSee('Abrir reporte', false);

        $response = $this->get(route('panel.reportes.equipos-nuevos', [
            'anio' => (int) $periodo['anio'],
            'semestre' => (int) $periodo['semestre'],
            'q_reportes_nuevos' => '990001',
        ]));

        $response->assertOk();
        $response->assertSee('Equipos nuevos sin mantenimiento', false);
        $response->assertDontSee('Volver a reportes', false);
        $response->assertSee('Equipo Nuevo Fuera', false);
        $response->assertDontSee('Equipo Nuevo Dentro', false);
        $response->assertDontSee('Equipo Normal Fuera', false);
        $response->assertDontSee('Equipo Nuevo Inactivo', false);

        $items = app(\App\Services\Legacy\MantenimientoRepository::class)->listarEquiposNuevosSinMantenimiento(
            10,
            0,
            '990001',
            $periodo
        );

        $filaFuera = collect($items)->firstWhere('usuario_id', $usuarioNuevoFuera);
        $filaDentro = collect($items)->firstWhere('usuario_id', $usuarioNuevoDentro);
        $filaNormal = collect($items)->firstWhere('usuario_id', $usuarioNormalFuera);
        $filaInactivo = collect($items)->firstWhere('usuario_id', $usuarioNuevoInactivo);

        $this->assertNotNull($filaFuera);
        $this->assertNull($filaDentro);
        $this->assertNull($filaNormal);
        $this->assertNull($filaInactivo);
        $this->assertSame('nuevo', $filaFuera['tipo_equipo'] ?? null);
        $this->assertSame('2027-01-15', $filaFuera['proxima_fecha_mantenimiento'] ?? null);
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
