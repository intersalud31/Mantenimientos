<?php

namespace App\Services\Legacy;

class ReversionEstadoMantenimientoService
{
    public function __construct(private readonly GestionEstadoMantenimientoService $gestionService)
    {
    }

    public function analizarUsuarioPorCodigoActivo(int $codigoActivo): array
    {
        return $this->gestionService->analizarUsuarioPorCodigoActivo($codigoActivo);
    }

    public function previsualizarPorCodigoActivo(int $codigoActivo, string $estadoDestino, array $datos = []): array
    {
        return $this->gestionService->previsualizarPorCodigoActivo($codigoActivo, $estadoDestino, $datos);
    }

    public function ejecutarPorCodigoActivo(
        int $codigoActivo,
        string $estadoDestino,
        array $datos,
        string $motivo,
        string $previewHash,
        ?array $administrador = null
    ): array {
        return $this->gestionService->ejecutarPorCodigoActivo(
            $codigoActivo,
            $estadoDestino,
            $datos,
            $motivo,
            $previewHash,
            $administrador
        );
    }
}
