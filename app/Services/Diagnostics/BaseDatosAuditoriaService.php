<?php

namespace App\Services\Diagnostics;

use Illuminate\Support\Facades\DB;

class BaseDatosAuditoriaService
{
    /**
     * @return array<string, mixed>
     */
    public function generar(): array
    {
        $connection = $this->connection();
        if (method_exists($connection, 'transactionLevel') && $connection->transactionLevel() === 0) {
            $connection->statement('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
        }

        return $connection->transaction(function () use ($connection): array {
            $database = (string) $connection->getDatabaseName();

            return [
                'meta' => [
                    'database' => $database,
                    'server_version' => $this->leerEscalar('SELECT VERSION() AS version', 'version'),
                    'charset_database' => $this->leerEscalar('SELECT @@character_set_database AS value', 'value'),
                    'collation_database' => $this->leerEscalar('SELECT @@collation_database AS value', 'value'),
                ],
                'counts' => [
                    'usuarios' => $this->contar('usuarios'),
                    'mantenimientos' => $this->contar('mantenimientos'),
                    'usuarios_sistema' => $this->contar('usuarios_sistema'),
                    'configuracion_sistema' => $this->contar('configuracion_sistema'),
                ],
                'hashes' => [
                    'usuarios' => $this->hashTable('usuarios', [
                        'id', 'area_id', 'nombre', 'codigo_activo', 'serial_equipo', 'tipo_equipo',
                        'fecha_ultimo_mantenimiento_base', 'estado_usuario', 'ultima_fecha_ejecucion',
                        'ultima_fecha_mantenimiento', 'proxima_fecha_mantenimiento', 'ultimo_mantenimiento_id',
                        'ultimo_mantenimiento_estado', 'mantenimiento_resumen_actualizado_at', 'created_at', 'updated_at',
                    ]),
                    'mantenimientos' => $this->hashTable('mantenimientos', [
                        'id', 'usuario_id', 'fecha_programada', 'hora_programada', 'estado', 'fecha_ejecucion',
                        'tecnico_responsable', 'observaciones', 'evidencia_pdf', 'created_at', 'updated_at',
                    ]),
                    'usuarios_sistema' => $this->hashTable('usuarios_sistema', [
                        'id', 'nombre', 'correo', 'password_hash', 'rol', 'estado', 'ultimo_acceso', 'created_at', 'updated_at',
                    ]),
                    'configuracion_sistema' => $this->hashTable('configuracion_sistema', [
                        'id', 'clave', 'valor', 'tipo', 'descripcion', 'actualizado_por', 'created_at', 'updated_at',
                    ]),
                ],
            ];
        });
    }

    public function confirmarBase(string $baseEsperada): bool
    {
        return $baseEsperada !== '' && $this->connection()->getDatabaseName() === $baseEsperada;
    }

    private function contar(string $tabla): int
    {
        return (int) $this->table($tabla)->count();
    }

    private function leerEscalar(string $sql, string $campo): string
    {
        $fila = $this->selectOne($sql);
        if ($fila === null || !isset($fila->{$campo})) {
            return '';
        }

        return (string) $fila->{$campo};
    }

    /**
     * @param array<int, string> $columnas
     */
    private function hashTable(string $tabla, array $columnas): array
    {
        $hash = hash_init('sha256');
        $total = $this->contar($tabla);

        $this->table($tabla)
            ->select($columnas)
            ->orderBy('id')
            ->chunkById(500, function ($rows) use ($hash): void {
                foreach ($rows as $row) {
                    hash_update($hash, json_encode((array) $row, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n");
                }
            }, 'id');

        return [
            'count' => $total,
            'sha256' => hash_final($hash),
        ];
    }

    protected function connection()
    {
        return DB::connection();
    }

    protected function selectOne(string $sql): ?object
    {
        return DB::selectOne($sql);
    }

    protected function table(string $tabla)
    {
        return DB::table($tabla);
    }
}
