<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collaborators', function (Blueprint $table) {
            $table->id();

            // Datos personales
            $table->string('full_name');
            $table->string('document')->unique();
            $table->string('email')->nullable();

            // Informacion laboral
            $table->string('position')->nullable();
            $table->string('area')->nullable();

            // Relacion con sucursal
            $table->foreignId('branch_id')
                  ->constrained('branches')
                  ->cascadeOnUpdate()
                  ->restrictOnDelete();

            // Estado del colaborador
            $table->boolean('active')->default(true);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collaborators');
    }
};
