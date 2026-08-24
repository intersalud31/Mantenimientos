<?php

namespace App\Services\Legacy;

use App\Services\SemestreService;

class NotificationSummaryService
{
    public function __construct(
        private readonly MantenimientoRepository $mantenimientoRepository,
        private readonly SemestreService $semestreService
    ) {
    }

    /**
     * @return array{
     *     pendientes_programacion:int,
     *     mantenimientos_hoy:int,
     *     total:int,
     *     empty_message:string,
     *     items:array<int, array<string, mixed>>
     * }
     */
    public function resumen(?array $periodo = null): array
    {
        return $this->obtenerResumen($periodo);

        $periodo = $periodo ?? $this->semestreService->actual();

        $pendientesProgramacion = $this->mantenimientoRepository->contarProximosParaNotificacion($periodo);
        $mantenimientosHoy = $this->mantenimientoRepository->contarProgramadosHoy();
        $total = $pendientesProgramacion + $mantenimientosHoy;

        return [
            'pendientes_programacion' => $pendientesProgramacion,
            'mantenimientos_hoy' => $mantenimientosHoy,
            'total' => $total,
            'empty_message' => 'No tienes notificaciones pendientes.',
            'items' => [
                [
                    'title' => 'Pendientes de programación',
                    'description' => $this->describirPendientes($pendientesProgramacion),
                    'link_label' => 'Ver próximos',
                    'route' => 'panel.proximos',
                    'count' => $pendientesProgramacion,
                ],
                [
                    'title' => 'Mantenimientos de hoy',
                    'description' => $this->describirHoy($mantenimientosHoy),
                    'link_label' => 'Ver programados',
                    'route' => 'panel.programados',
                    'count' => $mantenimientosHoy,
                ],
            ],
        ];
    }

    /**
     * @return array{
     *     mantenimientos_vencidos:int,
     *     mantenimientos_proximos:int,
     *     mantenimientos_hoy:int,
     *     total:int,
     *     empty_message:string,
     *     items:array<int, array<string, mixed>>
     * }
     */
    public function obtenerResumen(?array $periodo = null): array
    {
        $periodo = $periodo ?? $this->semestreService->actual();

        $mantenimientosVencidos = $this->mantenimientoRepository->contarNotificacionesVencidas($periodo);
        $mantenimientosProximos = $this->mantenimientoRepository->contarNotificacionesProximas($periodo);
        $mantenimientosHoy = $this->mantenimientoRepository->contarNotificacionesHoy();
        $total = $mantenimientosVencidos + $mantenimientosProximos + $mantenimientosHoy;

        return [
            'mantenimientos_vencidos' => $mantenimientosVencidos,
            'mantenimientos_proximos' => $mantenimientosProximos,
            'mantenimientos_hoy' => $mantenimientosHoy,
            'total' => $total,
            'empty_message' => 'No tienes notificaciones pendientes.',
            'items' => [
                [
                    'title' => 'Vencidos',
                    'description' => $this->describirVencidos($mantenimientosVencidos),
                    'link_label' => 'Ver',
                    'route' => 'panel.notificaciones.vencidos',
                    'count' => $mantenimientosVencidos,
                    'tone' => 'danger',
                ],
                [
                    'title' => 'Proximos 7 dias',
                    'description' => $this->describirProximos($mantenimientosProximos),
                    'link_label' => 'Ver',
                    'route' => 'panel.notificaciones.proximos',
                    'count' => $mantenimientosProximos,
                    'tone' => 'warning',
                ],
                [
                    'title' => 'Mantenimientos de hoy',
                    'description' => $this->describirHoy($mantenimientosHoy),
                    'link_label' => 'Ver',
                    'route' => 'panel.notificaciones.hoy',
                    'count' => $mantenimientosHoy,
                    'tone' => 'info',
                ],
            ],
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function obtenerVencidos(?array $periodo = null, int $limite = 100, int $desplazamiento = 0): array
    {
        return $this->mantenimientoRepository->listarNotificacionesVencidas($limite, $desplazamiento, $periodo);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function obtenerProximos(?array $periodo = null, int $limite = 100, int $desplazamiento = 0): array
    {
        return $this->mantenimientoRepository->listarNotificacionesProximas($limite, $desplazamiento, $periodo);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function obtenerHoy(?array $periodo = null, int $limite = 100, int $desplazamiento = 0): array
    {
        return $this->mantenimientoRepository->listarNotificacionesHoy($limite, $desplazamiento);
    }

    private function describirVencidos(int $cantidad): string
    {
        if ($cantidad === 1) {
            return '1 mantenimiento atrasado.';
        }

        return $cantidad . ' mantenimientos atrasados.';
    }

    private function describirProximos(int $cantidad): string
    {
        if ($cantidad === 1) {
            return '1 mantenimiento en los proximos 7 dias.';
        }

        return $cantidad . ' mantenimientos en los proximos 7 dias.';
    }

    private function describirPendientes(int $cantidad): string
    {
        if ($cantidad === 1) {
            return '1 equipo pendiente de programación.';
        }

        return $cantidad . ' equipos pendientes de programación.';
    }

    private function describirHoy(int $cantidad): string
    {
        if ($cantidad === 1) {
            return '1 mantenimiento programado para hoy.';
        }

        return $cantidad . ' mantenimientos programados para hoy.';
    }
}

