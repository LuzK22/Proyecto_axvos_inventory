<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /*
        |----------------------------------------------------------------------
        | actas
        | Registro de cada acta generada por una asignación.
        | Se genera automáticamente al crear/modificar una asignación.
        |----------------------------------------------------------------------
        */
        Schema::create('actas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('assignment_id')->constrained()->cascadeOnDelete();
            $table->string('acta_number')->unique();        // ACTA-2026-00001
            $table->string('acta_type')->default('entrega'); // entrega | devolucion | reemplazo

            // Estado del flujo de firmas
            $table->string('status')->default('borrador');
            // borrador | enviada | firmada_colaborador | firmada_responsable | completada | anulada

            // PDFs generados
            $table->string('pdf_path')->nullable();         // PDF final firmado

            // Quién genera el acta
            $table->foreignId('generated_by')->constrained('users');

            $table->text('notes')->nullable();
            $table->timestamp('sent_at')->nullable();       // cuándo se envió para firmar
            $table->timestamp('completed_at')->nullable();  // cuando ambas firmas están completas
            $table->timestamps();
        });

        /*
        |----------------------------------------------------------------------
        | acta_signatures
        | Firma individual por firmante. Cada acta tiene 2 firmas mínimo:
        | una del colaborador/receptor y otra del responsable del sistema.
        |----------------------------------------------------------------------
        */
        Schema::create('acta_signatures', function (Blueprint $table) {
            $table->id();
            $table->foreignId('acta_id')->constrained()->cascadeOnDelete();

            // Quién debe firmar
            $table->string('signer_role');  // collaborator | responsible | manager
            $table->string('signer_name');
            $table->string('signer_email')->nullable();
            $table->foreignId('signer_user_id')->nullable()->constrained('users')->nullOnDelete();

            // Token para firma por email (no requiere login)
            $table->string('token', 64)->unique()->nullable();
            $table->timestamp('token_expires_at')->nullable();

            // Firma realizada
            $table->timestamp('signed_at')->nullable();
            $table->string('signature_type')->nullable();   // drawn | image
            $table->longText('signature_data')->nullable(); // base64 PNG
            $table->string('signed_ip')->nullable();

            $table->timestamps();
        });

        /*
        |----------------------------------------------------------------------
        | acta_excel_templates
        | Plantillas Excel personalizadas por empresa.
        | Las empresas suben su formato .xlsx y mapean los campos.
        |----------------------------------------------------------------------
        */
        Schema::create('acta_excel_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');                         // "Formato Acta Entrega v3"
            $table->string('file_path');                    // storage/acta-templates/empresa_acta.xlsx
            $table->string('acta_type')->default('entrega'); // entrega | devolucion
            $table->boolean('active')->default(false);      // solo una activa a la vez
            $table->foreignId('uploaded_by')->constrained('users');
            $table->timestamps();
        });

        /*
        |----------------------------------------------------------------------
        | acta_excel_template_fields
        | Mapeo campo → celda Excel. Define en qué celda va cada dato.
        |----------------------------------------------------------------------
        */
        Schema::create('acta_excel_template_fields', function (Blueprint $table) {
            $table->id();
            $table->foreignId('acta_excel_template_id')
                  ->constrained()->cascadeOnDelete();
            $table->string('field_key');     // collaborator_name, asset_code, date, etc.
            $table->string('field_label');   // Nombre del colaborador, Código activo...
            $table->string('cell_ref');      // B5, D12, A{row} (para iterables)
            $table->boolean('is_iterable')->default(false); // ¿se repite por activo?
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('acta_excel_template_fields');
        Schema::dropIfExists('acta_excel_templates');
        Schema::dropIfExists('acta_signatures');
        Schema::dropIfExists('actas');
    }
};
