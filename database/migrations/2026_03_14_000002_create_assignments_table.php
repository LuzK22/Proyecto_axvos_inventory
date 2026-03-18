<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('collaborator_id')
                  ->constrained('collaborators')
                  ->restrictOnDelete();

            $table->foreignId('assigned_by')
                  ->constrained('users')
                  ->restrictOnDelete();

            $table->date('assignment_date');

            // Snapshot de la modalidad al momento de asignar
            $table->string('work_modality')->nullable();

            $table->text('notes')->nullable();

            // 'activa' = al menos un activo aún asignado
            // 'devuelta' = todos los activos fueron devueltos
            $table->enum('status', ['activa', 'devuelta'])->default('activa');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
