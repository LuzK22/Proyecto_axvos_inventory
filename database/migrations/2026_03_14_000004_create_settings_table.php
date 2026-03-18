<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('group')->default('general'); // general, company, documents, mail
            $table->string('label')->nullable();
            $table->string('type')->default('text'); // text, textarea, image, boolean, select
            $table->timestamps();
        });

        // Insertar configuraciones por defecto
        DB::table('settings')->insert([
            // ── Empresa ─────────────────────────────────────────────────
            ['key' => 'company_name',     'value' => 'Mi Empresa S.A.S',       'group' => 'company', 'label' => 'Nombre de la empresa', 'type' => 'text',     'created_at' => now(), 'updated_at' => now()],
            ['key' => 'company_nit',      'value' => '',                        'group' => 'company', 'label' => 'NIT / RUT',            'type' => 'text',     'created_at' => now(), 'updated_at' => now()],
            ['key' => 'company_address',  'value' => '',                        'group' => 'company', 'label' => 'Dirección',             'type' => 'text',     'created_at' => now(), 'updated_at' => now()],
            ['key' => 'company_city',     'value' => '',                        'group' => 'company', 'label' => 'Ciudad',                'type' => 'text',     'created_at' => now(), 'updated_at' => now()],
            ['key' => 'company_phone',    'value' => '',                        'group' => 'company', 'label' => 'Teléfono',              'type' => 'text',     'created_at' => now(), 'updated_at' => now()],
            ['key' => 'company_email',    'value' => '',                        'group' => 'company', 'label' => 'Correo empresa',        'type' => 'text',     'created_at' => now(), 'updated_at' => now()],
            ['key' => 'company_logo',     'value' => null,                      'group' => 'company', 'label' => 'Logo empresa',          'type' => 'image',    'created_at' => now(), 'updated_at' => now()],
            // ── Sistema ─────────────────────────────────────────────────
            ['key' => 'system_name',      'value' => 'AXVOS Inventory',         'group' => 'general', 'label' => 'Nombre del sistema',    'type' => 'text',     'created_at' => now(), 'updated_at' => now()],
            ['key' => 'system_slogan',    'value' => 'Conecta. Controla. Traza.','group' => 'general', 'label' => 'Eslogan',               'type' => 'text',     'created_at' => now(), 'updated_at' => now()],
            // ── Documentos / Actas ───────────────────────────────────────
            ['key' => 'acta_header_text',  'value' => 'ACTA DE ENTREGA DE ACTIVOS', 'group' => 'documents', 'label' => 'Título acta de entrega', 'type' => 'text', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'acta_footer_text',  'value' => 'Documento generado por AXVOS Inventory',  'group' => 'documents', 'label' => 'Pie de página actas', 'type' => 'textarea', 'created_at' => now(), 'updated_at' => now()],
            ['key' => 'acta_responsible_label', 'value' => 'Auxiliar de Soporte TI', 'group' => 'documents', 'label' => 'Cargo de quien entrega', 'type' => 'text', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('settings');
    }
};
