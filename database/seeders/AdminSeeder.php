<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AdminSeeder extends Seeder
{
    public function run(): void
    {

            // Crear rol Admin
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);

             //  Crear o recuperar el usuario admin
        $admin = User::updateOrCreate(
              ['username' => 'admin'], // Busca por username
              [
                'name' => 'Administrador',
                'email' => 'admin@inventario.com',
                'password' => bcrypt('admin123'), // Contraseña
                'email_verified_at' => now(),
            ]
        );
        
        // 3. Asignar rol Admin
        $admin->assignRole($adminRole);
        
        // 4. Mensaje simple
        $this->command->info('✅ Admin creado:');
        $this->command->info('   Usuario: admin');
        $this->command->info('   Contraseña: admin123');
    }
}


/*
        // Crear usuario administrador
        $admin = User::firstOrCreate(
            ['username' => 'admin'], // evita duplicados
            [
                'name' => 'Administrador',
                'email' => 'admin@empresa.com', // opcional
                'password' => Hash::make('admin123'),
            ]
        );

      // Asignar rol Admin
        $role = Role::firstOrCreate(['name' => 'Admin']);
        $admin->assignRole($role);
    }
}
*/