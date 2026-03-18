<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            // Código de activo fijo (placa/sticker contable)
            if (!Schema::hasColumn('assets', 'fixed_asset_code')) {
                $table->string('fixed_asset_code')->nullable()->after('serial');
            }
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            if (Schema::hasColumn('assets', 'fixed_asset_code')) {
                $table->dropColumn('fixed_asset_code');
            }
        });
    }
};
