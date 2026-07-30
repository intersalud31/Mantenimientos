<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Legacy\MantenimientoRepository;
use App\Services\Legacy\UsuarioRepository;
use App\Services\SemestreService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class PanelActionFlowTest extends TestCase
{
    use DatabaseTransactions;

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_pdf_de_evidencia_se_puede_ver_y_descargar(): void
    {
        $this->actingAs($this->makeLegacyUser(1001, 'Admin PDF', 'pdf@example.com', 'admin'));
        Storage::fake('public');
        Storage::disk('public')->put('constancias/evidencia.pdf', '%PDF-1.4');

        $repo = Mockery::mock(MantenimientoRepository::class);
        $repo->shouldReceive('obtenerEvidenciaPorMantenimientoId')
            ->with(55)
            ->andReturn(['evidencia_pdf' => 'constancias/evidencia.pdf']);

        $this->instance(MantenimientoRepository::class, $repo);

        $this->get(route('panel.evidencia.pdf', ['mantenimientoId' => 55]))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->get(route('panel.evidencia.pdf', ['mantenimientoId' => 55, 'download' => 1]))
            ->assertDownload('evidencia.pdf');
    }

    public function test_programado_hecho_sube_pdf_y_redirecciona_con_contexto(): void
    {
        $this->actingAs($this->makeLegacyUser(1002, 'Admin Programados', 'programados@example.com', 'admin'));
        Storage::fake('public');

        $repo = Mockery::mock(MantenimientoRepository::class);
        $repo->shouldReceive('obtenerNombreTecnicoPorId')
            ->with(7)
            ->andReturn('Tecnico Uno');
        $repo->shouldReceive('marcarProgramadoComoHechoYGenerarSiguiente')
            ->once()
            ->andReturn([
                'ok' => true,
                'mensaje' => 'Mantenimiento marcado como hecho.',
            ]);

        $this->instance(MantenimientoRepository::class, $repo);

        $response = $this->post(route('panel.programados.hecho'), [
            'mantenimiento_id' => 123,
            'fecha_ejecucion' => now()->toDateString(),
            'tecnico_id' => 7,
            'observaciones' => 'Prueba de ejecucion',
            'constancia_pdf' => UploadedFile::fake()->create('constancia.pdf', 120, 'application/pdf'),
            'q_programados' => 'Filtro',
            'programados_pagina' => 2,
            'programados_por_pagina' => 25,
        ]);

        $response->assertRedirect(route('panel.programados', [
            'q_programados' => 'Filtro',
            'programados_pagina' => 2,
            'programados_por_pagina' => 25,
        ]));
        $response->assertSessionHas('panel_message', 'Mantenimiento marcado como hecho.');
        $this->assertCount(1, Storage::disk('public')->allFiles('constancias'));
    }

    public function test_programados_renderiza_busqueda_y_contenedor_propios(): void
    {
        $this->actingAs($this->makeLegacyUser(10021, 'Admin Programados Vista', 'programados-vista@example.com', 'admin'));

        $periodo = app(SemestreService::class)->actual();
        $fechaProgramada = \Illuminate\Support\Carbon::parse((string) $periodo['inicio'])->addDays(7)->format('Y-m-d');

        $usuarioId = DB::table('usuarios')->insertGetId([
            'area_id' => null,
            'nombre' => 'Usuario Programados Vista',
            'codigo_activo' => 881001,
            'serial_equipo' => 'SER-PROG-VISTA-001',
            'tipo_equipo' => 'normal',
            'estado_usuario' => 1,
            'ultima_fecha_ejecucion' => null,
            'ultima_fecha_mantenimiento' => null,
            'proxima_fecha_mantenimiento' => $fechaProgramada,
            'ultimo_mantenimiento_id' => null,
            'ultimo_mantenimiento_estado' => 'programado',
            'mantenimiento_resumen_actualizado_at' => now(),
            'created_at' => now()->subMonth(),
            'updated_at' => now(),
        ]);

        DB::table('mantenimientos')->insert([
            'usuario_id' => $usuarioId,
            'fecha_programada' => $fechaProgramada,
            'hora_programada' => null,
            'estado' => 'programado',
            'fecha_ejecucion' => null,
            'tecnico_responsable' => null,
            'observaciones' => 'Programado de prueba para la vista.',
            'evidencia_pdf' => null,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $response = $this->get(route('panel.programados', [
            'anio' => (int) $periodo['anio'],
            'semestre' => (int) $periodo['semestre'],
            'q_programados' => 'Usuario Programados Vista',
            'programados_pagina' => 1,
            'programados_por_pagina' => 10,
        ]));

        $response->assertOk();
        $response->assertSee('name="q_programados"', false);
        $response->assertSee('data-panel-live-search-target="#tabla-programados"', false);
        $response->assertSee('id="tabla-programados"', false);
        $response->assertSee('Fecha de programación');
        $response->assertSee('Realizado por');
        $response->assertSee('Selecciona una persona');
        $response->assertSee('Seleccionar constancia en PDF');
        $response->assertSee('data-maintenance-date-trigger', false);
        $response->assertSee('data-maintenance-date-picker', false);
        $response->assertSee('data-maintenance-date-grid', false);
        $response->assertSee('Marcar como hecho', false);
        $response->assertDontSee('name="q_proximos"', false);
        $response->assertDontSee('Cierre operativo');
        $response->assertDontSee('ID mantenimiento:');
        $response->assertDontSee('Técnico:');
        $response->assertDontSee('Programado</span>');

        $html = $response->getContent();
        $this->assertMatchesRegularExpression('/<tbody>.*?<span class="tabla-programados-nombre">Usuario Programados Vista<\\/span>.*?<td class="tabla-programados-columna-fecha">' . preg_quote($fechaProgramada, '/') . '<\\/td>.*?<button[^>]+>Marcar como hecho<\\/button>.*?<\\/tbody>/s', $html);
        $this->assertStringNotContainsString('ID mantenimiento:', $html);
        $this->assertStringNotContainsString('Técnico:', $html);
        $this->assertStringNotContainsString('Programado</span>', $html);
    }

    public function test_reversion_de_acciones_redirecciona_con_mensaje(): void
    {
        $this->actingAs($this->makeLegacyUser(1003, 'Admin Reversion', 'reversion@example.com', 'admin'));

        $usuarioRepo = Mockery::mock(UsuarioRepository::class);
        $usuarioRepo->shouldReceive('resolverCodigoActivoPorBusqueda')
            ->with('Activo 123')
            ->andReturn([
                'ok' => true,
                'usuario_id' => 88,
                'nombre' => 'Activo 123',
                'codigo_activo' => 123,
            ]);

        $mantenimientoRepo = Mockery::mock(MantenimientoRepository::class);
        $mantenimientoRepo->shouldReceive('revertirAccionesPorCodigoActivo')
            ->with(123, 2)
            ->andReturn([
                'ok' => true,
                'mensaje' => 'Se revirtieron 2 accion(es) correctamente.',
            ]);

        $this->instance(UsuarioRepository::class, $usuarioRepo);
        $this->instance(MantenimientoRepository::class, $mantenimientoRepo);

        $response = $this->post(route('panel.configuracion.revertir'), [
            'busqueda_usuario' => 'Activo 123',
            'cantidad_reversiones' => 2,
        ]);

        $response->assertRedirect(route('panel.configuracion', [
            'busqueda_usuario' => 'Activo 123',
            'cantidad_reversiones' => 2,
        ]));
        $response->assertSessionHas('panel_message', 'Se revirtieron 2 accion(es) correctamente.');
    }

    public function test_crud_de_usuarios_cubre_crear_actualizar_estado_y_eliminar(): void
    {
        $this->actingAs($this->makeLegacyUser(1004, 'Admin CRUD', 'crud@example.com', 'admin'));

        $repo = Mockery::mock(UsuarioRepository::class);
        $repo->shouldReceive('crearUsuario')
            ->once()
            ->with('Usuario Nuevo', 555, 'SER-001', 3, 'nuevo', null)
            ->andReturn(['ok' => true, 'mensaje' => 'Usuario creado correctamente.']);
        $repo->shouldReceive('obtenerPorId')
            ->once()
            ->with(41)
            ->andReturn([
                'id' => 41,
                'fecha_ultimo_mantenimiento_base' => null,
                'ultima_fecha_mantenimiento' => null,
            ]);
        $repo->shouldReceive('usuarioTieneHistorialReal')
            ->once()
            ->with(41)
            ->andReturn(true);
        $repo->shouldReceive('actualizarUsuario')
            ->once()
            ->with(41, 'Usuario Editado', 556, 'SER-002', 3, 'normal', null, true)
            ->andReturn(true);
        $repo->shouldReceive('cambiarEstadoUsuario')
            ->once()
            ->with(41, false)
            ->andReturn(true);
        $repo->shouldReceive('eliminarUsuarioSeguro')
            ->once()
            ->with(41)
            ->andReturn(['ok' => true, 'mensaje' => 'Usuario eliminado correctamente.']);

        $this->instance(UsuarioRepository::class, $repo);

        $crear = $this->post(route('panel.usuarios.crear'), [
            'nombre' => 'Usuario Nuevo',
            'codigo_activo' => 555,
            'serial_equipo' => 'SER-001',
            'area_id' => 3,
            'tipo_equipo' => 'nuevo',
            'filtro_area_id' => 3,
            'filtro_q_usuarios' => 'Usuario',
        ]);

        $crear->assertRedirect(route('panel.usuarios', [
            'area_id' => 3,
            'q_usuarios' => 'Usuario',
            'nuevo_usuario' => 1,
        ]));
        $crear->assertSessionHas('panel_message', 'Usuario creado correctamente.');

        $actualizar = $this->post(route('panel.usuarios.actualizar'), [
            'usuario_id' => 41,
            'nombre' => 'Usuario Editado',
            'codigo_activo' => 556,
            'serial_equipo' => 'SER-002',
            'area_id' => 3,
            'tipo_equipo' => 'normal',
            'fecha_ultimo_mantenimiento_base' => null,
            'filtro_area_id' => 3,
            'filtro_q_usuarios' => 'Usuario',
            'editar_usuario_id' => 41,
        ]);

        $actualizar->assertRedirect(route('panel.usuarios', [
            'area_id' => 3,
            'q_usuarios' => 'Usuario',
        ]));
        $actualizar->assertSessionHas('panel_message', 'Usuario actualizado correctamente.');

        $estado = $this->post(route('panel.usuarios.estado'), [
            'usuario_id' => 41,
            'nuevo_estado' => 0,
            'filtro_area_id' => 3,
            'filtro_q_usuarios' => 'Usuario',
        ]);

        $estado->assertRedirect(route('panel.usuarios', [
            'area_id' => 3,
            'q_usuarios' => 'Usuario',
        ]));
        $estado->assertSessionHas('panel_message', 'Estado de usuario actualizado.');

        $eliminar = $this->post(route('panel.usuarios.eliminar'), [
            'usuario_id' => 41,
            'filtro_area_id' => 3,
            'filtro_q_usuarios' => 'Usuario',
        ]);

        $eliminar->assertRedirect(route('panel.usuarios', [
            'area_id' => 3,
            'q_usuarios' => 'Usuario',
        ]));
        $eliminar->assertSessionHas('panel_message', 'Usuario eliminado correctamente.');
    }

    public function test_busqueda_de_usuarios_encuentra_serial_alfanumerico(): void
    {
        DB::table('usuarios')->insert([
            'nombre' => 'Usuario Serial Prueba',
            'codigo_activo' => 991234,
            'serial_equipo' => 'ZX-TST-2345D4',
            'tipo_equipo' => 'normal',
            'area_id' => null,
            'estado_usuario' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $resultados = app(UsuarioRepository::class)->listarTodos('ZX-TST-2345D4');

        $this->assertCount(1, $resultados);
        $this->assertSame('ZX-TST-2345D4', $resultados[0]['serial_equipo']);
    }

    public function test_busqueda_de_usuarios_encuentra_area_caas(): void
    {
        DB::table('areas')->updateOrInsert(
            ['nombre' => 'CAAS'],
            [
                'estado' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $areaId = (int) DB::table('areas')->where('nombre', 'CAAS')->value('id');

        DB::table('usuarios')->insert([
            'nombre' => 'Usuario Area Prueba',
            'codigo_activo' => 991235,
            'serial_equipo' => 'SER-AREA-CAAS',
            'tipo_equipo' => 'normal',
            'area_id' => $areaId,
            'estado_usuario' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $resultados = app(UsuarioRepository::class)->listarTodos('CAAS');

        $this->assertNotEmpty($resultados);
        $this->assertTrue(collect($resultados)->contains(static function (array $fila): bool {
            return ($fila['serial_equipo'] ?? '') === 'SER-AREA-CAAS' && ($fila['area_nombre'] ?? '') === 'CAAS';
        }));
    }

    public function test_crear_usuario_viejo_sin_fecha_base_es_invalido(): void
    {
        $this->actingAs($this->makeLegacyUser(10041, 'Admin Validacion', 'validacion@example.com', 'admin'));

        $response = $this->from(route('panel.usuarios'))->post(route('panel.usuarios.crear'), [
            'nombre' => 'Usuario Viejo',
            'codigo_activo' => 991236,
            'serial_equipo' => 'SER-VIEJO-001',
            'tipo_equipo' => 'normal',
            'form_context' => 'crear_usuario',
        ]);

        $response->assertRedirect(route('panel.usuarios'));
        $response->assertSessionHasErrors('fecha_ultimo_mantenimiento_base');
    }

    public function test_actualizar_usuario_viejo_sin_historial_real_requiere_fecha_base(): void
    {
        $this->actingAs($this->makeLegacyUser(10042, 'Admin Editar', 'editar@example.com', 'admin'));

        $usuarioId = DB::table('usuarios')->insertGetId([
            'nombre' => 'Usuario Editar Base',
            'codigo_activo' => 991237,
            'serial_equipo' => 'SER-EDIT-001',
            'tipo_equipo' => 'normal',
            'fecha_ultimo_mantenimiento_base' => null,
            'area_id' => null,
            'estado_usuario' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->from(route('panel.usuarios', ['editar_usuario_id' => $usuarioId]))->post(route('panel.usuarios.actualizar'), [
            'usuario_id' => $usuarioId,
            'nombre' => 'Usuario Editar Base',
            'codigo_activo' => 991237,
            'serial_equipo' => 'SER-EDIT-001',
            'tipo_equipo' => 'normal',
            'form_context' => 'editar_usuario',
        ]);

        $response->assertRedirect(route('panel.usuarios', ['editar_usuario_id' => $usuarioId]));
        $response->assertSessionHasErrors('fecha_ultimo_mantenimiento_base');
    }

    public function test_lista_de_usuarios_no_muestra_fecha_base_en_tabla(): void
    {
        $this->actingAs($this->makeLegacyUser(10043, 'Admin Lista', 'lista@example.com', 'admin'));

        DB::table('usuarios')->insert([
            'nombre' => 'Usuario Fecha Base Lista',
            'codigo_activo' => 991238,
            'serial_equipo' => 'SER-LISTA-001',
            'tipo_equipo' => 'normal',
            'fecha_ultimo_mantenimiento_base' => '2026-06-15',
            'area_id' => null,
            'estado_usuario' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->get(route('panel.usuarios', ['q_usuarios' => 'Fecha Base Lista']));

        $response->assertOk();
        $response->assertSee('Usuario Fecha Base Lista');
        $response->assertSee('991238');
        $response->assertSee('Ser-Lista-001');

        $html = $response->getContent();
        $inicioTabla = strpos($html, 'id="usuarios-listado"');
        $this->assertNotFalse($inicioTabla);
        $htmlTabla = substr($html, $inicioTabla);

        $this->assertStringNotContainsString('Fecha de &uacute;ltimo mantenimiento', $htmlTabla);
        $this->assertStringNotContainsString('15/06/2026', $htmlTabla);
    }

    public function test_editar_usuario_sin_enviar_fecha_base_no_borra_la_existente(): void
    {
        $this->actingAs($this->makeLegacyUser(10044, 'Admin Preservar', 'preservar@example.com', 'admin'));

        $usuarioId = DB::table('usuarios')->insertGetId([
            'nombre' => 'Usuario Preserva Fecha',
            'codigo_activo' => 991239,
            'serial_equipo' => 'SER-PRESERVA-001',
            'tipo_equipo' => 'normal',
            'fecha_ultimo_mantenimiento_base' => '2026-05-10',
            'area_id' => null,
            'estado_usuario' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->post(route('panel.usuarios.actualizar'), [
            'usuario_id' => $usuarioId,
            'nombre' => 'Usuario Preserva Fecha Editado',
            'codigo_activo' => 991239,
            'serial_equipo' => 'SER-PRESERVA-001',
            'tipo_equipo' => 'normal',
            'form_context' => 'editar_usuario',
        ])->assertRedirect();

        $this->assertDatabaseHas('usuarios', [
            'id' => $usuarioId,
            'nombre' => 'Usuario Preserva Fecha Editado',
            'fecha_ultimo_mantenimiento_base' => '2026-05-10',
        ]);
    }

    public function test_editar_usuario_carga_ultima_fecha_resumen_si_no_tiene_fecha_base(): void
    {
        $this->actingAs($this->makeLegacyUser(10045, 'Admin Fecha Editar', 'fecha-editar@example.com', 'admin'));

        $usuarioId = DB::table('usuarios')->insertGetId([
            'nombre' => 'Usuario Fecha Resumen',
            'codigo_activo' => 991240,
            'serial_equipo' => 'SER-RESUMEN-EDIT',
            'tipo_equipo' => 'normal',
            'fecha_ultimo_mantenimiento_base' => null,
            'ultima_fecha_mantenimiento' => '2026-04-12',
            'ultima_fecha_ejecucion' => '2026-04-12',
            'proxima_fecha_mantenimiento' => '2026-10-12',
            'area_id' => null,
            'estado_usuario' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->get(route('panel.usuarios', ['editar_usuario_id' => $usuarioId]));

        $response->assertOk();
        $response->assertSee('type="date"', false);
        $response->assertSee('id="fecha_ultimo_mantenimiento_base_editar"', false);
        $response->assertSee('name="fecha_ultimo_mantenimiento_base"', false);
        $response->assertSee('value="2026-04-12"', false);
        $response->assertSee('data-user-last-maintenance-input', false);
        $response->assertDontSee('data-maintenance-date-trigger', false);
        $response->assertDontSee('data-maintenance-date-picker', false);
    }

    public function test_guardar_fecha_de_respaldo_sin_cambio_no_migra_fecha_base_automaticamente(): void
    {
        $this->actingAs($this->makeLegacyUser(10046, 'Admin No Migra', 'no-migra@example.com', 'admin'));

        $usuarioId = DB::table('usuarios')->insertGetId([
            'nombre' => 'Usuario No Migra Base',
            'codigo_activo' => 991241,
            'serial_equipo' => 'SER-NO-MIGRA',
            'tipo_equipo' => 'normal',
            'fecha_ultimo_mantenimiento_base' => null,
            'ultima_fecha_mantenimiento' => '2026-03-20',
            'ultima_fecha_ejecucion' => '2026-03-20',
            'proxima_fecha_mantenimiento' => '2026-09-21',
            'area_id' => null,
            'estado_usuario' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('mantenimientos')->insert([
            'usuario_id' => $usuarioId,
            'fecha_programada' => '2026-03-20',
            'hora_programada' => null,
            'estado' => 'realizado',
            'fecha_ejecucion' => '2026-03-20',
            'tecnico_responsable' => 'Tecnico Base',
            'observaciones' => 'Mantenimiento real de respaldo.',
            'evidencia_pdf' => null,
            'created_at' => now()->subMonths(4),
            'updated_at' => now()->subMonths(4),
        ]);

        $this->post(route('panel.usuarios.actualizar'), [
            'usuario_id' => $usuarioId,
            'nombre' => 'Usuario No Migra Base Editado',
            'codigo_activo' => 991241,
            'serial_equipo' => 'SER-NO-MIGRA',
            'tipo_equipo' => 'normal',
            'fecha_ultimo_mantenimiento_base' => '2026-03-20',
            'form_context' => 'editar_usuario',
        ])->assertRedirect();

        $this->assertDatabaseHas('usuarios', [
            'id' => $usuarioId,
            'nombre' => 'Usuario No Migra Base Editado',
            'fecha_ultimo_mantenimiento_base' => null,
            'ultima_fecha_mantenimiento' => '2026-03-20',
        ]);
    }

    public function test_cambiar_fecha_de_ultimo_mantenimiento_en_edicion_persiste_fecha_base(): void
    {
        $this->actingAs($this->makeLegacyUser(10047, 'Admin Cambia Fecha', 'cambia-fecha@example.com', 'admin'));

        $usuarioId = DB::table('usuarios')->insertGetId([
            'nombre' => 'Usuario Cambia Fecha',
            'codigo_activo' => 991242,
            'serial_equipo' => 'SER-CAMBIA-FECHA',
            'tipo_equipo' => 'normal',
            'fecha_ultimo_mantenimiento_base' => null,
            'ultima_fecha_mantenimiento' => '2026-03-20',
            'ultima_fecha_ejecucion' => '2026-03-20',
            'proxima_fecha_mantenimiento' => '2026-09-21',
            'area_id' => null,
            'estado_usuario' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $mantenimientosAntes = DB::table('mantenimientos')->where('usuario_id', $usuarioId)->count();

        $this->post(route('panel.usuarios.actualizar'), [
            'usuario_id' => $usuarioId,
            'nombre' => 'Usuario Cambia Fecha',
            'codigo_activo' => 991242,
            'serial_equipo' => 'SER-CAMBIA-FECHA',
            'tipo_equipo' => 'normal',
            'fecha_ultimo_mantenimiento_base' => '2026-02-10',
            'form_context' => 'editar_usuario',
        ])->assertRedirect();

        $this->assertDatabaseHas('usuarios', [
            'id' => $usuarioId,
            'fecha_ultimo_mantenimiento_base' => '2026-02-10',
        ]);
        $this->assertSame($mantenimientosAntes, DB::table('mantenimientos')->where('usuario_id', $usuarioId)->count());
    }

    public function test_no_permite_programar_mantenimiento_en_fecha_pasada(): void
    {
        $this->actingAs($this->makeLegacyUser(1005, 'Admin Fecha', 'fecha@example.com', 'admin'));

        $repo = Mockery::mock(MantenimientoRepository::class);
        $repo->shouldNotReceive('marcarComoProgramado');
        $this->instance(MantenimientoRepository::class, $repo);

        $response = $this->post(route('panel.proximos.programar'), [
            'mantenimiento_id' => 123,
            'fecha_programada' => now()->subDay()->format('Y-m-d'),
            'q_proximos' => 'Filtro',
            'proximos_pagina' => 1,
            'proximos_por_pagina' => 10,
        ]);

        $response->assertRedirect(route('panel.proximos', [
            'q_proximos' => 'Filtro',
            'proximos_pagina' => 1,
            'proximos_por_pagina' => 10,
        ]));
        $response->assertSessionHas('panel_error', 'La fecha programada no puede ser anterior a hoy.');
    }

    public function test_no_permite_marcar_como_hecho_con_fecha_pasada(): void
    {
        $this->actingAs($this->makeLegacyUser(1008, 'Admin Hecho Fecha', 'hecho-fecha@example.com', 'admin'));
        Storage::fake('public');

        $periodo = app(SemestreService::class)->actual();
        $fechaProgramada = \Illuminate\Support\Carbon::parse((string) $periodo['inicio'])->addDays(40)->format('Y-m-d');
        $fechaPasada = now()->subDay()->format('Y-m-d');

        $usuarioId = DB::table('usuarios')->insertGetId([
            'area_id' => null,
            'nombre' => 'Usuario Fecha Pasada',
            'codigo_activo' => 729002,
            'serial_equipo' => 'SER-HECHO-PAST',
            'tipo_equipo' => 'normal',
            'estado_usuario' => 1,
            'ultima_fecha_ejecucion' => null,
            'ultima_fecha_mantenimiento' => $fechaProgramada,
            'proxima_fecha_mantenimiento' => $fechaProgramada,
            'ultimo_mantenimiento_id' => null,
            'ultimo_mantenimiento_estado' => 'programado',
            'mantenimiento_resumen_actualizado_at' => now(),
            'created_at' => now()->subYear(),
            'updated_at' => now(),
        ]);

        $mantenimientoId = DB::table('mantenimientos')->insertGetId([
            'usuario_id' => $usuarioId,
            'fecha_programada' => $fechaProgramada,
            'hora_programada' => null,
            'estado' => 'programado',
            'fecha_ejecucion' => null,
            'tecnico_responsable' => null,
            'observaciones' => 'Programado de prueba con fecha pasada.',
            'evidencia_pdf' => null,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $tecnicoId = DB::table('tecnicos')->insertGetId([
            'nombre' => 'Tecnico Fecha Pasada',
            'estado' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->post(route('panel.programados.hecho'), [
            'mantenimiento_id' => $mantenimientoId,
            'fecha_ejecucion' => $fechaPasada,
            'tecnico_id' => $tecnicoId,
            'observaciones' => 'No debe guardarse.',
            'constancia_pdf' => UploadedFile::fake()->create('constancia.pdf', 120, 'application/pdf'),
            'q_programados' => 'Usuario Fecha Pasada',
            'programados_pagina' => 1,
            'programados_por_pagina' => 10,
        ]);

        $response->assertRedirect(route('panel.programados', [
            'q_programados' => 'Usuario Fecha Pasada',
            'programados_pagina' => 1,
            'programados_por_pagina' => 10,
        ]));
        $response->assertSessionHas('panel_error', 'La fecha de ejecucion no puede ser anterior a hoy.');

        $this->assertDatabaseHas('mantenimientos', [
            'id' => $mantenimientoId,
            'estado' => 'programado',
            'fecha_ejecucion' => null,
        ]);
    }

    public function test_busqueda_de_proximos_encuentra_area_caas(): void
    {
        $this->actingAs($this->makeLegacyUser(1007, 'Admin Proximos Area', 'proximos-area@example.com', 'admin'));

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

        DB::table('areas')->updateOrInsert(
            ['nombre' => 'CAAS'],
            [
                'estado' => 1,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $areaId = (int) DB::table('areas')->where('nombre', 'CAAS')->value('id');

        $usuarioId = DB::table('usuarios')->insertGetId([
            'area_id' => $areaId,
            'nombre' => 'Usuario Proximos CAAS',
            'codigo_activo' => 992001,
            'serial_equipo' => 'SER-PROX-CAAS',
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

        $resultados = app(MantenimientoRepository::class)->listarProximosPorPeriodo(10, 0, 'CAAS', $periodo);

        $this->assertNotEmpty($resultados);
        $this->assertTrue(collect($resultados)->contains(static function (array $fila): bool {
            return ($fila['area_nombre'] ?? '') === 'CAAS' && ($fila['nombre'] ?? '') === 'Usuario Proximos CAAS';
        }));
    }

    public function test_marcar_como_hecho_con_fecha_libre_aparece_en_hechos_del_periodo_programado(): void
    {
        $this->actingAs($this->makeLegacyUser(1006, 'Admin Hechos', 'hechos@example.com', 'admin'));
        Storage::fake('public');

        $periodo = app(SemestreService::class)->actual();
        $fechaProgramada = \Illuminate\Support\Carbon::parse((string) $periodo['inicio'])->addDays(40)->format('Y-m-d');
        $fechaEjecucion = now()->toDateString();

        $usuarioId = DB::table('usuarios')->insertGetId([
            'area_id' => null,
            'nombre' => 'Maria Paula Montano Hechos',
            'codigo_activo' => 729001,
            'serial_equipo' => 'SER-HECHO-001',
            'tipo_equipo' => 'normal',
            'estado_usuario' => 1,
            'ultima_fecha_ejecucion' => $fechaEjecucion,
            'ultima_fecha_mantenimiento' => $fechaProgramada,
            'proxima_fecha_mantenimiento' => $fechaProgramada,
            'ultimo_mantenimiento_id' => null,
            'ultimo_mantenimiento_estado' => 'programado',
            'mantenimiento_resumen_actualizado_at' => now(),
            'created_at' => now()->subYear(),
            'updated_at' => now(),
        ]);

        $mantenimientoId = DB::table('mantenimientos')->insertGetId([
            'usuario_id' => $usuarioId,
            'fecha_programada' => $fechaProgramada,
            'hora_programada' => null,
            'estado' => 'programado',
            'fecha_ejecucion' => null,
            'tecnico_responsable' => null,
            'observaciones' => 'Programado de prueba para hechos.',
            'evidencia_pdf' => null,
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $tecnicoId = DB::table('tecnicos')->insertGetId([
            'nombre' => 'Tecnico Hechos',
            'estado' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->post(route('panel.programados.hecho'), [
            'mantenimiento_id' => $mantenimientoId,
            'fecha_ejecucion' => $fechaEjecucion,
            'tecnico_id' => $tecnicoId,
            'observaciones' => 'Ejecucion realizada hoy.',
            'constancia_pdf' => UploadedFile::fake()->create('constancia.pdf', 120, 'application/pdf'),
            'q_programados' => 'Maria Paula',
            'programados_pagina' => 1,
            'programados_por_pagina' => 10,
        ]);

        $response->assertRedirect(route('panel.programados', [
            'q_programados' => 'Maria Paula',
            'programados_pagina' => 1,
            'programados_por_pagina' => 10,
        ]));
        $respuestaEsperada = 'Mantenimiento marcado como hecho. Se registro la ejecucion y se genero el siguiente mantenimiento a ' . app(\App\Services\Legacy\PeriodicidadMantenimientoService::class)->etiquetaMeses(
            app(\App\Services\Legacy\PeriodicidadMantenimientoService::class)->cicloMantenimientoMeses()
        ) . '.';

        $response->assertSessionHas('panel_message', $respuestaEsperada);

        $this->assertDatabaseHas('mantenimientos', [
            'id' => $mantenimientoId,
            'estado' => 'realizado',
            'fecha_ejecucion' => $fechaEjecucion,
        ]);

        $hechos = $this->get(route('panel.hechos', [
            'anio' => (int) $periodo['anio'],
            'semestre' => (int) $periodo['semestre'],
            'q_realizados' => 'Maria Paula',
        ]));

        $hechos->assertOk();
        $hechos->assertSee('Maria Paula Montano Hechos');
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
