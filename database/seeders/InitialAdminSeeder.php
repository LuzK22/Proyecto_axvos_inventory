 <?php
/*
namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use App\Models\Category;

class InitialAdminSeeder extends Seeder
{
    public function run(): void
    {
        // 1️⃣ Crear rol Admin
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);

        // 2️⃣ Crear usuario Admin
        $admin = User::firstOrCreate(
            ['email' => 'lk11opance@hotmail.com'],
            [
                'name' => 'Administrador',
                'password' => bcrypt('12345'),
            ]
        );

        $admin->assignRole($adminRole);

        // 3️⃣ Crear categorías principales
        $tech = Category::firstOrCreate(['name' => 'Tecnología']);
        $other = Category::firstOrCreate(['name' => 'Otros Activos']);

        // 4️⃣ Subcategorías Tecnología
        Category::firstOrCreate(['name' => 'Hardware', 'parent_id' => $tech->id]);
        Category::firstOrCreate(['name' => 'Software', 'parent_id' => $tech->id]);

        // 5️⃣ Subcategorías Otros Activos
        Category::firstOrCreate(['name' => 'Muebles', 'parent_id' => $other->id]);
        Category::firstOrCreate(['name' => 'Equipos', 'parent_id' => $other->id]);

        $this->command->info('✅ Admin y categorías creadas correctamente.');
    }
} -->
/*