<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('usuarios', function (Blueprint $table): void {
            if (!Schema::hasColumn('usuarios', 'ultima_fecha_ejecucion')) {
                $table->date('ultima_fecha_ejecucion')->nullable()->after('estado_usuario');
            }

            if (!Schema::hasColumn('usuarios', 'ultima_fecha_mantenimiento')) {
                $table->date('ultima_fecha_mantenimiento')->nullable()->after('ultima_fecha_ejecucion');
            }

            if (!Schema::hasColumn('usuarios', 'proxima_fecha_mantenimiento')) {
                $table->date('proxima_fecha_mantenimiento')->nullable()->after('ultima_fecha_mantenimiento');
            }

            if (!Schema::hasColumn('usuarios', 'ultimo_mantenimiento_id')) {
                $table->unsignedBigInteger('ultimo_mantenimiento_id')->nullable()->after('proxima_fecha_mantenimiento');
            }

            if (!Schema::hasColumn('usuarios', 'ultimo_mantenimiento_estado')) {
                $table->string('ultimo_mantenimiento_estado', 30)->nullable()->after('ultimo_mantenimiento_id');
            }

            if (!Schema::hasColumn('usuarios', 'mantenimiento_resumen_actualizado_at')) {
                $table->dateTime('mantenimiento_resumen_actualizado_at')->nullable()->after('ultimo_mantenimiento_estado');
            }
        });
    }

    public function down(): void
    {
        Schema::table('usuarios', function (Blueprint $table): void {
            if (Schema::hasColumn('usuarios', 'mantenimiento_resumen_actualizado_at')) {
                $table->dropColumn('mantenimiento_resumen_actualizado_at');
            }

            if (Schema::hasColumn('usuarios', 'ultimo_mantenimiento_estado')) {
                $table->dropColumn('ultimo_mantenimiento_estado');
            }

            if (Schema::hasColumn('usuarios', 'ultimo_mantenimiento_id')) {
                $table->dropColumn('ultimo_mantenimiento_id');
            }

            if (Schema::hasColumn('usuarios', 'proxima_fecha_mantenimiento')) {
                $table->dropColumn('proxima_fecha_mantenimiento');
            }

            if (Schema::hasColumn('usuarios', 'ultima_fecha_mantenimiento')) {
                $table->dropColumn('ultima_fecha_mantenimiento');
            }

            if (Schema::hasColumn('usuarios', 'ultima_fecha_ejecucion')) {
                $table->dropColumn('ultima_fecha_ejecucion');
            }
        });
    }
};
