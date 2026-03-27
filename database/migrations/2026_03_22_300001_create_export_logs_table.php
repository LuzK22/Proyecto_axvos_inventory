<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ISO 27001 — Control de exportaciones y acceso a datos masivos.
 *
 * Registra toda descarga de reportes/exportaciones: quién, qué, cuándo, desde dónde.
 * Permite detectar exfiltración de datos y auditar accesos masivos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('export_logs', function (Blueprint $table) {
            $table->id();

            // Quién exportó
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            // Qué entidad exportó (assets, collaborators, assignments, etc.)
            $table->string('entity_type', 60);

            // Formato de salida
            $table->enum('format', ['xlsx', 'pdf', 'csv', 'json'])->default('xlsx');

            // Filtros aplicados (JSON serializado para auditoría)
            $table->json('filters')->nullable();

            // Cantidad de registros exportados
            $table->unsignedInteger('rows_exported')->default(0);

            // Trazabilidad
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index(['user_id', 'created_at']);
            $table->index('entity_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('export_logs');
    }
};
