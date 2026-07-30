<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('notificacion_queue');
        Schema::dropIfExists('notificaciones_inapp');
        Schema::dropIfExists('notificaciones');
        Schema::dropIfExists('mantenimiento_eventos');
        Schema::dropIfExists('auditoria_sistema');
    }

    public function down(): void
    {
        if (!Schema::hasTable('auditoria_sistema')) {
            Schema::create('auditoria_sistema', function (Blueprint $table): void {
                $table->id();
                $table->string('tabla_afectada', 120);
                $table->unsignedBigInteger('registro_id');
                $table->string('accion', 50);
                $table->longText('antes')->nullable();
                $table->longText('despues')->nullable();
                $table->unsignedBigInteger('usuario_sistema_id')->nullable();
                $table->string('ip', 45)->nullable();
                $table->string('user_agent', 255)->nullable();
                $table->dateTime('created_at')->useCurrent();
                $table->index(['tabla_afectada', 'registro_id', 'created_at'], 'idx_auditoria_tabla_registro');
                $table->index(['accion', 'created_at'], 'idx_auditoria_accion');
            });
        }

        if (!Schema::hasTable('mantenimiento_eventos')) {
            Schema::create('mantenimiento_eventos', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('mantenimiento_id');
                $table->enum('tipo_evento', ['creado', 'programado', 'realizado', 'revertido', 'cancelado', 'normalizado']);
                $table->string('descripcion', 500)->nullable();
                $table->longText('datos_antes')->nullable();
                $table->longText('datos_despues')->nullable();
                $table->unsignedBigInteger('usuario_sistema_id')->nullable();
                $table->string('ip', 45)->nullable();
                $table->dateTime('created_at')->useCurrent();
                $table->index(['mantenimiento_id', 'created_at'], 'idx_mantenimiento_eventos_mantenimiento');
                $table->index(['tipo_evento', 'created_at'], 'idx_mantenimiento_eventos_tipo');
                $table->foreign('mantenimiento_id', 'fk_mantenimiento_eventos_mantenimiento')
                    ->references('id')
                    ->on('mantenimientos')
                    ->restrictOnDelete()
                    ->restrictOnUpdate();
            });
        }

        if (!Schema::hasTable('notificaciones')) {
            Schema::create('notificaciones', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('mantenimiento_id');
                $table->unsignedBigInteger('usuario_id');
                $table->enum('tipo', ['preaviso_7d', 'vencido', 'recordatorio_hoy'])->default('preaviso_7d');
                $table->enum('canal', ['inapp', 'email'])->default('inapp');
                $table->string('titulo', 180);
                $table->text('mensaje');
                $table->enum('estado', ['pendiente', 'enviada', 'error'])->default('pendiente');
                $table->dateTime('created_at')->useCurrent();
                $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();
                $table->index(['mantenimiento_id'], 'fk_notif_mantenimiento');
                $table->index(['estado', 'canal'], 'idx_notif_estado_canal');
                $table->index(['usuario_id', 'created_at'], 'idx_notif_usuario_fecha');
                $table->foreign('mantenimiento_id', 'fk_notif_mantenimiento')
                    ->references('id')
                    ->on('mantenimientos')
                    ->restrictOnDelete()
                    ->restrictOnUpdate();
                $table->foreign('usuario_id', 'fk_notif_usuario')
                    ->references('id')
                    ->on('usuarios')
                    ->restrictOnDelete()
                    ->restrictOnUpdate();
            });
        }

        if (!Schema::hasTable('notificaciones_inapp')) {
            Schema::create('notificaciones_inapp', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('notificacion_id');
                $table->string('destinatario_ti', 120)->default('TI');
                $table->boolean('leida')->default(false);
                $table->dateTime('leida_at')->nullable();
                $table->dateTime('created_at')->useCurrent();
                $table->unique(['notificacion_id', 'destinatario_ti'], 'uq_notificaciones_inapp_notificacion_dest');
                $table->index(['leida', 'created_at'], 'idx_notificaciones_inapp_leida');
                $table->foreign('notificacion_id', 'fk_notificaciones_inapp_notificacion')
                    ->references('id')
                    ->on('notificaciones')
                    ->cascadeOnDelete()
                    ->restrictOnUpdate();
            });
        }

        if (!Schema::hasTable('notificacion_queue')) {
            Schema::create('notificacion_queue', function (Blueprint $table): void {
                $table->id();
                $table->unsignedBigInteger('notificacion_id');
                $table->enum('canal', ['inapp', 'email']);
                $table->enum('estado', ['pendiente', 'procesando', 'enviado', 'error'])->default('pendiente');
                $table->unsignedTinyInteger('reintentos')->default(0);
                $table->string('ultimo_error', 500)->nullable();
                $table->dateTime('next_retry_at')->nullable();
                $table->dateTime('sent_at')->nullable();
                $table->dateTime('created_at')->useCurrent();
                $table->dateTime('updated_at')->useCurrent()->useCurrentOnUpdate();
                $table->index(['notificacion_id'], 'fk_notificacion_queue_notificacion');
                $table->index(['canal', 'estado'], 'idx_notificacion_queue_canal_estado');
                $table->index(['estado', 'next_retry_at'], 'idx_notificacion_queue_estado_retry');
                $table->foreign('notificacion_id', 'fk_notificacion_queue_notificacion')
                    ->references('id')
                    ->on('notificaciones')
                    ->cascadeOnDelete()
                    ->restrictOnUpdate();
            });
        }
    }
};
