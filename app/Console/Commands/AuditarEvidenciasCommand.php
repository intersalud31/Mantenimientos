<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Throwable;

class AuditarEvidenciasCommand extends Command
{
    protected $signature = 'evidencias:auditar';

    protected $description = 'Audita evidencias PDF registradas y archivos fisicos sin modificar datos.';

    public function handle(): int
    {
        try {
            $disk = Storage::disk('public');
            $referencias = [];
            $rutasReferenciadas = [];

            DB::table('mantenimientos')
                ->select('id', 'evidencia_pdf')
                ->whereNotNull('evidencia_pdf')
                ->whereRaw("TRIM(evidencia_pdf) <> ''")
                ->orderBy('id')
                ->get()
                ->each(function (object $registro) use ($disk, &$referencias, &$rutasReferenciadas): void {
                    $ruta = $this->normalizarRuta((string) $registro->evidencia_pdf);
                    $existe = $ruta !== '' && $disk->exists($ruta);
                    $rutasReferenciadas[$ruta] = true;

                    $referencias[] = [
                        'mantenimiento_id' => (int) $registro->id,
                        'ruta_registrada' => $ruta,
                        'archivo_existe' => $existe,
                        'tamano_bytes' => $existe ? $disk->size($ruta) : null,
                    ];
                });

            $archivos = [];
            foreach ($disk->allFiles() as $archivo) {
                $ruta = $this->normalizarRuta($archivo);
                if ($ruta === '' || isset($rutasReferenciadas[$ruta])) {
                    continue;
                }

                $archivos[] = [
                    'ruta' => $ruta,
                    'tamano_bytes' => $disk->size($ruta),
                ];
            }

            $this->line(json_encode([
                'evidencias' => $referencias,
                'archivos_huerfanos' => $archivos,
                'resumen' => [
                    'total_evidencias_registradas' => count($referencias),
                    'existentes' => count(array_filter($referencias, static fn (array $item): bool => $item['archivo_existe'] === true)),
                    'faltantes' => count(array_filter($referencias, static fn (array $item): bool => $item['archivo_existe'] === false)),
                    'huerfanos' => count($archivos),
                ],
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR));

            return self::SUCCESS;
        } catch (Throwable) {
            $this->error('No se pudo completar la auditoria de evidencias.');

            return self::FAILURE;
        }
    }

    private function normalizarRuta(string $ruta): string
    {
        return ltrim(str_replace('\\', '/', trim($ruta)), '/');
    }
}
