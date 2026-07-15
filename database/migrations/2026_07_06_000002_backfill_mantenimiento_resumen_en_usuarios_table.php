<?php

use App\Services\Legacy\UsuarioMantenimientoResumenService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $servicio = app(UsuarioMantenimientoResumenService::class);

        DB::table('usuarios')
            ->select('id')
            ->orderBy('id')
            ->chunkById(100, function ($usuarios) use ($servicio): void {
                foreach ($usuarios as $usuario) {
                    $servicio->sincronizarResumenUsuario((int) $usuario->id);
                }
            });
    }

    public function down(): void
    {
        // No se revierte el backfill de datos.
    }
};
