<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Branch;
use App\Models\Status;
use App\Models\Category;
use App\Models\AssetType;

class InitialDataSeeder extends Seeder
{
    /**
     * Datos iniciales del sistema AXVOS Inventory
     * Sucursales, estados, categorías y tipos de activo de ejemplo
     */
    public function run(): void
    {
        // ── 1. Sucursal principal ──────────────────────────────────────
        $branch = Branch::firstOrCreate(
            ['name' => 'Sede Principal'],
            [
                'city'    => 'Bogotá',
                'address' => 'Calle 1 # 1-01',
                'active'  => true,
            ]
        );

        // ── 2. Estados de activo ───────────────────────────────────────
        $statuses = [
            ['name' => 'Disponible',    'color' => 'success'],
            ['name' => 'Asignado',      'color' => 'primary'],
            ['name' => 'En Bodega',     'color' => 'secondary'],
            ['name' => 'Préstamo',      'color' => 'warning'],
            ['name' => 'Mantenimiento', 'color' => 'warning'],
            ['name' => 'En Garantía',   'color' => 'info'],
            ['name' => 'Baja',          'color' => 'danger'],
            ['name' => 'Alquilado',     'color' => 'light'],
            ['name' => 'Leasing',       'color' => 'light'],
        ];

        foreach ($statuses as $s) {
            Status::firstOrCreate(['name' => $s['name']], ['color' => $s['color'] ?? 'secondary']);
        }

        // ── 3. Categorías de activo ────────────────────────────────────
        $categories = [
            'Equipo de Cómputo / TI',
            'Equipo de Telecomunicación',
            'Maquinaria y Equipo',
            'Muebles y Enseres',
            'Activos Intangibles',
            'Otros Activos',
        ];

        foreach ($categories as $cat) {
            Category::firstOrCreate(['name' => $cat]);
        }

        // ── 4. Tipos de activo TI (ejemplos) ──────────────────────────
        $tiTypes = [
            ['name' => 'Portátil',       'code' => 'POR', 'category' => 'TI'],
            ['name' => 'Monitor',        'code' => 'MON', 'category' => 'TI'],
            ['name' => 'Mouse',          'code' => 'MOU', 'category' => 'TI'],
            ['name' => 'Teclado',        'code' => 'TEC', 'category' => 'TI'],
            ['name' => 'Diadema',        'code' => 'DIA', 'category' => 'TI'],
            ['name' => 'Impresora',      'code' => 'IMP', 'category' => 'TI'],
            ['name' => 'UPS',            'code' => 'UPS', 'category' => 'TI'],
            ['name' => 'Cargador',       'code' => 'CAR', 'category' => 'TI'],
            ['name' => 'Tablet',         'code' => 'TAB', 'category' => 'TI'],
            ['name' => 'Celular',        'code' => 'CEL', 'category' => 'TI'],
            ['name' => 'Disco Externo',  'code' => 'DSK', 'category' => 'TI'],
            ['name' => 'Switch/Router',  'code' => 'NET', 'category' => 'TI'],
        ];

        foreach ($tiTypes as $t) {
            AssetType::firstOrCreate(
                ['code' => $t['code'], 'category' => $t['category']],
                [
                    'name'   => $t['name'],
                    'prefix' => $t['category'] . '-' . $t['code'],
                    'active' => true,
                ]
            );
        }

        // ── 5. Tipos de activo OTRO (ejemplos) ────────────────────────
        $otroTypes = [
            ['name' => 'Silla',           'code' => 'SIL', 'category' => 'OTRO'],
            ['name' => 'Mesa/Escritorio', 'code' => 'ESC', 'category' => 'OTRO'],
            ['name' => 'Televisor',       'code' => 'TEL', 'category' => 'OTRO'],
            ['name' => 'Archivador',      'code' => 'ARC', 'category' => 'OTRO'],
            ['name' => 'Proyector',       'code' => 'PRY', 'category' => 'OTRO'],
            ['name' => 'Teléfono Fijo',   'code' => 'TLF', 'category' => 'OTRO'],
            ['name' => 'Aire Acondicionado', 'code' => 'AAC', 'category' => 'OTRO'],
        ];

        foreach ($otroTypes as $t) {
            AssetType::firstOrCreate(
                ['code' => $t['code'], 'category' => $t['category']],
                [
                    'name'   => $t['name'],
                    'prefix' => $t['category'] . '-' . $t['code'],
                    'active' => true,
                ]
            );
        }

        $this->command->info('✅ Datos iniciales creados:');
        $this->command->info('   • ' . count($statuses)  . ' estados de activo');
        $this->command->info('   • ' . count($categories) . ' categorías');
        $this->command->info('   • ' . count($tiTypes)    . ' tipos de activo TI');
        $this->command->info('   • ' . count($otroTypes)  . ' tipos de activo OTRO');
        $this->command->info('   • 1 sucursal principal');
    }
}
