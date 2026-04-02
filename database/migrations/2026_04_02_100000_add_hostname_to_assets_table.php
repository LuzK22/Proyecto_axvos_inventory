<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            if (!Schema::hasColumn('assets', 'hostname')) {
                // Nombre del equipo asignado por la empresa (ej: LAPTOP-JUAN01, PC-RECEPCION)
                $table->string('hostname', 100)->nullable()->after('serial');
            }
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            if (Schema::hasColumn('assets', 'hostname')) {
                $table->dropColumn('hostname');
            }
        });
    }
};
