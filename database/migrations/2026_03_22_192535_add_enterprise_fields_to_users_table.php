<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Sucursal del usuario para row-level security
            $table->unsignedBigInteger('branch_id')->nullable()->after('username');
            $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();

            // 2FA — TOTP
            $table->text('two_factor_secret')->nullable()->after('branch_id');
            $table->text('two_factor_recovery_codes')->nullable()->after('two_factor_secret');
            $table->timestamp('two_factor_confirmed_at')->nullable()->after('two_factor_recovery_codes');

            // Política de contraseñas — forzar cambio periódico
            $table->timestamp('password_changed_at')->nullable()->after('two_factor_confirmed_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropColumn([
                'branch_id',
                'two_factor_secret',
                'two_factor_recovery_codes',
                'two_factor_confirmed_at',
                'password_changed_at',
            ]);
        });
    }
};
