<?php

namespace App\Console\Commands;

use App\Services\Diagnostics\BaseDatosAuditoriaService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Throwable;

class BaseDatosAuditoriaCommand extends Command
{
    protected $signature = 'mantenimientos:auditoria
        {--database= : Nombre exacto de la base a confirmar}
    ';

    protected $description = 'Genera una auditoria de solo lectura de la base activa con conteos y huellas.';

    public function handle(BaseDatosAuditoriaService $service): int
    {
        $baseEsperada = trim((string) $this->option('database'));
        if ($baseEsperada === '') {
            $this->error('Debes confirmar la base exacta usando --database=<nombre>.');

            return self::FAILURE;
        }

        if (!$service->confirmarBase($baseEsperada)) {
            $this->error('La base activa no coincide con la confirmada. Activa=' . DB::connection()->getDatabaseName() . ', esperada=' . $baseEsperada);

            return self::FAILURE;
        }

        try {
            $this->line(json_encode($service->generar(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } catch (Throwable $throwable) {
            $this->error('No se pudo completar la auditoria de la base de datos.');

            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
