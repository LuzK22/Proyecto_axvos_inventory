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
        // deletion_requests.resolved_by: nullOnDelete → restrictOnDelete
        // Preservar el rastro de quién aprobó/rechazó cada solicitud de baja
        Schema::table('deletion_requests', function (Blueprint $table) {
            $table->dropForeign(['resolved_by']);
            $table->foreign('resolved_by')
                  ->references('id')->on('users')
                  ->restrictOnDelete();
        });

        // loans.returned_by: nullOnDelete → restrictOnDelete
        // Preservar el rastro de quién procesó la devolución de cada préstamo
        Schema::table('loans', function (Blueprint $table) {
            $table->dropForeign(['returned_by']);
            $table->foreign('returned_by')
                  ->references('id')->on('users')
                  ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('deletion_requests', function (Blueprint $table) {
            $table->dropForeign(['resolved_by']);
            $table->foreign('resolved_by')
                  ->references('id')->on('users')
                  ->nullOnDelete();
        });

        Schema::table('loans', function (Blueprint $table) {
            $table->dropForeign(['returned_by']);
            $table->foreign('returned_by')
                  ->references('id')->on('users')
                  ->nullOnDelete();
        });
    }
};
