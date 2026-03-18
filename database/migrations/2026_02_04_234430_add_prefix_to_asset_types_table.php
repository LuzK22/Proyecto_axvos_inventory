<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_types', function (Blueprint $table) {
            if (!Schema::hasColumn('asset_types', 'prefix')) {
                $table->string('prefix', 20)->after('code')->nullable();
            }
        });
        
        // Actualizar prefijos existentes
        DB::table('asset_types')->where('category', 'TI')->update([
            'prefix' => DB::raw("CONCAT('TI-', UPPER(code))")
        ]);
        
        DB::table('asset_types')->where('category', 'OTRO')->update([
            'prefix' => DB::raw("CONCAT('OTRO-', UPPER(code))")
        ]);
        
        // Hacer el campo no nullable después de poblarlo
        Schema::table('asset_types', function (Blueprint $table) {
            $table->string('prefix', 20)->nullable(false)->change();
        });
    }

    public function down(): void
    {
        Schema::table('asset_types', function (Blueprint $table) {
            $table->dropColumn('prefix');
        });
    }
};
