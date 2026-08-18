<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Legacy\ReversionEstadoMantenimientoService;
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
                ['id' => 1, 'nombre' => 'Giuliano'],
                ['id' => 2, 'nombre' => 'Juan Manuel'],
            ]);

        $gestionService = Mockery::mock(ReversionEstadoMantenimientoService::class);

        $this->instance(UsuarioRepository::class, $usuarioRepo);
        $this->instance(MantenimientoRepository::class, $mantenimientoRepo);
        $this->instance(ReversionEstadoMantenimientoService::class, $gestionService);

        $response = $this->get(route('panel.configuracion'));

        $response->assertOk();
        $response->assertSee('Registrar técnico');
        $response->assertSee('Técnicos activos');
        $response->assertSee('Gestión de estados');
        $response->assertSee('Gestionar estado del mantenimiento');
        $response->assertSee('Giuliano');
        $response->assertSee('Juan Manuel');
        $response->assertSee('Lista de usuarios');
        $response->assertSee('Ejecutados');
        $response->assertDontSee('Vista general');
        $response->assertDontSee('Cantidad de reversiones');
        $response->assertDontSee('Revertir acciones');
    }

    public function test_configuracion_con_usuario_resuelto_muestra_estado_actual_y_tres_destinos(): void
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

        $gestionService = Mockery::mock(ReversionEstadoMantenimientoService::class);
        $gestionService->shouldReceive('analizarUsuarioPorCodigoActivo')
            ->with(845)
            ->once()
            ->andReturn([
                'ok' => true,
                'usuario' => [
                    'id' => 88,
                    'nombre' => 'Natalia Romero',
                    'codigo_activo' => 845,
                    'tipo_equipo' => 'normal',
                    'estado_usuario' => 1,
                    'activo' => true,
                ],
                'periodo' => [
                    'etiqueta' => '2026 - S2',
                ],
                'estado_actual' => [
                    'valor' => 'programado',
                    'etiqueta' => 'Programado',
                ],
                'destinos' => [
                    [
                        'estado_destino' => 'no_programado',
                        'estado_destino_etiqueta' => 'No programado',
                        'descripcion' => 'Regresa el mantenimiento programado al estado pendiente del semestre actual.',
                        'es_estado_actual' => false,
                    ],
                    [
                        'estado_destino' => 'programado',
                        'estado_destino_etiqueta' => 'Programado',
                        'descripcion' => 'Ubica el mantenimiento en Programados dentro del semestre vigente.',
                        'es_estado_actual' => true,
                    ],
                    [
                        'estado_destino' => 'ejecutado',
                        'estado_destino_etiqueta' => 'Ejecutado',
                        'descripcion' => 'Marca el mantenimiento vigente como ejecutado y prepara el siguiente ciclo.',
                        'es_estado_actual' => false,
                    ],
                ],
            ]);

        $this->instance(UsuarioRepository::class, $usuarioRepo);
        $this->instance(MantenimientoRepository::class, $mantenimientoRepo);
        $this->instance(ReversionEstadoMantenimientoService::class, $gestionService);

        $response = $this->get(route('panel.configuracion', [
            'busqueda_usuario' => 'Natalia',
        ]));

        $response->assertOk();
        $response->assertSee('Usuario confirmado');
        $response->assertSee('Natalia Romero');
        $response->assertSee('Código activo 845');
        $response->assertSee('Estado actual:');
        $response->assertSee('Programado');
        $response->assertSee('No programado');
        $response->assertSee('Ejecutado');
        $response->assertSee('Generar previsualización');
        $response->assertDontSee('>Tipo<', false);
        $response->assertDontSee('Cantidad de reversiones');
        $response->assertDontSee('Revertir acciones');
    }

    public function test_busqueda_async_de_configuracion_devuelve_coincidencias_multiples(): void
    {
        $this->actingAs($this->makeLegacyUser(2003, 'Admin Config C', 'config-c@example.com', 'admin'));

        $usuarioRepo = Mockery::mock(UsuarioRepository::class);
        $usuarioRepo->shouldReceive('buscarCoincidenciasConfiguracion')
            ->with('ana', 8)
            ->once()
            ->andReturn([
                [
                    'usuario_id' => 11,
                    'nombre' => 'Ana Lucia',
                    'codigo_activo' => 845,
                    'estado_usuario' => 1,
                    'activo' => true,
                ],
                [
                    'usuario_id' => 12,
                    'nombre' => 'Ana Maria',
                    'codigo_activo' => 846,
                    'estado_usuario' => 0,
                    'activo' => false,
                ],
            ]);
        $usuarioRepo->shouldNotReceive('resolverCodigoActivoPorBusqueda');

        $mantenimientoRepo = Mockery::mock(MantenimientoRepository::class);
        $mantenimientoRepo->shouldReceive('listarTecnicosActivos')
            ->once()
            ->andReturn([]);

        $gestionService = Mockery::mock(ReversionEstadoMantenimientoService::class);
        $gestionService->shouldNotReceive('analizarUsuarioPorCodigoActivo');

        $this->instance(UsuarioRepository::class, $usuarioRepo);
        $this->instance(MantenimientoRepository::class, $mantenimientoRepo);
        $this->instance(ReversionEstadoMantenimientoService::class, $gestionService);

        $response = $this->getJson(route('panel.configuracion.buscar', ['q' => 'ana']));

        $response->assertOk();
        $response->assertJson([
            'ok' => true,
            'mode' => 'multiple',
            'matches_count' => 2,
            'query' => 'ana',
        ]);
        $response->assertJsonMissingPath('usuario.password_hash');
        $this->assertStringContainsString('Ana Lucia', (string) $response->json('html'));
        $this->assertStringContainsString('Ana Maria', (string) $response->json('html'));
    }

    public function test_busqueda_async_de_configuracion_resuelve_codigo_activo_sin_recarga(): void
    {
        $this->actingAs($this->makeLegacyUser(2004, 'Admin Config D', 'config-d@example.com', 'admin'));

        $usuarioRepo = Mockery::mock(UsuarioRepository::class);
        $usuarioRepo->shouldReceive('resolverCodigoActivoPorBusqueda')
            ->with('845')
            ->once()
            ->andReturn([
                'ok' => true,
                'usuario_id' => 88,
                'nombre' => 'Yuly Barahona',
                'codigo_activo' => 845,
            ]);

        $mantenimientoRepo = Mockery::mock(MantenimientoRepository::class);
        $mantenimientoRepo->shouldReceive('listarTecnicosActivos')
            ->once()
            ->andReturn([
                ['id' => 1, 'nombre' => 'Giuliano'],
            ]);

        $gestionService = Mockery::mock(ReversionEstadoMantenimientoService::class);
        $gestionService->shouldReceive('analizarUsuarioPorCodigoActivo')
            ->with(845)
            ->once()
            ->andReturn([
                'ok' => true,
                'usuario' => [
                    'id' => 88,
                    'nombre' => 'Yuly Barahona',
                    'codigo_activo' => 845,
                    'tipo_equipo' => 'normal',
                    'estado_usuario' => 1,
                    'activo' => true,
                ],
                'periodo' => [
                    'etiqueta' => 'Semestre 2 de 2026',
                ],
                'estado_actual' => [
                    'valor' => 'pendiente',
                    'etiqueta' => 'No programado',
                ],
                'destinos' => [
                    [
                        'estado_destino' => 'pendiente',
                        'estado_destino_etiqueta' => 'No programado',
                        'descripcion' => 'Gestiona el estado del mantenimiento vigente.',
                        'es_estado_actual' => true,
                    ],
                    [
                        'estado_destino' => 'programado',
                        'estado_destino_etiqueta' => 'Programado',
                        'descripcion' => 'Programa el mantenimiento vigente usando el flujo actual de Próximos.',
                        'es_estado_actual' => false,
                    ],
                ],
            ]);

        $this->instance(UsuarioRepository::class, $usuarioRepo);
        $this->instance(MantenimientoRepository::class, $mantenimientoRepo);
        $this->instance(ReversionEstadoMantenimientoService::class, $gestionService);

        $response = $this->getJson(route('panel.configuracion.buscar', ['codigo_activo' => 845, 'q' => '845']));

        $response->assertOk();
        $response->assertJson([
            'ok' => true,
            'mode' => 'resolved',
            'codigo_activo' => 845,
        ]);
        $this->assertStringContainsString('Yuly Barahona', (string) $response->json('html'));
        $this->assertStringContainsString('No programado', (string) $response->json('html'));
        $this->assertStringContainsString('Programado', (string) $response->json('html'));
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
