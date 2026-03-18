<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            // Valor de compra
            if (!Schema::hasColumn('assets', 'purchase_value')) {
                $table->decimal('purchase_value', 14, 2)->nullable()->after('fixed_asset_code');
            }
            // Fecha de compra
            if (!Schema::hasColumn('assets', 'purchase_date')) {
                $table->date('purchase_date')->nullable()->after('purchase_value');
            }
            // Proveedor (para LEASING / ALQUILADO)
            if (!Schema::hasColumn('assets', 'provider_name')) {
                $table->string('provider_name', 200)->nullable()->after('purchase_date');
            }
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn(['purchase_value', 'purchase_date', 'provider_name']);
        });
    }
};
