<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            if (!Schema::hasColumn('assets', 'domain_user')) {
                // Usuario de dominio asignado al equipo (ej: jgarcia, mlopez)
                $table->string('domain_user', 100)->nullable()->after('hostname');
            }
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            if (Schema::hasColumn('assets', 'domain_user')) {
                $table->dropColumn('domain_user');
            }
        });
    }
};

