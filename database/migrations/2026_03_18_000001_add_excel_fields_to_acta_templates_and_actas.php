<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acta_excel_templates', function (Blueprint $table) {
            // TI | OTRO | ALL (por instalación single-empresa, pero configurable)
            $table->string('asset_category', 10)->default('ALL')->after('acta_type');
            // Fila inicial donde empieza la tabla de activos (para campos iterables A{row}, B{row}, etc.)
            $table->unsignedInteger('assets_start_row')->nullable()->after('active');
        });

        Schema::table('actas', function (Blueprint $table) {
            // Excel generado desde plantilla (borrador) y Excel editado/subido (final)
            $table->string('xlsx_draft_path')->nullable()->after('pdf_path');
            $table->string('xlsx_final_path')->nullable()->after('xlsx_draft_path');
        });
    }

    public function down(): void
    {
        Schema::table('acta_excel_templates', function (Blueprint $table) {
            $table->dropColumn(['asset_category', 'assets_start_row']);
        });

        Schema::table('actas', function (Blueprint $table) {
            $table->dropColumn(['xlsx_draft_path', 'xlsx_final_path']);
        });
    }
};

