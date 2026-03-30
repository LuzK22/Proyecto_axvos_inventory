<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'default_signature_type')) {
                $table->string('default_signature_type', 20)->nullable()->after('locked_until');
            }
            if (!Schema::hasColumn('users', 'default_signature_data')) {
                $table->longText('default_signature_data')->nullable()->after('default_signature_type');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'default_signature_data')) {
                $table->dropColumn('default_signature_data');
            }
            if (Schema::hasColumn('users', 'default_signature_type')) {
                $table->dropColumn('default_signature_type');
            }
        });
    }
};

