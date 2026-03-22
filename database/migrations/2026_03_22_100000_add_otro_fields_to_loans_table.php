<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            // Categoría del préstamo: TI | OTRO
            $table->string('category', 10)->default('TI')->after('id');

            // Tipo de destino: collaborator | branch
            $table->string('destination_type', 20)->default('collaborator')->after('collaborator_id');

            // Sucursal destino (para préstamos entre sucursales en OTRO)
            $table->foreignId('destination_branch_id')
                ->nullable()
                ->after('destination_type')
                ->constrained('branches')
                ->nullOnDelete();

            // Hacer collaborator_id nullable (los préstamos a sucursal no tienen colaborador)
            $table->foreignId('collaborator_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('loans', function (Blueprint $table) {
            $table->dropForeign(['destination_branch_id']);
            $table->dropColumn(['category', 'destination_type', 'destination_branch_id']);

            // Revertir collaborator_id a NOT NULL (solo si no hay nulls)
            $table->foreignId('collaborator_id')->nullable(false)->change();
        });
    }
};
