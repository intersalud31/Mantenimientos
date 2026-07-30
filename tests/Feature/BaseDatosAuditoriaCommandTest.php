<?php

namespace Tests\Feature;

use App\Services\Diagnostics\BaseDatosAuditoriaService;
use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use ReflectionMethod;
use RuntimeException;
use Tests\TestCase;

class BaseDatosAuditoriaCommandTest extends TestCase
{
    public function test_genera_conteos_y_huellas_sin_escritura(): void
    {
        $base = DB::connection()->getDatabaseName();
        $this->assertSame('mantenimientos_normalizacion_pruebas', $base);
        $this->assertNotSame('mantenimientos', $base);

        $antes = $this->snapshotCounts();

        $exitCode = Artisan::call('mantenimientos:auditoria', [
            '--database' => $base,
        ]);

        $this->assertSame(0, $exitCode);

        $output = trim(Artisan::output());
        $json = json_decode($output, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame($base, $json['meta']['database'] ?? null);
        $this->assertSame($antes['usuarios'], (int) ($json['counts']['usuarios'] ?? -1));
        $this->assertSame($antes['mantenimientos'], (int) ($json['counts']['mantenimientos'] ?? -1));
        $this->assertSame($antes['usuarios_sistema'], (int) ($json['counts']['usuarios_sistema'] ?? -1));
        $this->assertSame($antes['configuracion_sistema'], (int) ($json['counts']['configuracion_sistema'] ?? -1));

        foreach (['usuarios', 'mantenimientos', 'usuarios_sistema', 'configuracion_sistema'] as $tabla) {
            $this->assertArrayHasKey($tabla, $json['hashes'] ?? []);
            $this->assertSame(64, strlen((string) ($json['hashes'][$tabla]['sha256'] ?? '')));
        }

        $despues = $this->snapshotCounts();

        $this->assertSame($antes, $despues);
    }

    public function test_rechaza_confirmacion_de_base_incorrecta(): void
    {
        $exitCode = Artisan::call('mantenimientos:auditoria', [
            '--database' => 'base_incorrecta',
        ]);

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('no coincide', Artisan::output());
    }

    public function test_leer_escalar_devuelve_vacio_cuando_selectone_regresa_null(): void
    {
        $service = new class extends BaseDatosAuditoriaService {
            protected function selectOne(string $sql): ?object
            {
                return null;
            }
        };

        $this->assertSame('', $this->invocarLeerEscalar($service, 'SELECT VERSION() AS version', 'version'));
    }

    public function test_leer_escalar_devuelve_vacio_si_falta_la_propiedad_solicitada(): void
    {
        $service = new class extends BaseDatosAuditoriaService {
            protected function selectOne(string $sql): ?object
            {
                return (object) ['otra_propiedad' => 'valor'];
            }
        };

        $this->assertSame('', $this->invocarLeerEscalar($service, 'SELECT VERSION() AS version', 'version'));
    }

    public function test_excepcion_sql_se_propaga_desde_el_servicio_y_revierte_la_transaccion(): void
    {
        $antes = $this->snapshotCounts();
        $connection = DB::connection();

        $service = new class extends BaseDatosAuditoriaService {
            protected function selectOne(string $sql): ?object
            {
                throw new QueryException(
                    'mysql',
                    'SELECT VERSION() AS version',
                    [],
                    new RuntimeException('fallo controlado')
                );
            }
        };

        try {
            $service->generar();
            $this->fail('Se esperaba una excepción SQL propagada desde el servicio.');
        } catch (QueryException $exception) {
            $this->assertSame('SELECT VERSION() AS version', $exception->getSql());
        }

        $this->assertSame(0, $connection->transactionLevel());
        $this->assertSame($antes, $this->snapshotCounts());
    }

    public function test_comando_falla_sin_json_parcial_cuando_la_auditoria_lanza_excepcion(): void
    {
        $base = DB::connection()->getDatabaseName();
        $antes = $this->snapshotCounts();
        $connection = DB::connection();

        $service = new class extends BaseDatosAuditoriaService {
            protected function selectOne(string $sql): ?object
            {
                throw new QueryException(
                    'mysql',
                    'SELECT VERSION() AS version',
                    [],
                    new RuntimeException('fallo controlado')
                );
            }
        };

        app()->instance(BaseDatosAuditoriaService::class, $service);

        try {
            $exitCode = Artisan::call('mantenimientos:auditoria', [
                '--database' => $base,
            ]);
        } finally {
            app()->forgetInstance(BaseDatosAuditoriaService::class);
        }

        $this->assertSame(1, $exitCode);

        $output = trim(Artisan::output());
        $this->assertStringContainsString('No se pudo completar la auditoria de la base de datos.', $output);
        $this->assertStringNotContainsString('"meta"', $output);
        $this->assertStringNotContainsString('"counts"', $output);
        $this->assertStringNotContainsString('SELECT VERSION()', $output);
        $this->assertSame($antes, $this->snapshotCounts());
        $this->assertSame(0, $connection->transactionLevel());
    }

    public function test_las_lecturas_ocurren_dentro_de_una_transaccion_y_el_islamiento_se_configura_antes_de_abrirla(): void
    {
        $realConnection = DB::connection();
        $eventos = [];
        $fakeConnection = new class ($realConnection, $eventos) {
            public int $level = 0;
            public array $eventos = [];

            public function __construct(private $realConnection, array &$eventos)
            {
                $this->eventos =& $eventos;
            }

            public function getDatabaseName(): string
            {
                return (string) $this->realConnection->getDatabaseName();
            }

            public function transactionLevel(): int
            {
                return $this->level;
            }

            public function statement(string $sql): bool
            {
                $this->eventos[] = [
                    'evento' => 'statement',
                    'sql' => $sql,
                    'nivel' => $this->level,
                ];

                return true;
            }

            public function transaction(Closure $callback): mixed
            {
                $this->eventos[] = [
                    'evento' => 'transaction_antes',
                    'nivel' => $this->level,
                ];

                $this->level++;

                try {
                    return $this->realConnection->transaction(function () use ($callback) {
                        $this->eventos[] = [
                            'evento' => 'transaction_abierta',
                            'nivel_fake' => $this->level,
                            'nivel_real' => $this->realConnection->transactionLevel(),
                        ];

                        $resultado = $callback();

                        $this->eventos[] = [
                            'evento' => 'lecturas_completadas',
                            'nivel_fake' => $this->level,
                            'nivel_real' => $this->realConnection->transactionLevel(),
                        ];

                        return $resultado;
                    });
                } finally {
                    $this->level--;
                    $this->eventos[] = [
                        'evento' => 'transaction_despues',
                        'nivel' => $this->level,
                    ];
                }
            }

            public function selectOne(string $sql): ?object
            {
                $this->eventos[] = [
                    'evento' => 'selectOne',
                    'nivel' => $this->level,
                    'consulta' => $sql,
                ];

                return $this->realConnection->selectOne($sql);
            }

            public function table(string $tabla)
            {
                $this->eventos[] = [
                    'evento' => 'table',
                    'nivel' => $this->level,
                    'tabla' => $tabla,
                ];

                return $this->realConnection->table($tabla);
            }
        };

        $service = new class ($fakeConnection) extends BaseDatosAuditoriaService {
            public function __construct(private $connection)
            {
            }

            protected function connection()
            {
                return $this->connection;
            }

            protected function selectOne(string $sql): ?object
            {
                return $this->connection->selectOne($sql);
            }

            protected function table(string $tabla)
            {
                return $this->connection->table($tabla);
            }
        };

        $resultado = $service->generar();

        $this->assertSame($realConnection->getDatabaseName(), $resultado['meta']['database'] ?? null);
        $this->assertSame('statement', $eventos[0]['evento'] ?? null);
        $this->assertSame('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ', $eventos[0]['sql'] ?? null);
        $this->assertSame(0, $eventos[0]['nivel'] ?? -1);
        $this->assertSame('transaction_antes', $eventos[1]['evento'] ?? null);
        $this->assertSame(0, $eventos[1]['nivel'] ?? -1);
        $this->assertSame('transaction_abierta', $eventos[2]['evento'] ?? null);
        $this->assertSame(1, $eventos[2]['nivel_fake'] ?? -1);
        $this->assertSame(1, $eventos[2]['nivel_real'] ?? -1);
        $this->assertContains('selectOne', array_column($eventos, 'evento'));
        $this->assertContains('table', array_column($eventos, 'evento'));
        $this->assertSame('transaction_despues', $eventos[array_key_last($eventos)]['evento'] ?? null);
        $this->assertSame(0, $eventos[array_key_last($eventos)]['nivel'] ?? -1);
        $this->assertSame(0, $realConnection->transactionLevel());
    }

    private function snapshotCounts(): array
    {
        return [
            'usuarios' => DB::table('usuarios')->count(),
            'mantenimientos' => DB::table('mantenimientos')->count(),
            'usuarios_sistema' => DB::table('usuarios_sistema')->count(),
            'configuracion_sistema' => DB::table('configuracion_sistema')->count(),
        ];
    }

    private function invocarLeerEscalar(BaseDatosAuditoriaService $service, string $sql, string $campo): string
    {
        $method = new ReflectionMethod(BaseDatosAuditoriaService::class, 'leerEscalar');
        $method->setAccessible(true);

        return (string) $method->invoke($service, $sql, $campo);
    }
}
