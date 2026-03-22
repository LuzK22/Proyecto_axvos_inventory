<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Migración: mejoras al módulo Otros Activos
 *
 * 1. asset_types  → agrega columna `subcategory` (solo OTRO, nullable)
 * 2. assignments  → agrega columna `destination_type` para los 4 destinos posibles
 *
 * Destinos soportados:
 *   - collaborator → activo va directamente al colaborador
 *   - jefe         → queda con el jefe/responsable de área (también es un colaborador)
 *   - area         → queda en espacio compartido de oficina
 *   - pool         → pool de uso rotativo, sin asignación fija
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Subcategoría para Tipos de Activo OTRO ──────────────────────────
        Schema::table('asset_types', function (Blueprint $table) {
            // Subcategoría opcional (ej: "Mobiliario", "Enseres", "Redes")
            // Solo aplica a categoría OTRO; para TI se deja null
            $table->string('subcategory')->nullable()->after('category');
        });

        // ── 2. Tipo de destino para Asignaciones ───────────────────────────────
        Schema::table('assignments', function (Blueprint $table) {
            // Destino de la asignación — reemplaza el toggle binario colaborador/área
            // Valores: collaborator | jefe | area | pool
            $table->string('destination_type')->default('collaborator')->after('area_id');
        });
    }

    public function down(): void
    {
        Schema::table('asset_types', function (Blueprint $table) {
            $table->dropColumn('subcategory');
        });

        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn('destination_type');
        });
    }
};
