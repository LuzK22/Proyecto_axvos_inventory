<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Ley 1581/2012 Colombia — Consentimiento de tratamiento de datos personales.
 *
 * Registra cuándo, desde qué IP y con qué versión de política cada usuario
 * aceptó el tratamiento de sus datos.  Nunca se borra (inmutable por ley).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('data_consents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // Versión de la política aceptada (ej. "1.0", "2.0")
            $table->string('policy_version', 10)->default('1.0');

            // Tipo de consentimiento
            $table->enum('type', ['privacy_policy', 'terms_of_service', 'data_treatment'])
                  ->default('data_treatment');

            // Marca de tiempo en que se aceptó
            $table->timestamp('accepted_at')->useCurrent();

            // Datos de trazabilidad (ISO 27001)
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            // Si fue revocado (derecho al olvido Art. 15 Ley 1581)
            $table->timestamp('revoked_at')->nullable();
            $table->text('revocation_reason')->nullable();

            $table->index(['user_id', 'policy_version', 'type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('data_consents');
    }
};
