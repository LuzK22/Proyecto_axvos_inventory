<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deletion_requests', function (Blueprint $table) {
            $table->id();

            // Activo al que aplica
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();

            // Quien solicita
            $table->foreignId('requested_by')->constrained('users');

            // Quien resuelve (Aprobador)
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();

            // Estado: pending | approved | rejected
            $table->string('status')->default('pending');

            // Motivo de la solicitud
            $table->string('reason')->default('otro');
            // danado | obsoleto | perdido | venta | donacion | otro

            $table->text('notes')->nullable();         // Descripción del solicitante
            $table->text('rejection_notes')->nullable(); // Motivo de rechazo

            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deletion_requests');
    }
};
