<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Legacy\UsuarioRepository;
use App\Services\SemestreService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class EstadoMantenimientoVigentePanelTest extends TestCase
{
    use DatabaseTransactions;

    public function test_panel_usuarios_muestra_estado_vigente_unificado_del_semestre_actual(): void
    {
        $this->actingAs($this->makeLegacyUser(12001, 'Admin Estados Usuarios', 'estados-usuarios@example.com', 'admin'));

        $periodo = app(SemestreService::class)->actual();
        $inicio = (string) $periodo['inicio'];

        $usuarioPendiente = $this->crearUsuario('Usuario Estado Pendiente', 998001);
        $usuarioProgramado = $this->crearUsuario('Usuario Estado Programado', 998002);
        $usuarioEjecutado = $this->crearUsuario('Usuario Estado Ejecutado', 998003);
        $usuarioInactivo = $this->crearUsuario('Usuario Estado Inactivo', 998004, 0);

        DB::table('mantenimientos')->insert([
            [
                'usuario_id' => $usuarioPendiente,
                'fecha_programada' => $inicio,
                'hora_programada' => null,
                'estado' => 'pendiente',
                'fecha_ejecucion' => null,
                'observaciones' => 'Pendiente vigente.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'usuario_id' => $usuarioProgramado,
                'fecha_programada' => date('Y-m-d', strtotime($inicio . ' +1 day')),
                'hora_programada' => null,
                'estado' => 'reprogramado',
                'fecha_ejecucion' => null,
                'observaciones' => 'Reprogramado vigente.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'usuario_id' => $usuarioEjecutado,
                'fecha_programada' => date('Y-m-d', strtotime($inicio . ' +2 day')),
                'hora_programada' => null,
                'estado' => 'realizado',
                'fecha_ejecucion' => date('Y-m-d', strtotime($inicio . ' +2 day')),
                'observaciones' => 'Realizado vigente.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'usuario_id' => $usuarioInactivo,
                'fecha_programada' => date('Y-m-d', strtotime($inicio . ' +3 day')),
                'hora_programada' => null,
                'estado' => 'programado',
                'fecha_ejecucion' => null,
                'observaciones' => 'Programado de usuario inactivo.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->get(route('panel.usuarios', ['q_usuarios' => 'Usuario Estado']));

        $response->assertOk();
        $response->assertSee('Usuario Estado Pendiente', false);
        $response->assertSee('Usuario Estado Programado', false);
        $response->assertSee('Usuario Estado Ejecutado', false);

        $usuarios = app(UsuarioRepository::class)->listarTodos('Usuario Estado');
        $filaPendiente = collect($usuarios)->firstWhere('id', $usuarioPendiente);
        $filaProgramada = collect($usuarios)->firstWhere('id', $usuarioProgramado);
        $filaEjecutada = collect($usuarios)->firstWhere('id', $usuarioEjecutado);
        $filaInactiva = collect($usuarios)->firstWhere('id', $usuarioInactivo);

        $this->assertSame('no_programado', $filaPendiente['estado_vigente'] ?? null);
        $this->assertSame('programado', $filaProgramada['estado_vigente'] ?? null);
        $this->assertSame('ejecutado', $filaEjecutada['estado_vigente'] ?? null);
        $this->assertNotNull($filaInactiva);
        $this->assertNull($filaInactiva['estado_vigente'] ?? null);
    }

    public function test_panel_proximos_muestra_etiqueta_no_programado(): void
    {
        $this->actingAs($this->makeLegacyUser(12002, 'Admin Estados Proximos', 'estados-proximos@example.com', 'admin'));

        $periodo = app(SemestreService::class)->actual();
        $fechaPendiente = date('Y-m-d', strtotime((string) $periodo['inicio'] . ' +5 day'));

        $usuarioId = $this->crearUsuario('Usuario Vista Proximos Estado', 998005, 1, $fechaPendiente);

        DB::table('mantenimientos')->insert([
            'usuario_id' => $usuarioId,
            'fecha_programada' => $fechaPendiente,
            'hora_programada' => null,
            'estado' => 'pendiente',
            'fecha_ejecucion' => null,
            'observaciones' => 'Pendiente para próximos.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->get(route('panel.proximos', [
            'anio' => (int) $periodo['anio'],
            'semestre' => (int) $periodo['semestre'],
            'q_proximos' => 'Usuario Vista Proximos Estado',
        ]));

        $response->assertOk();
        $response->assertSee('Usuario Vista Proximos Estado', false);

        $items = app(\App\Services\Legacy\MantenimientoRepository::class)->listarProximosPorPeriodo(
            10,
            0,
            'Usuario Vista Proximos Estado',
            $periodo
        );
        $fila = collect($items)->firstWhere('usuario_id', $usuarioId);

        $this->assertNotNull($fila);
        $this->assertSame('no_programado', $fila['estado_vigente'] ?? null);
    }

    public function test_panel_programados_y_hechos_muestran_etiquetas_unificadas(): void
    {
        $this->actingAs($this->makeLegacyUser(12003, 'Admin Estados Operacion', 'estados-operacion@example.com', 'admin'));

        $periodo = app(SemestreService::class)->actual();
        $inicio = (string) $periodo['inicio'];

        $usuarioProgramado = $this->crearUsuario('Usuario Vista Programado Estado', 998006, 1, date('Y-m-d', strtotime($inicio . ' +8 day')));
        $usuarioHecho = $this->crearUsuario('Usuario Vista Hecho Estado', 998007, 1, null);

        DB::table('mantenimientos')->insert([
            [
                'usuario_id' => $usuarioProgramado,
                'fecha_programada' => date('Y-m-d', strtotime($inicio . ' +45 day')),
                'hora_programada' => null,
                'estado' => 'programado',
                'fecha_ejecucion' => null,
                'observaciones' => 'Programado para vista.',
                'tecnico_responsable' => null,
                'evidencia_pdf' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'usuario_id' => $usuarioHecho,
                'fecha_programada' => date('Y-m-d', strtotime($inicio . ' +46 day')),
                'hora_programada' => null,
                'estado' => 'realizado',
                'fecha_ejecucion' => date('Y-m-d', strtotime($inicio . ' +46 day')),
                'tecnico_responsable' => 'Tecnico Estado',
                'observaciones' => 'Hecho para vista.',
                'evidencia_pdf' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $programados = $this->get(route('panel.programados', [
            'anio' => (int) $periodo['anio'],
            'semestre' => (int) $periodo['semestre'],
            'q_programados' => 'Usuario Vista Programado Estado',
        ]));

        $programados->assertOk();
        $programados->assertSee('Usuario Vista Programado Estado', false);
        $programados->assertSee('Programado', false);

        $hechos = $this->get(route('panel.hechos', [
            'anio' => (int) $periodo['anio'],
            'semestre' => (int) $periodo['semestre'],
            'q_realizados' => 'Usuario Vista Hecho Estado',
        ]));

        $hechos->assertOk();
        $hechos->assertSee('Usuario Vista Hecho Estado', false);
        $hechos->assertSee('Ejecutado', false);
        $hechos->assertDontSee('Hecho</span>', false);
    }

    private function crearUsuario(string $nombre, int $codigoActivo, int $estado = 1, ?string $proximaFecha = null): int
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
