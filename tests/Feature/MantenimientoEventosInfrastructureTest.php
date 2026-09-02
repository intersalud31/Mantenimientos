<?php

namespace Tests\Feature;

use App\Services\Legacy\MantenimientoEventoRepository;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MantenimientoEventosInfrastructureTest extends TestCase
{
    use DatabaseTransactions;

    public function test_mantenimiento_eventos_existe_con_estructura_esperada_y_registra_evento_basico(): void
    {
        $this->assertTrue(Schema::hasTable('mantenimiento_eventos'));

        foreach ([
            'id',
            'mantenimiento_id',
            'tipo_evento',
            'descripcion',
            'datos_antes',
            'datos_despues',
            'usuario_sistema_id',
            'ip',
            'created_at',
        ] as $columna) {
            $this->assertTrue(
                Schema::hasColumn('mantenimiento_eventos', $columna),
                "La columna {$columna} debe existir en mantenimiento_eventos."
            );
        }

        $mantenimientoId = (int) DB::table('mantenimientos')->insertGetId([
            'usuario_id' => $this->crearUsuarioLegacy(),
            'fecha_programada' => '2026-08-28',
            'estado' => 'pendiente',
            'fecha_ejecucion' => null,
            'tecnico_responsable' => null,
            'observaciones' => 'Observacion humana.',
            'evidencia_pdf' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        app(MantenimientoEventoRepository::class)->registrarEvento(
            $mantenimientoId,
            'programado',
            'Evento de infraestructura.',
            ['estado' => 'pendiente'],
            ['estado' => 'programado'],
            null,
            '127.0.0.1'
        );

        $evento = DB::table('mantenimiento_eventos')
            ->where('mantenimiento_id', $mantenimientoId)
            ->where('tipo_evento', 'programado')
            ->first();

        $this->assertNotNull($evento);
        $this->assertSame('Evento de infraestructura.', (string) $evento->descripcion);
        $this->assertSame('127.0.0.1', (string) $evento->ip);
        $this->assertSame(['estado' => 'pendiente'], json_decode((string) $evento->datos_antes, true, 512, JSON_THROW_ON_ERROR));
        $this->assertSame(['estado' => 'programado'], json_decode((string) $evento->datos_despues, true, 512, JSON_THROW_ON_ERROR));
    }

    private function crearUsuarioLegacy(): int
    {
        return (int) DB::table('usuarios')->insertGetId([
            'nombre' => 'Equipo Evento Infraestructura',
            'codigo_activo' => 990801,
            'serial_equipo' => 'EVT-INF-001',
            'area_id' => null,
            'estado_usuario' => 1,
            'tipo_equipo' => 'normal',
            'fecha_ultimo_mantenimiento_base' => '2026-02-28',
            'proxima_fecha_mantenimiento' => '2026-08-28',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
