<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('asset_types', 'created_by')) {
            return; // ya existe, nada que hacer
        }

        Schema::table('asset_types', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by')->nullable()->after('active');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('asset_types', function (Blueprint $table) {
            if (Schema::hasColumn('asset_types', 'created_by')) {
                $table->dropForeign(['created_by']);
                $table->dropColumn('created_by');
            }
        });
    }
};
