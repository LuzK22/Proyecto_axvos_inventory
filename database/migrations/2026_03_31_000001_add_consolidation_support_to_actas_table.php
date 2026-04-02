<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * FASE 2 — Soporte de agrupación multi-asignación en actas.
 *
 * Cambios:
 * 1. actas.assignment_id → nullable (las actas consolidadas no tienen una sola asignación raíz)
 * 2. actas.collaborator_id → FK nullable (destinatario directo del acta consolidada)
 * 3. actas.destination_type → string nullable (collaborator | area | pool)
 * 4. Nueva tabla acta_assignments (pivot N:M actas ↔ assignments)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Hacer assignment_id nullable en actas ──────────────────────
        Schema::table('actas', function (Blueprint $table) {
            // Primero eliminamos la FK existente
            $table->dropForeign(['assignment_id']);
            // La reponemos nullable con FK que acepta null
            $table->foreignId('assignment_id')
                  ->nullable()
                  ->change()
                  ->constrained('assignments')
                  ->nullOnDelete();
        });

        // ── 2. Agregar collaborator_id y destination_type a actas ─────────
        Schema::table('actas', function (Blueprint $table) {
            $table->foreignId('collaborator_id')
                  ->nullable()
                  ->after('assignment_id')
                  ->constrained('collaborators')
                  ->nullOnDelete();

            $table->string('destination_type', 20)
                  ->nullable()
                  ->after('collaborator_id')
                  ->comment('collaborator | area | pool — solo en actas consolidadas');
        });

        // ── 3. Tabla pivot acta_assignments ───────────────────────────────
        Schema::create('acta_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('acta_id')
                  ->constrained('actas')
                  ->cascadeOnDelete();
            $table->foreignId('assignment_id')
                  ->constrained('assignments')
                  ->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['acta_id', 'assignment_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acta_assignments');

        Schema::table('actas', function (Blueprint $table) {
            $table->dropColumn(['collaborator_id', 'destination_type']);
        });

        Schema::table('actas', function (Blueprint $table) {
            $table->dropForeign(['assignment_id']);
            $table->foreignId('assignment_id')
                  ->change()
                  ->constrained('assignments')
                  ->cascadeOnDelete();
        });
    }
};
