<?php

namespace App\Services\Legacy;

use Illuminate\Http\Request;

class PanelNavigationService
{
    public function resolverRutaLegacyApartado(string $apartado): ?string
    {
        // Compatibilidad con el panel legacy que navegaba por ?apartado=...
        $apartadoNormalizado = strtolower(trim($apartado));

        return match ($apartadoNormalizado) {
            'calculadora', 'resumen', 'notificaciones', 'reportes' => 'panel.reportes',
            'reversiones', 'configuracion' => 'panel.configuracion',
            'usuarios' => 'panel.usuarios',
            'proximos' => 'panel.proximos',
            'programados' => 'panel.programados',
            'hechos' => 'panel.hechos',
            default => null,
        };
    }

    public function querySinApartado(array $query): array
    {
        unset($query['apartado']);

        return array_filter(
            $query,
            static fn ($valor) => $valor !== null && $valor !== ''
        );
    }

    public function urlConQuery(string $url, array $query): string
    {
        if ($query === []) {
            return $url;
        }

        return $url . (str_contains($url, '?') ? '&' : '?') . http_build_query($query, '', '&', PHP_QUERY_RFC3986);
    }

    public function paginaDesdeRequest(Request $request, string $parametro, int $default = 1): int
    {
        return max(1, (int) $request->query($parametro, $default));
    }

    public function porPaginaDesdeRequest(Request $request, string $parametro, array $permitidos = [10, 25, 50], int $default = 10): int
    {
        $valor = (int) $request->query($parametro, $default);

        return in_array($valor, $permitidos, true) ? $valor : $default;
    }

    public function totalPaginas(int $total, int $porPagina): int
    {
        if ($porPagina <= 0) {
            $porPagina = 10;
        }

        return max(1, (int) ceil($total / $porPagina));
    }

    public function offset(int $pagina, int $porPagina): int
    {
        return max(0, ($pagina - 1) * $porPagina);
    }
}
