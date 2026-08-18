<?php

namespace Tests\Feature;

use App\Services\Legacy\MantenimientoRepository;
use App\Services\SemestreService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Tests\TestCase;

class MantenimientoRepositoryMaterializacionPendienteTest extends TestCase
{
    use DatabaseTransactions;

    public function test_reutiliza_el_pendiente_existente_del_usuario_y_semestre(): void
    {
        $periodo = app(SemestreService::class)->actual();
        $usuarioId = $this->crearUsuario(995001, 'Usuario Materializacion Reutiliza');
        $fechaOriginal = $this->fechaDelPeriodo($periodo, 5);
        $fechaNueva = $this->fechaDelPeriodo($periodo, 12);

        $mantenimientoId = (int) DB::table('mantenimientos')->insertGetId([
            'usuario_id' => $usuarioId,
            'fecha_programada' => $fechaOriginal,
            'hora_programada' => null,
            'estado' => 'pendiente',
            'fecha_ejecucion' => null,
            'observaciones' => 'Pendiente manual existente.',
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);

        $resultadoId = app(MantenimientoRepository::class)->materializarPendienteSemestral(
            $usuarioId,
            $fechaNueva,
            'Materializado desde prueba.'
        );

        $this->assertSame($mantenimientoId, $resultadoId);

        $pendientes = DB::table('mantenimientos')
            ->where('usuario_id', $usuarioId)
            ->orderBy('id')
            ->get();

        $this->assertCount(1, $pendientes);
        $this->assertSame('pendiente', (string) $pendientes[0]->estado);
        $this->assertSame($fechaNueva, (string) $pendientes[0]->fecha_programada);
        $this->assertSame('Pendiente manual existente.', (string) $pendientes[0]->observaciones);
    }

    public function test_crea_un_pendiente_si_no_existe_en_el_semestre_vigente(): void
    {
        $periodo = app(SemestreService::class)->actual();
        $usuarioId = $this->crearUsuario(995002, 'Usuario Materializacion Crea');
        $fechaNueva = $this->fechaDelPeriodo($periodo, 18);

        $resultadoId = app(MantenimientoRepository::class)->materializarPendienteSemestral(
            $usuarioId,
            $fechaNueva,
            'Pendiente generado desde gestion.'
        );

        $this->assertNotNull($resultadoId);

        $mantenimiento = DB::table('mantenimientos')->where('id', $resultadoId)->first();

        $this->assertNotNull($mantenimiento);
        $this->assertSame($usuarioId, (int) $mantenimiento->usuario_id);
        $this->assertSame('pendiente', (string) $mantenimiento->estado);
        $this->assertSame($fechaNueva, (string) $mantenimiento->fecha_programada);
        $this->assertSame('Pendiente generado desde gestion.', (string) $mantenimiento->observaciones);
    }

    public function test_limita_la_materializacion_al_usuario_y_semestre_correspondiente(): void
    {
        $semestreService = app(SemestreService::class);
        $periodoActual = $semestreService->actual();
        $periodoSiguiente = $semestreService->semestreDeFecha($this->fechaDelPeriodo($periodoActual, 190));

        $usuarioObjetivo = $this->crearUsuario(995003, 'Usuario Materializacion Objetivo');
        $usuarioAjeno = $this->crearUsuario(995004, 'Usuario Materializacion Ajeno');

        $pendienteOtroSemestreId = (int) DB::table('mantenimientos')->insertGetId([
            'usuario_id' => $usuarioObjetivo,
            'fecha_programada' => $this->fechaDelPeriodo($periodoSiguiente, 10),
            'hora_programada' => null,
            'estado' => 'pendiente',
            'fecha_ejecucion' => null,
            'observaciones' => 'Pendiente del otro semestre.',
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);

        $pendienteOtroUsuarioId = (int) DB::table('mantenimientos')->insertGetId([
            'usuario_id' => $usuarioAjeno,
            'fecha_programada' => $this->fechaDelPeriodo($periodoActual, 7),
            'hora_programada' => null,
            'estado' => 'pendiente',
            'fecha_ejecucion' => null,
            'observaciones' => 'Pendiente de otro usuario.',
            'created_at' => now()->subDays(2),
            'updated_at' => now()->subDays(2),
        ]);

        $nuevoId = app(MantenimientoRepository::class)->materializarPendienteSemestral(
            $usuarioObjetivo,
            $this->fechaDelPeriodo($periodoActual, 9),
            'Pendiente del semestre vigente.'
        );

        $this->assertNotNull($nuevoId);
        $this->assertNotSame($pendienteOtroSemestreId, $nuevoId);
        $this->assertNotSame($pendienteOtroUsuarioId, $nuevoId);

        $this->assertSame('pendiente', (string) DB::table('mantenimientos')->where('id', $pendienteOtroSemestreId)->value('estado'));
        $this->assertSame('pendiente', (string) DB::table('mantenimientos')->where('id', $pendienteOtroUsuarioId)->value('estado'));
    }

    public function test_no_genera_duplicados_en_el_mismo_semestre_y_reutiliza_el_principal(): void
    {
        $periodo = app(SemestreService::class)->actual();
        $usuarioId = $this->crearUsuario(995005, 'Usuario Materializacion Duplicados');
        $fechaPrincipal = $this->fechaDelPeriodo($periodo, 3);
        $fechaNueva = $this->fechaDelPeriodo($periodo, 19);

        $principalId = (int) DB::table('mantenimientos')->insertGetId([
            'usuario_id' => $usuarioId,
            'fecha_programada' => $fechaPrincipal,
            'hora_programada' => null,
            'estado' => 'pendiente',
            'fecha_ejecucion' => null,
            'observaciones' => '',
            'created_at' => now()->subDays(3),
            'updated_at' => now()->subDays(3),
        ]);

        $resultadoId = app(MantenimientoRepository::class)->materializarPendienteSemestral(
            $usuarioId,
            $fechaNueva,
            'Pendiente principal sincronizado.'
        );

        $this->assertSame($principalId, $resultadoId);

        $principal = DB::table('mantenimientos')->where('id', $principalId)->first();

        $this->assertSame('pendiente', (string) $principal->estado);
        $this->assertSame($fechaNueva, (string) $principal->fecha_programada);
        $this->assertSame('Pendiente principal sincronizado.', (string) $principal->observaciones);

        $pendientes = DB::table('mantenimientos')
            ->where('usuario_id', $usuarioId)
            ->where('estado', 'pendiente')
            ->count();

        $this->assertSame(1, $pendientes);

        $this->assertSame(
            1,
            (int) DB::table('mantenimientos')
                ->where('usuario_id', $usuarioId)
                ->count()
        );
    }

    public function test_hace_rollback_completo_si_la_operacion_superior_falla(): void
    {
        $periodo = app(SemestreService::class)->actual();
        $usuarioId = $this->crearUsuario(995006, 'Usuario Materializacion Rollback');
        $fechaNueva = $this->fechaDelPeriodo($periodo, 14);

        try {
            DB::transaction(function () use ($usuarioId, $fechaNueva): void {
                app(MantenimientoRepository::class)->materializarPendienteSemestral(
                    $usuarioId,
                    $fechaNueva,
                    'Pendiente transaccional.'
                );

                throw new RuntimeException('Forzar rollback de prueba.');
            });

            $this->fail('La transaccion externa debio lanzar la excepcion de prueba.');
        } catch (RuntimeException $e) {
            $this->assertSame('Forzar rollback de prueba.', $e->getMessage());
        }

        $registros = DB::table('mantenimientos')
            ->where('usuario_id', $usuarioId)
            ->get();

        $this->assertCount(0, $registros);
    }

    private function crearUsuario(int $codigoActivo, string $nombre): int
    {
        return (int) DB::table('usuarios')->insertGetId([
            'area_id' => null,
            'nombre' => $nombre,
            'codigo_activo' => $codigoActivo,
            'serial_equipo' => 'SER-' . $codigoActivo,
            'tipo_equipo' => 'normal',
            'estado_usuario' => 1,
            'ultima_fecha_ejecucion' => null,
            'ultima_fecha_mantenimiento' => null,
            'proxima_fecha_mantenimiento' => null,
            'ultimo_mantenimiento_id' => null,
            'ultimo_mantenimiento_estado' => null,
            'mantenimiento_resumen_actualizado_at' => null,
            'created_at' => now()->subMonths(8),
            'updated_at' => now(),
        ]);
    }

    private function fechaDelPeriodo(array $periodo, int $dias): string
    {
        return date('Y-m-d', strtotime((string) $periodo['inicio'] . ' +' . $dias . ' day'));
    }
}
