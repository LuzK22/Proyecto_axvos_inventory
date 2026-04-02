<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    // Mapa nombre → subcategoría para tipos TI
    private const SUBCATS = [
        'Portátil'      => 'Portátiles y Móviles',
        'Tablet'        => 'Portátiles y Móviles',
        'Celular'       => 'Portátiles y Móviles',
        'Monitor'       => 'Pantallas',
        'Teclado'       => 'Periféricos',
        'Mouse'         => 'Periféricos',
        'Diadema'       => 'Periféricos',
        'Hub USB'       => 'Periféricos',
        'Cargador'      => 'Periféricos',
        'Impresora'     => 'Impresión',
        'Disco Externo' => 'Almacenamiento',
        'Switch/Router' => 'Red y Conectividad',
        'UPS'           => 'Energía',
    ];

    public function up(): void
    {
        foreach (self::SUBCATS as $name => $subcat) {
            \DB::table('asset_types')
                ->where('category', 'TI')
                ->where('name', $name)
                ->update(['subcategory' => $subcat]);
        }
    }

    public function down(): void
    {
        \DB::table('asset_types')
            ->where('category', 'TI')
            ->whereIn('name', array_keys(self::SUBCATS))
            ->update(['subcategory' => null]);
    }
};
