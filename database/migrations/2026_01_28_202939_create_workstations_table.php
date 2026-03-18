<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('workstations', function (Blueprint $table) {
            $table->id();

            // Nombre del puesto (Ej: Contabilidad-01)
            $table->string('name');

            // Sucursal
            $table->foreignId('branch_id')
                  ->constrained()
                  ->cascadeOnUpdate();

            // Responsable (Jefe)
            $table->foreignId('responsible_id')
                  ->constrained('collaborators')
                  ->cascadeOnUpdate();

            $table->boolean('active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workstations');
    }
};