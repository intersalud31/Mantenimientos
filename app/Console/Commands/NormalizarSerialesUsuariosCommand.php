<?php

namespace App\Console\Commands;

use App\Services\Legacy\SerialEquipoNormalizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class NormalizarSerialesUsuariosCommand extends Command
{
    protected $signature = 'usuarios:normalizar-seriales {--dry-run : Solo muestra los cambios sin escribirlos}';

    protected $description = 'Normaliza a mayusculas los seriales reales de equipos en la tabla legacy de usuarios.';

    public function handle(SerialEquipoNormalizer $normalizer): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $cambios = 0;

        DB::table('usuarios')
            ->select('id', 'serial_equipo')
            ->orderBy('id')
            ->chunkById(100, function ($usuarios) use ($dryRun, $normalizer, &$cambios): bool {
                foreach ($usuarios as $usuario) {
                    $serialActual = $usuario->serial_equipo;
                    $serialNormalizado = $normalizer->normalizar(is_string($serialActual) ? $serialActual : null);

                    if (!$this->debeActualizar($serialActual, $serialNormalizado, $normalizer)) {
                        continue;
                    }

                    $cambios++;

                    $mensaje = sprintf(
                        '#%d %s => %s',
                        (int) $usuario->id,
                        $this->exportarValor($serialActual),
                        $this->exportarValor($serialNormalizado)
                    );

                    if ($dryRun) {
                        $this->line('[dry-run] ' . $mensaje);
                        continue;
                    }

                    DB::table('usuarios')
                        ->where('id', (int) $usuario->id)
                        ->update([
                            'serial_equipo' => $serialNormalizado,
                            'updated_at' => now(),
                        ]);

                    $this->line($mensaje);
                }

                return true;
            }, 'id');

        $this->info('Cambios detectados: ' . $cambios . ($dryRun ? ' (dry-run)' : ''));

        return self::SUCCESS;
    }

    private function debeActualizar(mixed $serialActual, ?string $serialNormalizado, SerialEquipoNormalizer $normalizer): bool
    {
        if (!is_string($serialActual)) {
            return false;
        }

        $serialRecortado = trim($serialActual);
        if ($serialRecortado === '' || $normalizer->esValorEspecial($serialRecortado)) {
            return false;
        }

        return $serialNormalizado !== $serialActual;
    }

    private function exportarValor(mixed $valor): string
    {
        if ($valor === null) {
            return 'NULL';
        }

        $valor = (string) $valor;

        return '"' . $valor . '"';
    }
}
