<?php

namespace App\Policies;

use App\Models\Asset;
use App\Models\User;
use App\Models\Scopes\BranchScope;

class AssetPolicy
{
    /**
     * Determina si el usuario puede ver el activo.
     * Regla: solo puede verlo si pertenece a la misma sucursal,
     * o si tiene permiso de reportes globales (acceso a todas las sucursales).
     */
    public function view(User $user, Asset $asset): bool
    {
        // Global roles / global-report permission see all assets
        if ($user->hasAnyRole(BranchScope::GLOBAL_ROLES) || $user->hasPermissionTo('reports.global')) {
            return true;
        }

        // Users without a branch see all (e.g. single-branch installs with no branch_id)
        if (!$user->branch_id) {
            return true;
        }

        return $asset->branch_id === $user->branch_id;
    }

    /**
     * Determina si el usuario puede editar el activo.
     * Solo usuarios con permiso tech.assets.edit o assets.edit.
     */
    public function update(User $user, Asset $asset): bool
    {
        $category = $asset->type?->category;

        if ($category === 'TI') {
            return $user->hasPermissionTo('tech.assets.edit');
        }

        return $user->hasPermissionTo('assets.edit');
    }

    /**
     * Determina si el usuario puede solicitar la baja del activo.
     */
    public function requestDisposal(User $user, Asset $asset): bool
    {
        // No se puede solicitar baja si ya está en Baja, Donado o Vendido
        if ($asset->isRetired()) {
            return false;
        }

        $category = $asset->type?->category;

        if ($category === 'TI') {
            return $user->hasPermissionTo('tech.assets.disposal.request');
        }

        return $user->hasPermissionTo('assets.disposal.request');
    }

    /**
     * Determina si el usuario puede aprobar la baja del activo.
     */
    public function approveDisposal(User $user, Asset $asset): bool
    {
        return $user->hasPermissionTo('tech.assets.disposal.approve')
            || $user->hasPermissionTo('assets.disposal.approve');
    }
}
