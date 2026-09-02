<?php

namespace App\Services\Legacy;

use Illuminate\Support\Facades\DB;

class MantenimientoEventoRepository
{
    public function registrarEvento(
        int $mantenimientoId,
        string $tipoEvento,
        string $descripcion,
        array $datosAntes = [],
        array $datosDespues = [],
        ?int $usuarioSistemaId = null,
        ?string $ip = null
    ): void {
        if ($mantenimientoId <= 0) {
            throw new \InvalidArgumentException('El identificador de mantenimiento es invalido.');
        }

        $tipoEvento = trim($tipoEvento);
        if ($tipoEvento === '') {
            throw new \InvalidArgumentException('El tipo de evento es obligatorio.');
        }

        DB::table('mantenimiento_eventos')->insert([
            'mantenimiento_id' => $mantenimientoId,
            'tipo_evento' => $tipoEvento,
            'descripcion' => mb_substr(trim($descripcion), 0, 500),
            'datos_antes' => json_encode($datosAntes, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            'datos_despues' => json_encode($datosDespues, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR),
            'usuario_sistema_id' => $this->normalizarUsuarioSistemaId($usuarioSistemaId),
            'ip' => $this->normalizarIp($ip),
            'created_at' => now(),
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function obtenerUltimoEventoPorMantenimiento(int $mantenimientoId): ?array
    {
        if ($mantenimientoId <= 0) {
            return null;
        }

        $fila = DB::table('mantenimiento_eventos')
            ->select(
                'id',
                'mantenimiento_id',
                'tipo_evento',
                'descripcion',
                'datos_antes',
                'datos_despues',
                'usuario_sistema_id',
                'ip',
                'created_at'
            )
            ->where('mantenimiento_id', $mantenimientoId)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->first();

        return $fila ? (array) $fila : null;
    }

    private function normalizarUsuarioSistemaId(?int $usuarioSistemaId): ?int
    {
        if ($usuarioSistemaId === null || $usuarioSistemaId <= 0) {
            return null;
        }

        $existe = DB::table('usuarios_sistema')
            ->where('id', $usuarioSistemaId)
            ->exists();

        return $existe ? $usuarioSistemaId : null;
    }

    private function normalizarIp(?string $ip): ?string
    {
        $ip = trim((string) $ip);
        if ($ip === '') {
            return null;
        }

        return mb_substr($ip, 0, 45);
    }
}
