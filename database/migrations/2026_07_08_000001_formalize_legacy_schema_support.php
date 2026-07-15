<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('tecnicos')) {
            Schema::create('tecnicos', function (Blueprint $table): void {
                $table->id();
                $table->string('nombre', 150)->unique();
                $table->boolean('estado')->default(true)->index();
                $table->timestamps();
            });
        }

        if (!Schema::hasColumn('usuarios', 'tipo_equipo')) {
            Schema::table('usuarios', function (Blueprint $table): void {
                $table->enum('tipo_equipo', ['normal', 'nuevo'])->default('normal')->after('serial_equipo');
            });
        }

        if (!Schema::hasColumn('mantenimientos', 'tecnico_responsable')) {
            Schema::table('mantenimientos', function (Blueprint $table): void {
                $table->string('tecnico_responsable', 150)->nullable()->after('fecha_ejecucion');
            });
        }

        if (!Schema::hasColumn('mantenimientos', 'evidencia_pdf')) {
            Schema::table('mantenimientos', function (Blueprint $table): void {
                $table->string('evidencia_pdf', 255)->nullable()->after('observaciones');
            });
        }

        if (Schema::hasColumn('usuarios', 'tipo_equipo')) {
            DB::statement(
                "UPDATE usuarios u
                SET u.tipo_equipo = CASE
                    WHEN EXISTS (
                        SELECT 1
                        FROM mantenimientos m
                        WHERE m.usuario_id = u.id
                          AND (
                                m.observaciones LIKE '%Equipo nuevo registrado.%'
                                OR m.observaciones LIKE '%(N=nuevo)%'
                          )
                    ) THEN 'nuevo'
                    ELSE 'normal'
                END
                WHERE u.tipo_equipo IS NULL
                   OR u.tipo_equipo NOT IN ('normal', 'nuevo')
                   OR (
                        u.tipo_equipo = 'normal'
                        AND EXISTS (
                            SELECT 1
                            FROM mantenimientos m2
                            WHERE m2.usuario_id = u.id
                              AND (
                                    m2.observaciones LIKE '%Equipo nuevo registrado.%'
                                    OR m2.observaciones LIKE '%(N=nuevo)%'
                              )
                        )
                   )"
            );
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('mantenimientos', 'evidencia_pdf')) {
            Schema::table('mantenimientos', function (Blueprint $table): void {
                $table->dropColumn('evidencia_pdf');
            });
        }

        if (Schema::hasColumn('mantenimientos', 'tecnico_responsable')) {
            Schema::table('mantenimientos', function (Blueprint $table): void {
                $table->dropColumn('tecnico_responsable');
            });
        }

        if (Schema::hasColumn('usuarios', 'tipo_equipo')) {
            Schema::table('usuarios', function (Blueprint $table): void {
                $table->dropColumn('tipo_equipo');
            });
        }

        if (Schema::hasTable('tecnicos')) {
            Schema::dropIfExists('tecnicos');
        }
    }
};
