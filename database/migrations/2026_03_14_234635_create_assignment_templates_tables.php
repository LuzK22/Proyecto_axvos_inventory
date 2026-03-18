<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |----------------------------------------------------------------------
        | assignment_types
        | Define qué modelo de asignación usa cada empresa.
        | Ej: "Por Modalidad de Trabajo", "Por Cargo", "Por Área", "Libre"
        |----------------------------------------------------------------------
        */
        Schema::create('assignment_types', function (Blueprint $table) {
            $table->id();
            $table->string('name');                          // "Por Modalidad de Trabajo"
            $table->string('trigger_field')->nullable();     // campo que activa la plantilla: "modalidad","cargo","area"
            $table->string('trigger_label')->nullable();     // etiqueta del campo en el formulario
            $table->string('target')->default('person');     // person | area | project | pool
            $table->boolean('requires_return')->default(true);
            $table->boolean('active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        /*
        |----------------------------------------------------------------------
        | assignment_templates
        | Plantillas de asignación. Cada empresa crea las suyas.
        | Ej: "Trabajo Remoto", "Trabajo Presencial", "Gerente", "Operario"
        |----------------------------------------------------------------------
        */
        Schema::create('assignment_templates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_type_id')->constrained()->cascadeOnDelete();
            $table->string('name');                          // "Trabajo Remoto"
            $table->text('description')->nullable();
            $table->string('trigger_value')->nullable();     // "remoto" | "presencial" | "gerente"
            $table->boolean('active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        /*
        |----------------------------------------------------------------------
        | assignment_template_items
        | Qué activos van en cada plantilla y a quién se asignan.
        |----------------------------------------------------------------------
        */
        Schema::create('assignment_template_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_template_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_type_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('quantity')->default(1);
            $table->string('goes_to')->default('assignee'); // assignee | area | jefe | pool
            $table->text('notes')->nullable();              // "Queda en puesto asignado al jefe"
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignment_template_items');
        Schema::dropIfExists('assignment_templates');
        Schema::dropIfExists('assignment_types');
    }
};
