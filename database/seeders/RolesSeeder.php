<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolesSeeder extends Seeder
{
    /**
     * Roles del sistema AXVOS Inventory
     *
     * Admin           - Acceso total: modulos, configuracion, usuarios, parametros
     * Auxiliar_TI     - TI completo + admin basico. NO aprueba bajas.
     * Gestor_Activos  - Otros Activos completo + admin basico. NO aprueba bajas.
     * Gestor_General  - TI + Otros completo. Genera actas para ambos.
     * Aprobador       - Aprueba/rechaza bajas TI y Otros. Controles de excepcion.
     * Auditor         - Solo lectura. Reportes + auditoria global. Exporta Excel.
     */
    public function run(): void
    {
        $roles = [
            'Admin',
            'Auxiliar_TI',
            'Gestor_Activos',
            'Gestor_General',
            'Aprobador',
            'Auditor',
        ];

        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        $this->assignPermissionsToRoles();

        $this->command->info('Roles creados/verificados: ' . count($roles));
    }

    private function assignPermissionsToRoles(): void
    {
        // ── Admin: todo ────────────────────────────────────────────────────────
        Role::findByName('Admin')->syncPermissions(Permission::all());

        // ── Auxiliar_TI ────────────────────────────────────────────────────────
        // Activos TI, Asignaciones TI, Prestamos TI, Reportes TI, Admin basico.
        // NO aprueba bajas ni elimina activos.
        Role::findByName('Auxiliar_TI')->syncPermissions([
            'collaborators.view', 'collaborators.create', 'collaborators.edit',
            'asset-types.view', 'asset-types.create', 'asset-types.edit',
            'tech.types.view', 'tech.types.create', 'tech.types.edit',
            'tech.assets.view', 'tech.assets.create', 'tech.assets.edit', 'tech.assets.assign',
            'tech.assets.disposal.view', 'tech.assets.disposal.request',
            'tech.history.view', 'tech.reports.view',
            'users.manage', 'branches.manage', 'categories.manage',
            'statuses.manage', 'admin.settings',
        ]);

        // ── Gestor_Activos ─────────────────────────────────────────────────────
        // Otros Activos completo, Asignaciones, Reportes, Admin basico.
        // NO aprueba bajas.
        Role::findByName('Gestor_Activos')->syncPermissions([
            'collaborators.view', 'collaborators.create', 'collaborators.edit',
            'assets.view', 'assets.create', 'assets.edit', 'assets.assign',
            'assets.disposal.view', 'assets.disposal.request',
            'assets.reports.view', 'assets.history.view',
            'users.manage', 'branches.manage', 'categories.manage',
            'statuses.manage', 'admin.settings',
        ]);

        // ── Gestor_General ─────────────────────────────────────────────────────
        // Acceso completo TI + Otros Activos. Genera actas para ambos.
        // Solicita bajas pero NO las aprueba.
        Role::findByName('Gestor_General')->syncPermissions([
            'collaborators.view', 'collaborators.create', 'collaborators.edit',
            'asset-types.view', 'asset-types.create', 'asset-types.edit',
            'tech.types.view', 'tech.types.create', 'tech.types.edit',
            'tech.assets.view', 'tech.assets.create', 'tech.assets.edit', 'tech.assets.assign',
            'tech.assets.disposal.view', 'tech.assets.disposal.request',
            'tech.history.view', 'tech.reports.view',
            'assets.view', 'assets.create', 'assets.edit', 'assets.assign',
            'assets.disposal.view', 'assets.disposal.request',
            'assets.reports.view', 'assets.history.view',
            'users.manage', 'branches.manage', 'categories.manage',
            'statuses.manage', 'admin.settings',
        ]);

        // ── Aprobador ──────────────────────────────────────────────────────────
        // Unico rol con autoridad para aprobar/rechazar bajas TI y Otros.
        // Ve activos para contexto. Controles de excepcion y auditoria.
        // NO tiene acceso a crear, editar ni asignar activos.
        Role::findByName('Aprobador')->syncPermissions([
            'collaborators.view',
            'tech.assets.view',
            'assets.view',
            'tech.assets.disposal.view', 'tech.assets.disposal.approve',
            'assets.disposal.view', 'assets.disposal.approve',
            'loans.approve',
            'assets.maintenance.approve',
            'audit.discrepancy.resolve',
            'audit.adjustment.approve',
            'reports.view',
        ]);

        // ── Auditor ────────────────────────────────────────────────────────────
        // Solo lectura total. No puede crear, editar ni eliminar nada.
        // Accede a reportes TI, Otros, auditoria global. Exporta Excel.
        Role::findByName('Auditor')->syncPermissions([
            'collaborators.view',
            'tech.assets.view', 'tech.history.view', 'tech.reports.view',
            'assets.view', 'assets.history.view', 'assets.reports.view',
            'audit.view', 'audit.export',
            'reports.view', 'reports.global',
        ]);
    }
}
