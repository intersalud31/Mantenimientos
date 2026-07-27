<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\TestCase;

class PanelAuthorizationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_usuario_administrador_puede_entrar_al_panel(): void
    {
        $admin = $this->makeLegacyUser(901, 'Admin Prueba', 'admin@example.com', 'admin');

        $this->actingAs($admin);

        $this->get(route('panel'))->assertOk();
        $this->get(route('panel.reportes'))->assertOk();
        $this->get(route('panel.proximos'))->assertOk();
        $this->get(route('panel.programados'))->assertOk();
        $this->get(route('panel.hechos'))->assertOk();
        $this->get(route('panel.usuarios'))->assertOk();
        $this->get(route('panel.configuracion'))->assertOk();
    }

    public function test_usuario_no_admin_recibe_forbidden_en_panel_y_acciones_sensibles(): void
    {
        $usuario = $this->makeLegacyUser(902, 'Usuario Prueba', 'usuario@example.com', 'usuario');

        $this->actingAs($usuario);

        $this->get(route('panel'))->assertForbidden();
        $this->get(route('panel.reportes'))->assertForbidden();
        $this->get(route('panel.proximos'))->assertForbidden();
        $this->get(route('panel.programados'))->assertForbidden();
        $this->get(route('panel.hechos'))->assertForbidden();
        $this->get(route('panel.usuarios'))->assertForbidden();
        $this->get(route('panel.configuracion'))->assertForbidden();
        $this->get(route('panel.evidencia.pdf', ['mantenimientoId' => 1]))->assertForbidden();
        $this->post(route('panel.usuarios.crear'), [])->assertForbidden();
        $this->post(route('panel.configuracion.revertir'), [])->assertForbidden();
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
