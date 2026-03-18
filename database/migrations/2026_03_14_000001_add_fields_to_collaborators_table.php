<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collaborators', function (Blueprint $table) {
            $table->string('phone')->nullable()->after('email');
            $table->enum('modalidad_trabajo', ['presencial', 'remoto', 'hibrido'])
                  ->default('presencial')
                  ->after('area');
        });
    }

    public function down(): void
    {
        Schema::table('collaborators', function (Blueprint $table) {
            $table->dropColumn(['phone', 'modalidad_trabajo']);
        });
    }
};
