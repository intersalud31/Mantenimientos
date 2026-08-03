<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Legacy\MantenimientoRepository;
use App\Services\Legacy\UsuarioRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery;
use Tests\TestCase;

class ConfiguracionLayoutTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_configuracion_renderiza_tres_tarjetas_verticales_con_etiquetas_actualizadas(): void
    {
        $this->actingAs($this->makeLegacyUser(2001, 'Admin Config', 'config@example.com', 'admin'));

        $usuarioRepo = Mockery::mock(UsuarioRepository::class);
        $usuarioRepo->shouldNotReceive('resolverCodigoActivoPorBusqueda');

        $mantenimientoRepo = Mockery::mock(MantenimientoRepository::class);
        $mantenimientoRepo->shouldReceive('listarTecnicosActivos')
            ->once()
            ->andReturn([
                ['nombre' => 'Giuliano'],
                ['nombre' => 'Juan Manuel'],
            ]);

        $this->instance(UsuarioRepository::class, $usuarioRepo);
        $this->instance(MantenimientoRepository::class, $mantenimientoRepo);

        $response = $this->get(route('panel.configuracion'));

        $response->assertOk();
        $response->assertSee('Registrar técnico');
        $response->assertSee('Técnicos activos');
        $response->assertSee('Revertir última acción');
        $response->assertSee('Giuliano');
        $response->assertSee('Juan Manuel');
        $response->assertSee('Lista de usuarios');
        $response->assertSee('Ejecutados');
        $response->assertDontSee('Vista general');
        $response->assertDontSee('Gestión operativa y reversión controlada');
    }

    public function test_configuracion_con_usuario_resuelto_muestra_confirmacion_y_selector_de_reversion(): void
    {
        $this->actingAs($this->makeLegacyUser(2002, 'Admin Config B', 'config-b@example.com', 'admin'));

        $usuarioRepo = Mockery::mock(UsuarioRepository::class);
        $usuarioRepo->shouldReceive('resolverCodigoActivoPorBusqueda')
            ->with('Natalia')
            ->once()
            ->andReturn([
                'ok' => true,
                'usuario_id' => 88,
                'nombre' => 'Natalia Romero',
                'codigo_activo' => 845,
            ]);

        $mantenimientoRepo = Mockery::mock(MantenimientoRepository::class);
        $mantenimientoRepo->shouldReceive('listarTecnicosActivos')
            ->once()
            ->andReturn([]);

        $this->instance(UsuarioRepository::class, $usuarioRepo);
        $this->instance(MantenimientoRepository::class, $mantenimientoRepo);

        $response = $this->get(route('panel.configuracion', [
            'busqueda_usuario' => 'Natalia',
            'cantidad_reversiones' => 3,
        ]));

        $response->assertOk();
        $response->assertSee('Usuario confirmado');
        $response->assertSee('Natalia Romero');
        $response->assertSee('Código activo 845');
        $response->assertSee('Cantidad de reversiones');
        $response->assertSee('Revertir acciones');
        $response->assertSee('Busca al usuario, confirma el objetivo y selecciona cuántas acciones recientes deseas revertir.');
        $response->assertDontSee('Id interno');
        $response->assertDontSee('Reversiones permitidas');
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
