<?php

namespace App\Console\Commands;

use App\Services\Legacy\UsuarioMantenimientoResumenService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class MantenimientoResumenesCommand extends Command
{
    protected $signature = 'mantenimientos:resumenes
        {--usuario= : ID del usuario a auditar o reconstruir}
        {--all : Procesa todos los usuarios}
        {--chunk=100 : Tamano de lote}
        {--apply : Aplica cambios; sin esta opcion solo audita}
    ';

    protected $description = 'Audita y reconstruye los resumenes derivados de usuarios desde mantenimientos.';

    public function handle(UsuarioMantenimientoResumenService $service): int
    {
        $aplicar = (bool) $this->option('apply');
        $chunk = max(1, (int) $this->option('chunk'));
        $usuarioId = (int) $this->option('usuario');
        $consulta = DB::table('usuarios')->select('id')->orderBy('id');
        if ($usuarioId > 0) {
            $consulta->where('id', $usuarioId);
        }

        $total = 0;
        $correctos = 0;
        $desactualizados = 0;
        $actualizados = 0;

        $consulta->orderBy('id')->chunkById($chunk, function ($usuarios) use ($service, $aplicar, &$total, &$correctos, &$desactualizados, &$actualizados): bool {
            foreach ($usuarios as $fila) {
                $id = (int) $fila->id;
                $auditoria = $service->auditarResumenUsuario($id);
                if (!($auditoria['ok'] ?? false)) {
                    continue;
                }

                $total++;

                if (($auditoria['coincide'] ?? false) === true) {
                    $correctos++;
                    continue;
                }

                $desactualizados++;

                if (!$aplicar) {
                    continue;
                }

                DB::transaction(function () use ($service, $id): void {
                    $service->sincronizarResumenUsuario($id);
                });

                $actualizados++;
            }

            return true;
        }, 'id');

        $this->line('usuarios_auditados=' . $total);
        $this->line('resumenes_correctos=' . $correctos);
        $this->line('resumenes_desactualizados=' . $desactualizados);
        $this->line('modo=' . ($aplicar ? 'apply' : 'dry-run'));
        $this->line('resumenes_actualizados=' . ($aplicar ? $actualizados : 0));

        return self::SUCCESS;
    }
}
