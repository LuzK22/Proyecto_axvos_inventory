<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('acta_excel_templates', function (Blueprint $table) {
            if (!Schema::hasColumn('acta_excel_templates', 'template_type')) {
                $table->string('template_type', 10)->default('xlsx')->after('file_path');
            }
        });
    }

    public function down(): void
    {
        Schema::table('acta_excel_templates', function (Blueprint $table) {
            if (Schema::hasColumn('acta_excel_templates', 'template_type')) {
                $table->dropColumn('template_type');
            }
        });
    }
};
