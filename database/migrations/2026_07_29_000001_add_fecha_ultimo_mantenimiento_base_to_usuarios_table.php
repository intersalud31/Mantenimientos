<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('usuarios', 'fecha_ultimo_mantenimiento_base')) {
            Schema::table('usuarios', function (Blueprint $table): void {
                $table->date('fecha_ultimo_mantenimiento_base')->nullable()->after('tipo_equipo');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('usuarios', 'fecha_ultimo_mantenimiento_base')) {
            Schema::table('usuarios', function (Blueprint $table): void {
                $table->dropColumn('fecha_ultimo_mantenimiento_base');
            });
        }
    }
};
