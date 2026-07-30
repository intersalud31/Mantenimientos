<?php

namespace App\Console\Commands;

use App\Services\Diagnostics\Bd3SnapshotService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use RuntimeException;

class Bd3SnapshotCommand extends Command
{
    protected $signature = 'bd3:snapshot
        {--output= : Ruta del archivo JSON de salida}
        {--force : Permite sobrescribir el archivo de salida}
    ';

    protected $description = 'Genera una fotografía lógica de la base aislada para BD-3.';

    public function handle(Bd3SnapshotService $service): int
    {
        try {
            $service->assertBaseAislada();
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $snapshot = $service->generar();
        $output = trim((string) $this->option('output'));

        if ($output === '') {
            $nombre = 'bd3_snapshot_' . now()->format('Ymd_His') . '.json';
            $output = storage_path('app/diagnostics/' . $nombre);
        }

        if (File::exists($output) && ! $this->option('force')) {
            $this->error('El archivo de salida ya existe. Usa --force si deseas sobrescribirlo.');

            return self::FAILURE;
        }

        File::ensureDirectoryExists(dirname($output));
        File::put($output, json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $this->info('Fotografía lógica generada en: ' . $output);
        $this->line('Base verificada: ' . ($snapshot['meta']['database'] ?? ''));

        return self::SUCCESS;
    }
}
