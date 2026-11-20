<?php

namespace App\Console\Commands;

use App\Services\Legacy\UsuarioNombreRepairService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CorregirNombresUsuariosCommand extends Command
{
    protected $signature = 'usuarios:corregir-nombres
        {--dry-run : Solo muestra los cambios sin escribirlos}
        {--apply : Aplica las correcciones despues de revisarlas}';

    protected $description = 'Corrige nombres de usuarios importados con caracteres perdidos en la base legacy.';

    public function handle(UsuarioNombreRepairService $repairService): int
    {
        $apply = (bool) $this->option('apply');
        $dryRun = (bool) $this->option('dry-run') || !$apply;
        $cambios = 0;

        DB::table('usuarios')
            ->select('id', 'nombre')
            ->orderBy('id')
            ->chunkById(100, function ($usuarios) use ($repairService, $dryRun, &$cambios): bool {
                foreach ($usuarios as $usuario) {
                    $nombreActual = (string) ($usuario->nombre ?? '');
                    $nombreCorregido = $repairService->reparar($nombreActual);

                    if ($nombreCorregido === $nombreActual) {
                        continue;
                    }

                    $cambios++;

                    if ($dryRun) {
                        $this->line(sprintf('[dry-run] #%d %s => %s', (int) $usuario->id, $nombreActual, $nombreCorregido));
                        continue;
                    }

                    DB::table('usuarios')
                        ->where('id', (int) $usuario->id)
                        ->update([
                            'nombre' => $nombreCorregido,
                            'updated_at' => now(),
                        ]);

                    $this->line(sprintf('#%d %s => %s', (int) $usuario->id, $nombreActual, $nombreCorregido));
                }

                return true;
            }, 'id');

        $this->info('Cambios aplicados: ' . $cambios . ($dryRun ? ' (dry-run)' : ''));

        return self::SUCCESS;
    }
}
