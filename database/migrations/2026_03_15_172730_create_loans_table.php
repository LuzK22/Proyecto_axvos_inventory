<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loans', function (Blueprint $table) {
            $table->id();

            // Activo prestado y a quién se le entregó
            $table->foreignId('asset_id')->constrained('assets')->restrictOnDelete();
            $table->foreignId('collaborator_id')->constrained('collaborators')->restrictOnDelete();

            // Fechas del préstamo
            $table->date('start_date');
            $table->date('end_date');                     // fecha pactada de devolución
            $table->datetime('returned_at')->nullable();  // null = todavía no devuelto

            // activo | vencido | devuelto
            $table->string('status', 20)->default('activo')->index();

            $table->text('notes')->nullable();

            // Quién registró y quién procesó la devolución
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('returned_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loans');
    }
};
