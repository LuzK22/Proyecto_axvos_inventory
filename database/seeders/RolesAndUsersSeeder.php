<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesAndUsersSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | ADMIN → TODOS LOS PERMISOS
        |--------------------------------------------------------------------------
        */
        $admin = Role::firstOrCreate(['name' => 'Admin']);
        $admin->syncPermissions(Permission::all());

        /*
        |--------------------------------------------------------------------------
        | AUXILIAR TI
        |--------------------------------------------------------------------------
        */
        Role::findByName('Auxiliar_TI')->givePermissionTo([
            'tech.assets.view',
            'tech.assets.create',
            'tech.assets.assign',

             // Bajas
            'tech.assets.disposal.request',

             // Reportes / historial
            'tech.history.view',
            'tech.reports.view',

            // SOLO SOLICITA
           'assets.request.edit',
           'assets.request.delete',

            // Colaboradores
            'collaborators.view',
            'collaborators.create',
            'collaborators.edit',


        ]);

        /*
        |--------------------------------------------------------------------------
        | GESTOR OTROS ACTIVOS
        |--------------------------------------------------------------------------
        */
        Role::findByName('Auxiliar_Activos')->givePermissionTo([
            'assets.view',
            'assets.create',
            'assets.assign',
            
              //Solicitudes (clave)
            'assets.request.edit',
           'assets.request.delete',

            'assets.disposal.view',

            // Bajas
            'assets.disposal.request',
            'assets.history.view',
            'assets.reports.view',

            // Colaboradores
            'collaborators.view',
            'collaborators.create',
            'collaborators.edit',
        ]);

        /*
        |--------------------------------------------------------------------------
        | GESTOR GENERAL
        |--------------------------------------------------------------------------
        | Gestiona TODO pero NO aprueba bajas
        |--------------------------------------------------------------------------
        */
        Role::findByName('Gestor_General')->givePermissionTo([
            // TI
            'tech.assets.view',
            'tech.assets.create',
            'tech.assets.assign',
            'tech.reports.view',


            // Otros activos
            'assets.view',
            'assets.create',
            'assets.assign',
            'assets.history.view',
            'tech.history.view',
            'assets.reports.view',

            // SOLO SOLICITA
           'assets.request.edit',
           'assets.request.delete',


            'collaborators.view',
            'collaborators.create',
            'collaborators.edit',
            'assets.disposal.view',
            'assets.disposal.request',
           



            // Reportes
            'reports.view',
            'reports.global',
        ]);

        /*
        |--------------------------------------------------------------------------
        | APROBADOR
        |--------------------------------------------------------------------------
        */
        Role::findByName('Aprobador')->givePermissionTo([
             // Aprobar cambios
            'assets.approve.edit',
            'assets.approve.delete',

            // Aprobar bajas
            'tech.assets.disposal.approve',
            'assets.disposal.approve',
        ]);

        /*
        |--------------------------------------------------------------------------
        | AUDITOR
        |--------------------------------------------------------------------------
        */
        Role::findByName('Auditor')->givePermissionTo([
            'reports.view',
            'reports.global',
            'tech.history.view',
            'assets.history.view',
            'tech.reports.view',
            'assets.reports.view',

        ]);
    }
}
