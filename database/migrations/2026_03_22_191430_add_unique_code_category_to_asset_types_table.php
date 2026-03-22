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
        Schema::table('asset_types', function (Blueprint $table) {
            // Evita dos tipos con el mismo prefijo de código dentro de la misma categoría
            // Ej: dos tipos TI con prefix="POR" generarían colisión en internal_code
            $table->unique(['prefix', 'category'], 'asset_types_prefix_category_unique');
        });
    }

    public function down(): void
    {
        Schema::table('asset_types', function (Blueprint $table) {
            $table->dropUnique('asset_types_prefix_category_unique');
        });
    }
};
