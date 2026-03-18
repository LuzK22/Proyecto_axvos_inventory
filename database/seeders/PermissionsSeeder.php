<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class PermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $permissions = [

            /*
            |------------------------------------------------------------------
            | COLABORADORES
            |------------------------------------------------------------------
            */
            'collaborators.view',
            'collaborators.create',
            'collaborators.edit',

            /*
            |------------------------------------------------------------------
            | TIPOS DE ACTIVOS
            |------------------------------------------------------------------
            */
            'asset-types.view',
            'asset-types.create',
            'asset-types.edit',
            'asset-types.delete',

            'tech.types.view',
            'tech.types.create',
            'tech.types.edit',

            /*
            |------------------------------------------------------------------
            | ACTIVOS TI
            |------------------------------------------------------------------
            */
            'tech.assets.view',
            'tech.assets.create',
            'tech.assets.edit',
            'tech.assets.assign',

            // Bajas TI
            'tech.assets.disposal.view',
            'tech.assets.disposal.request',   // Auxiliar TI / Gestor General: solicita
            'tech.assets.disposal.approve',   // Aprobador: aprueba o rechaza

            'tech.history.view',
            'tech.reports.view',

            /*
            |------------------------------------------------------------------
            | OTROS ACTIVOS
            |------------------------------------------------------------------
            */
            'assets.view',
            'assets.create',
            'assets.edit',
            'assets.assign',

            // Bajas Otros Activos
            'assets.disposal.view',
            'assets.disposal.request',        // Gestor Activos / Gestor General: solicita
            'assets.disposal.approve',        // Aprobador: aprueba o rechaza

            'assets.reports.view',
            'assets.history.view',

            /*
            |------------------------------------------------------------------
            | SOLICITUDES Y APROBACIONES DE CAMBIOS
            |------------------------------------------------------------------
            */
            'assets.request.edit',
            'assets.request.delete',
            'assets.approve.edit',
            'assets.approve.delete',

            /*
            |------------------------------------------------------------------
            | APROBADOR — controles exclusivos
            |------------------------------------------------------------------
            */
            'loans.approve',               // Aprobar prestamos de larga duracion
            'assets.maintenance.approve',  // Aprobar salida para mantenimiento externo
            'audit.discrepancy.resolve',   // Resolver discrepancias marcadas por Auditor
            'audit.adjustment.approve',    // Aprobar ajustes de inventario fisico vs sistema

            /*
            |------------------------------------------------------------------
            | AUDITORIA
            |------------------------------------------------------------------
            */
            'audit.view',     // Dashboard de auditoria global consolidada
            'audit.export',   // Exportar reportes Excel

            /*
            |------------------------------------------------------------------
            | ADMINISTRACION
            |------------------------------------------------------------------
            */
            'users.manage',
            'branches.manage',
            'categories.manage',
            'statuses.manage',
            'admin.settings',

            /*
            |------------------------------------------------------------------
            | REPORTES GLOBALES
            |------------------------------------------------------------------
            */
            'reports.view',
            'reports.global',

            /*
            |------------------------------------------------------------------
            | OPCIONAL — Activar manualmente si se requiere validacion formal
            | de actas. Por defecto no se asigna a ningun rol.
            |------------------------------------------------------------------
            */
            'actas.validate',

        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name'       => $permission,
                'guard_name' => 'web',
            ]);
        }

        $this->command->info('Permisos creados/verificados: ' . count($permissions));
    }
}
