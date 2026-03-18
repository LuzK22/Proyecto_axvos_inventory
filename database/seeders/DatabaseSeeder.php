<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PermissionsSeeder::class,   // 1. Crea permisos
            RolesSeeder::class,         // 2. Crea roles y asigna permisos
            AdminSeeder::class,         // 3. Crea usuario admin
            InitialDataSeeder::class,   // 4. Sucursales, estados, tipos de activo
        ]);
    }
}
