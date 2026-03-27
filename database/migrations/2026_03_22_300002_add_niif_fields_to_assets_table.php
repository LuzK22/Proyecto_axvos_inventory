<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NIIF / NIC 16 — Propiedades, Planta y Equipo.
 * NIC 38 — Activos Intangibles.
 *
 * Agrega los campos contables necesarios para calcular depreciación,
 * valor en libros y realizar el cruce con el software contable (Siigo, SAP, World Office).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            // Vida útil en años según tabla DIAN / política contable
            if (!Schema::hasColumn('assets', 'useful_life_years')) {
                $table->unsignedTinyInteger('useful_life_years')->nullable()
                      ->after('purchase_date')
                      ->comment('Vida útil estimada en años (NIIF NIC 16)');
            }

            // Valor residual o de salvamento al final de la vida útil
            if (!Schema::hasColumn('assets', 'residual_value')) {
                $table->decimal('residual_value', 14, 2)->nullable()->default(0)
                      ->after('useful_life_years')
                      ->comment('Valor de salvamento al vencimiento de la vida útil');
            }

            // Método de depreciación (NIIF permite cualquiera coherente con uso)
            if (!Schema::hasColumn('assets', 'depreciation_method')) {
                $table->enum('depreciation_method', [
                    'linea_recta',
                    'saldo_decreciente',
                    'unidades_produccion',
                    'no_deprecia',           // terrenos, arte
                ])->nullable()->default('linea_recta')
                  ->after('residual_value');
            }

            // Fecha desde la que inicia el cómputo de depreciación
            if (!Schema::hasColumn('assets', 'depreciation_start_date')) {
                $table->date('depreciation_start_date')->nullable()
                      ->after('depreciation_method')
                      ->comment('Fecha de entrada en servicio (inicio depreciación)');
            }

            // Código del Plan Único de Cuentas — PUC Colombia
            if (!Schema::hasColumn('assets', 'account_code')) {
                $table->string('account_code', 20)->nullable()
                      ->after('depreciation_start_date')
                      ->comment('Código PUC Colombia (ej. 1524050501)');
            }

            // Índice para búsquedas contables
            $table->index('account_code');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropIndex(['account_code']);
            $table->dropColumn([
                'useful_life_years',
                'residual_value',
                'depreciation_method',
                'depreciation_start_date',
                'account_code',
            ]);
        });
    }
};
