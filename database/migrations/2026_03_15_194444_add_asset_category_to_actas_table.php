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
        Schema::table('actas', function (Blueprint $table) {
            // 'TI' = activos tecnológicos | 'OTRO' = otros activos
            $table->string('asset_category', 10)->default('TI')->after('acta_type');
        });
    }

    public function down(): void
    {
        Schema::table('actas', function (Blueprint $table) {
            $table->dropColumn('asset_category');
        });
    }
};
