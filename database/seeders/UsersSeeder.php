<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    /**
     * Crea un usuario demo por cada rol del sistema.
     * Contraseña por defecto: Password1!
     * CAMBIAR en producción.
     */
    public function run(): void
    {
        $users = [
            [
                'name'     => 'Auxiliar TI',
                'email'    => 'auxiliarti@axvos.local',
                'username' => 'auxiliarti',
                'role'     => 'Auxiliar_TI',
            ],
            [
                'name'     => 'Gestor Activos',
                'email'    => 'gestoractivos@axvos.local',
                'username' => 'gestoractivos',
                'role'     => 'Gestor_Activos',
            ],
            [
                'name'     => 'Gestor General',
                'email'    => 'gestorgeneral@axvos.local',
                'username' => 'gestorgeneral',
                'role'     => 'Gestor_General',
            ],
            [
                'name'     => 'Aprobador',
                'email'    => 'aprobador@axvos.local',
                'username' => 'aprobador',
                'role'     => 'Aprobador',
            ],
            [
                'name'     => 'Auditor',
                'email'    => 'auditor@axvos.local',
                'username' => 'auditor',
                'role'     => 'Auditor',
            ],
        ];

        foreach ($users as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name'              => $data['name'],
                    'username'          => $data['username'],
                    'password'          => Hash::make('Password1!'),
                    'email_verified_at' => now(),
                ]
            );

            // Asigna el rol (solo si no lo tiene ya)
            if (!$user->hasRole($data['role'])) {
                $user->assignRole($data['role']);
            }
        }
    }
}
