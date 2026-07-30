<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Legacy\UsuarioMantenimientoResumenService;
use App\Services\Legacy\UsuarioRepository;
use App\Services\SemestreService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_root_redirects_to_login_form_for_guest_users(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('login.form'));
    }

    public function test_login_real_syncs_legacy_session_and_laravel_auth(): void
    {
        $this->assertTrue(
            Schema::hasTable('usuarios_sistema'),
            'La tabla usuarios_sistema debe existir en la base de pruebas.'
        );

        $password = 'Secreta123!';
        $correo = 'prueba.login@example.com';
        $nombre = 'Usuario de Prueba';

        $usuarioId = DB::table('usuarios_sistema')->insertGetId([
            'nombre' => $nombre,
            'correo' => $correo,
            'password_hash' => Hash::make($password),
            'rol' => 'admin',
            'estado' => 1,
            'ultimo_acceso' => null,
        ]);

        $response = $this->post(route('login.store'), [
            'correo' => $correo,
            'password' => $password,
        ]);

        $response->assertRedirect(route('panel'));
        $response->assertSessionHas('usuario_autenticado', function (array $usuario) use ($usuarioId, $correo, $nombre): bool {
            return (int) ($usuario['id'] ?? 0) === $usuarioId
                && ($usuario['nombre'] ?? null) === $nombre
                && ($usuario['correo'] ?? null) === $correo;
        });

        $this->assertTrue(Auth::check());
        $this->assertSame($correo, Auth::user()->email);
        $this->assertSame($nombre, Auth::user()->name);

        $panel = $this->get(route('panel'));
        $panel->assertOk();
        $panel->assertSee($nombre);
    }

    public function test_panel_with_query_params_keeps_context_after_login(): void
    {
        $this->assertTrue(
            Schema::hasTable('usuarios_sistema'),
            'La tabla usuarios_sistema debe existir en la base de pruebas.'
        );

        $password = 'Secreta123!';
        $correo = 'prueba.filtros@example.com';
        $nombre = 'Filtro de Prueba';

        DB::table('usuarios_sistema')->insert([
            'nombre' => $nombre,
            'correo' => $correo,
            'password_hash' => Hash::make($password),
            'rol' => 'admin',
            'estado' => 1,
            'ultimo_acceso' => null,
        ]);

        $login = $this->post(route('login.store'), [
            'correo' => $correo,
            'password' => $password,
        ]);

        $login->assertRedirect(route('panel'));

        $panel = $this->get(route('panel', [
            'apartado' => 'hechos',
            'anio' => 2026,
            'semestre' => 2,
            'q_realizados' => 'Prueba',
        ]));

        $panel->assertRedirect(route('panel.hechos', [
            'anio' => 2026,
            'semestre' => 2,
            'q_realizados' => 'Prueba',
        ]));
        $panel->assertSessionHas('usuario_autenticado');
    }

    public function test_return_url_malicioso_usa_ruta_segura_por_defecto(): void
    {
        $this->actingAs($this->makeLegacyUser(7001, 'Admin Return', 'return@example.com', 'admin'));

        $repo = Mockery::mock(UsuarioRepository::class);
        $repo->shouldReceive('crearUsuario')
            ->once()
            ->andReturn([
                'ok' => true,
                'mensaje' => 'Usuario creado correctamente.',
            ]);

        $this->instance(UsuarioRepository::class, $repo);

        $response = $this->post(route('panel.usuarios.crear'), [
            'nombre' => 'Usuario Retorno',
            'codigo_activo' => 9901,
            'serial_equipo' => 'SER-9901',
            'area_id' => null,
            'tipo_equipo' => 'nuevo',
            'filtro_area_id' => 0,
            'filtro_q_usuarios' => '',
            'return_url' => '//google.com',
        ]);

        $response->assertRedirect(route('panel.usuarios', [
            'area_id' => 0,
            'q_usuarios' => '',
            'nuevo_usuario' => 1,
        ]));
        $response->assertSessionHas('panel_message', 'Usuario creado correctamente.');
    }

    public function test_login_con_multiples_intentos_fallidos_no_autentica_al_usuario(): void
    {
        $this->assertTrue(
            Schema::hasTable('usuarios_sistema'),
            'La tabla usuarios_sistema debe existir en la base de pruebas.'
        );

        $password = 'Secreta123!';
        $correo = 'prueba.intentos@example.com';
        $ip = '203.0.113.77';

        DB::table('usuarios_sistema')->insert([
            'nombre' => 'Usuario Intentos',
            'correo' => $correo,
            'password_hash' => Hash::make($password),
            'rol' => 'admin',
            'estado' => 1,
            'ultimo_acceso' => null,
        ]);

        for ($i = 0; $i < 5; $i++) {
            $response = $this->post(route('login.store'), [
                'correo' => $correo,
                'password' => 'ClaveIncorrecta' . $i,
            ], ['REMOTE_ADDR' => $ip]);

            $response->assertRedirect();
            $response->assertSessionHas('error_login');
            $this->assertGuest();
        }

        $bloqueado = $this->post(route('login.store'), [
            'correo' => $correo,
            'password' => 'ClaveIncorrecta-bloqueada',
        ], ['REMOTE_ADDR' => $ip]);

        $bloqueado->assertTooManyRequests();
        $this->assertGuest();
        $this->assertFalse(Auth::check());
    }

    public function test_locale_y_timezone_se_mantienen_en_es_y_bogota(): void
    {
        $this->assertSame('es', app()->getLocale());
        $this->assertSame('es', config('app.locale'));
        $this->assertSame('es', config('app.fallback_locale'));
        $this->assertSame('America/Bogota', config('app.timezone'));
    }

    public function test_proximos_lista_usuarios_por_fecha_calculada_y_sin_pendiente_previo(): void
    {
        $this->actingAs($this->makeLegacyUser(7002, 'Admin Proximos', 'proximos@example.com', 'admin'));

        $periodo = app(SemestreService::class)->actual();
        $anio = (int) $periodo['anio'];
        $semestre = (int) $periodo['semestre'];

        if ($semestre === 2) {
            $fechaEjecucion = sprintf('%04d-01-15', $anio);
            $fechaEsperada = sprintf('%04d-07-15', $anio);
        } else {
            $fechaEjecucion = sprintf('%04d-07-15', $anio - 1);
            $fechaEsperada = sprintf('%04d-01-15', $anio);
        }

        $usuarioId = DB::table('usuarios')->insertGetId([
            'area_id' => null,
            'nombre' => 'Usuario Proximos Regresion',
            'codigo_activo' => 991001,
            'serial_equipo' => 'SER-PROX-001',
            'tipo_equipo' => 'normal',
            'estado_usuario' => 1,
            'ultima_fecha_ejecucion' => $fechaEjecucion,
            'ultima_fecha_mantenimiento' => $fechaEjecucion,
            'proxima_fecha_mantenimiento' => $fechaEsperada,
            'ultimo_mantenimiento_id' => null,
            'ultimo_mantenimiento_estado' => 'realizado',
            'mantenimiento_resumen_actualizado_at' => now(),
            'created_at' => now()->subYear(),
            'updated_at' => now(),
        ]);

        DB::table('mantenimientos')->insert([
            'usuario_id' => $usuarioId,
            'fecha_programada' => $fechaEjecucion,
            'hora_programada' => null,
            'estado' => 'realizado',
            'fecha_ejecucion' => $fechaEjecucion,
            'tecnico_responsable' => 'Tecnico Base',
            'observaciones' => 'Mantenimiento previo de prueba.',
            'evidencia_pdf' => null,
            'created_at' => now()->subMonths(6),
            'updated_at' => now()->subMonths(6),
        ]);

        $response = $this->get(route('panel.proximos', [
            'anio' => $anio,
            'semestre' => $semestre,
        ]));

        $response->assertOk();
        $response->assertSee('Usuario Proximos Regresion');

        $this->assertDatabaseHas('mantenimientos', [
            'usuario_id' => $usuarioId,
            'estado' => 'pendiente',
            'fecha_programada' => $fechaEsperada,
        ]);
    }

    public function test_proximos_muestra_nombre_antes_de_area_equipo(): void
    {
        $this->actingAs($this->makeLegacyUser(7003, 'Admin Orden', 'orden@example.com', 'admin'));

        $response = $this->get(route('panel.proximos'));

        $response->assertOk();
        $response->assertSeeInOrder([
            'NOMBRE',
            'ÁREA / EQUIPO',
            'ACTIVO',
            'ÚLTIMO MANTENIMIENTO',
            'PRÓXIMO MANTENIMIENTO',
            'PROGRAMACIÓN',
        ]);
    }

    public function test_resumen_usuario_usa_ultimo_realizado_y_ignora_programado_o_pendiente_mas_nuevo(): void
    {
        $periodo = app(SemestreService::class)->actual();
        $anio = (int) $periodo['anio'];
        $semestre = (int) $periodo['semestre'];

        if ($semestre === 2) {
            $fechaRealizada = sprintf('%04d-01-15', $anio);
            $fechaPendiente = sprintf('%04d-07-15', $anio);
            $fechaProgramada = sprintf('%04d-07-20', $anio);
        } else {
            $fechaRealizada = sprintf('%04d-07-15', $anio - 1);
            $fechaPendiente = sprintf('%04d-01-15', $anio);
            $fechaProgramada = sprintf('%04d-01-20', $anio);
        }

        $usuarioId = DB::table('usuarios')->insertGetId([
            'area_id' => null,
            'nombre' => 'Usuario Resumen Ultimo Real',
            'codigo_activo' => 993001,
            'serial_equipo' => 'SER-RESUMEN-001',
            'tipo_equipo' => 'normal',
            'estado_usuario' => 1,
            'ultima_fecha_ejecucion' => $fechaRealizada,
            'ultima_fecha_mantenimiento' => $fechaRealizada,
            'proxima_fecha_mantenimiento' => $fechaProgramada,
            'ultimo_mantenimiento_id' => null,
            'ultimo_mantenimiento_estado' => 'realizado',
            'mantenimiento_resumen_actualizado_at' => now(),
            'created_at' => now()->subYear(),
            'updated_at' => now(),
        ]);

        DB::table('mantenimientos')->insert([
            [
                'usuario_id' => $usuarioId,
                'fecha_programada' => $fechaRealizada,
                'hora_programada' => null,
                'estado' => 'realizado',
                'fecha_ejecucion' => $fechaRealizada,
                'tecnico_responsable' => 'Tecnico Base',
                'observaciones' => 'Realizado base.',
                'evidencia_pdf' => null,
                'created_at' => now()->subMonths(8),
                'updated_at' => now()->subMonths(8),
            ],
            [
                'usuario_id' => $usuarioId,
                'fecha_programada' => $fechaPendiente,
                'hora_programada' => null,
                'estado' => 'pendiente',
                'fecha_ejecucion' => null,
                'tecnico_responsable' => null,
                'observaciones' => 'Pendiente reciente.',
                'evidencia_pdf' => null,
                'created_at' => now()->subDays(2),
                'updated_at' => now()->subDays(2),
            ],
            [
                'usuario_id' => $usuarioId,
                'fecha_programada' => $fechaProgramada,
                'hora_programada' => null,
                'estado' => 'programado',
                'fecha_ejecucion' => null,
                'tecnico_responsable' => null,
                'observaciones' => 'Programado reciente.',
                'evidencia_pdf' => null,
                'created_at' => now()->subDay(),
                'updated_at' => now()->subDay(),
            ],
        ]);

        app(UsuarioMantenimientoResumenService::class)->sincronizarResumenUsuario($usuarioId, $periodo);

        $usuario = DB::table('usuarios')
            ->select('ultima_fecha_mantenimiento', 'proxima_fecha_mantenimiento', 'ultimo_mantenimiento_estado')
            ->where('id', $usuarioId)
            ->first();

        $this->assertSame($fechaRealizada, (string) ($usuario->ultima_fecha_mantenimiento ?? ''));
        $this->assertSame($fechaProgramada, (string) ($usuario->proxima_fecha_mantenimiento ?? ''));
        $this->assertNotSame($usuario->ultima_fecha_mantenimiento, $usuario->proxima_fecha_mantenimiento);
        $this->assertSame('programado', (string) ($usuario->ultimo_mantenimiento_estado ?? ''));
    }

    public function test_resumen_usuario_sin_historial_real_deja_fecha_ultima_nula(): void
    {
        $periodo = app(SemestreService::class)->actual();
        $anio = (int) $periodo['anio'];
        $semestre = (int) $periodo['semestre'];

        $fechaPendiente = $semestre === 2
            ? sprintf('%04d-07-15', $anio)
            : sprintf('%04d-01-15', $anio);

        $usuarioId = DB::table('usuarios')->insertGetId([
            'area_id' => null,
            'nombre' => 'Usuario Sin Historico Real',
            'codigo_activo' => 993002,
            'serial_equipo' => 'SER-RESUMEN-002',
            'tipo_equipo' => 'normal',
            'estado_usuario' => 1,
            'ultima_fecha_ejecucion' => null,
            'ultima_fecha_mantenimiento' => null,
            'proxima_fecha_mantenimiento' => $fechaPendiente,
            'ultimo_mantenimiento_id' => null,
            'ultimo_mantenimiento_estado' => null,
            'mantenimiento_resumen_actualizado_at' => now(),
            'created_at' => now()->subYear(),
            'updated_at' => now(),
        ]);

        DB::table('mantenimientos')->insert([
            [
                'usuario_id' => $usuarioId,
                'fecha_programada' => $fechaPendiente,
                'hora_programada' => null,
                'estado' => 'pendiente',
                'fecha_ejecucion' => null,
                'tecnico_responsable' => null,
                'observaciones' => 'Pendiente sin historial real.',
                'evidencia_pdf' => null,
                'created_at' => now()->subDay(),
                'updated_at' => now()->subDay(),
            ],
        ]);

        app(UsuarioMantenimientoResumenService::class)->sincronizarResumenUsuario($usuarioId, $periodo);

        $usuario = DB::table('usuarios')
            ->select('ultima_fecha_mantenimiento', 'proxima_fecha_mantenimiento')
            ->where('id', $usuarioId)
            ->first();

        $this->assertNull($usuario->ultima_fecha_mantenimiento);
        $this->assertSame($fechaPendiente, (string) ($usuario->proxima_fecha_mantenimiento ?? ''));
    }

    public function test_resumen_usuario_usa_fecha_base_si_no_existe_historial_real(): void
    {
        $periodo = app(SemestreService::class)->actual();
        $fechaBase = '2026-01-15';

        $usuarioId = DB::table('usuarios')->insertGetId([
            'area_id' => null,
            'nombre' => 'Usuario Con Fecha Base',
            'codigo_activo' => 993003,
            'serial_equipo' => 'SER-RESUMEN-003',
            'tipo_equipo' => 'normal',
            'fecha_ultimo_mantenimiento_base' => $fechaBase,
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

        app(UsuarioMantenimientoResumenService::class)->sincronizarResumenUsuario($usuarioId, $periodo);

        $usuario = DB::table('usuarios')
            ->select('ultima_fecha_ejecucion', 'ultima_fecha_mantenimiento', 'proxima_fecha_mantenimiento')
            ->where('id', $usuarioId)
            ->first();

        $this->assertNull($usuario->ultima_fecha_ejecucion);
        $this->assertSame($fechaBase, (string) ($usuario->ultima_fecha_mantenimiento ?? ''));
        $this->assertSame('2026-07-15', (string) ($usuario->proxima_fecha_mantenimiento ?? ''));
    }

    public function test_fecha_base_no_sobrescribe_historial_real_existente(): void
    {
        $periodo = app(SemestreService::class)->actual();
        $fechaRealizada = '2026-01-20';
        $fechaBase = '2025-12-01';

        $usuarioId = DB::table('usuarios')->insertGetId([
            'area_id' => null,
            'nombre' => 'Usuario Con Historial Real',
            'codigo_activo' => 993004,
            'serial_equipo' => 'SER-RESUMEN-004',
            'tipo_equipo' => 'normal',
            'fecha_ultimo_mantenimiento_base' => $fechaBase,
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
            'fecha_programada' => $fechaRealizada,
            'hora_programada' => null,
            'estado' => 'realizado',
            'fecha_ejecucion' => $fechaRealizada,
            'tecnico_responsable' => 'Tecnico Base',
            'observaciones' => 'Mantenimiento real existente.',
            'evidencia_pdf' => null,
            'created_at' => now()->subMonths(6),
            'updated_at' => now()->subMonths(6),
        ]);

        app(UsuarioMantenimientoResumenService::class)->sincronizarResumenUsuario($usuarioId, $periodo);

        $usuario = DB::table('usuarios')
            ->select('ultima_fecha_mantenimiento', 'proxima_fecha_mantenimiento')
            ->where('id', $usuarioId)
            ->first();

        $this->assertSame($fechaRealizada, (string) ($usuario->ultima_fecha_mantenimiento ?? ''));
        $this->assertSame('2026-07-20', (string) ($usuario->proxima_fecha_mantenimiento ?? ''));
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
