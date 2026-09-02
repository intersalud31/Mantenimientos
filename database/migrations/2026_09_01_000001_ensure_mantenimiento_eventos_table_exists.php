<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mantenimiento_eventos')) {
            return;
        }

        Schema::create('mantenimiento_eventos', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('mantenimiento_id');
            $table->string('tipo_evento', 50);
            $table->string('descripcion', 500)->nullable();
            $table->longText('datos_antes')->nullable();
            $table->longText('datos_despues')->nullable();
            $table->unsignedBigInteger('usuario_sistema_id')->nullable();
            $table->string('ip', 45)->nullable();
            $table->dateTime('created_at')->useCurrent();

            $table->index('mantenimiento_id', 'idx_mantenimiento_eventos_mantenimiento_id');
            $table->index(['mantenimiento_id', 'created_at'], 'idx_mantenimiento_eventos_mantenimiento');
            $table->index(['tipo_evento', 'created_at'], 'idx_mantenimiento_eventos_tipo');

            if (Schema::hasTable('mantenimientos')) {
                $table->foreign('mantenimiento_id', 'fk_mantenimiento_eventos_mantenimiento')
                    ->references('id')
                    ->on('mantenimientos')
                    ->restrictOnDelete()
                    ->restrictOnUpdate();
            }
        });
    }

    public function down(): void
    {
        // Intencionalmente no elimina la tabla para proteger la bitacora tecnica.
    }
};
