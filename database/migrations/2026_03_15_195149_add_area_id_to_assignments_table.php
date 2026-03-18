<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            // Para Otros Activos: asignar a área en vez de colaborador
            // collaborator_id se vuelve nullable (ya puede serlo, pero lo confirmamos)
            $table->foreignId('area_id')->nullable()->after('collaborator_id')
                  ->constrained('areas')->nullOnDelete();
            // Categoría del assignment: TI | OTRO
            $table->string('asset_category', 10)->default('TI')->after('area_id');
        });
    }

    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropForeign(['area_id']);
            $table->dropColumn(['area_id', 'asset_category']);
        });
    }
};
