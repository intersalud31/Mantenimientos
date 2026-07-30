<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class AuthSecurityTest extends TestCase
{
    use DatabaseTransactions;

    public function test_login_correcto_con_hash_seguro_autentica_y_regenera_sesion(): void
    {
        $this->assertTrue(
            Schema::hasTable('usuarios_sistema'),
            'La tabla usuarios_sistema debe existir en la base de pruebas.'
        );

        $password = 'Secreta123!';
        $correo = 'auth.segura@example.com';

        DB::table('usuarios_sistema')->insert([
            'nombre' => 'Usuario Seguro',
            'correo' => $correo,
            'password_hash' => Hash::make($password),
            'rol' => 'admin',
            'estado' => 1,
            'ultimo_acceso' => null,
        ]);

        $sessionAnterior = session()->getId();

        $response = $this->post(route('login.store'), [
            'correo' => $correo,
            'password' => $password,
        ]);

        $response->assertRedirect();
        $this->assertAuthenticated();
        $this->assertNotSame($sessionAnterior, session()->getId());
        $this->assertArrayHasKey('usuario_autenticado', session()->all());
    }

    public function test_login_con_password_incorrecta_rechaza_sin_autenticar(): void
    {
        $this->assertTrue(
            Schema::hasTable('usuarios_sistema'),
            'La tabla usuarios_sistema debe existir en la base de pruebas.'
        );

        $password = 'Secreta123!';
        $correo = 'auth.erronea@example.com';

        DB::table('usuarios_sistema')->insert([
            'nombre' => 'Usuario Erroneo',
            'correo' => $correo,
            'password_hash' => Hash::make($password),
            'rol' => 'admin',
            'estado' => 1,
            'ultimo_acceso' => null,
        ]);

        $response = $this->post(route('login.store'), [
            'correo' => $correo,
            'password' => 'ClaveIncorrecta',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error_login');
        $this->assertGuest();
        $this->assertFalse(Auth::check());
    }

    public function test_login_con_password_plana_guardada_no_autentica(): void
    {
        $this->assertTrue(
            Schema::hasTable('usuarios_sistema'),
            'La tabla usuarios_sistema debe existir en la base de pruebas.'
        );

        $correo = 'auth.plana@example.com';

        DB::table('usuarios_sistema')->insert([
            'nombre' => 'Usuario Plano',
            'correo' => $correo,
            'password_hash' => 'ClaveEnTextoPlano',
            'rol' => 'admin',
            'estado' => 1,
            'ultimo_acceso' => null,
        ]);

        $response = $this->post(route('login.store'), [
            'correo' => $correo,
            'password' => 'ClaveEnTextoPlano',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error_login');
        $this->assertGuest();
        $this->assertFalse(Auth::check());
    }

    public function test_usuario_inactivo_es_rechazado_en_login(): void
    {
        $this->assertTrue(
            Schema::hasTable('usuarios_sistema'),
            'La tabla usuarios_sistema debe existir en la base de pruebas.'
        );

        $password = 'Secreta123!';
        $correo = 'auth.inactivo@example.com';

        DB::table('usuarios_sistema')->insert([
            'nombre' => 'Usuario Inactivo',
            'correo' => $correo,
            'password_hash' => Hash::make($password),
            'rol' => 'admin',
            'estado' => 0,
            'ultimo_acceso' => null,
        ]);

        $response = $this->post(route('login.store'), [
            'correo' => $correo,
            'password' => $password,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('error_login');
        $this->assertGuest();
        $this->assertFalse(Auth::check());
    }

    public function test_usuario_administrador_puede_acceder_al_panel(): void
    {
        $admin = $this->makeLegacyUser(9011, 'Admin Prueba', 'admin.auth@example.com', 'admin');

        $this->actingAs($admin);

        $this->get(route('panel'))->assertOk();
        $this->get(route('panel.reportes'))->assertOk();
        $this->get(route('panel.proximos'))->assertOk();
        $this->get(route('panel.programados'))->assertOk();
        $this->get(route('panel.hechos'))->assertOk();
        $this->get(route('panel.usuarios'))->assertOk();
        $this->get(route('panel.configuracion'))->assertOk();
    }

    public function test_usuario_sin_autorizacion_recibe_forbidden_en_panel(): void
    {
        $usuario = $this->makeLegacyUser(9012, 'Usuario Prueba', 'usuario.auth@example.com', 'usuario');

        $this->actingAs($usuario);

        $this->get(route('panel'))->assertForbidden();
        $this->get(route('panel.reportes'))->assertForbidden();
        $this->get(route('panel.proximos'))->assertForbidden();
        $this->get(route('panel.programados'))->assertForbidden();
        $this->get(route('panel.hechos'))->assertForbidden();
        $this->get(route('panel.usuarios'))->assertForbidden();
        $this->get(route('panel.configuracion'))->assertForbidden();
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
