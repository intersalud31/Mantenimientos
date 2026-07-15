<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use DatabaseTransactions;

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
}
