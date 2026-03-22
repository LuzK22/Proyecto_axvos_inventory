<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class BranchScope implements Scope
{
    /**
     * Roles that can see assets from ALL branches (no filter applied)
     */
    const GLOBAL_ROLES = ['Admin', 'Gestor_General', 'Auditor'];

    public function apply(Builder $builder, Model $model): void
    {
        $user = auth()->user();

        if (!$user) {
            return;
        }

        // Global roles bypass branch filtering
        if ($user->hasAnyRole(self::GLOBAL_ROLES)) {
            return;
        }

        // Users without a branch assigned can only see unassigned-branch assets
        if (!$user->branch_id) {
            return;
        }

        $builder->where($model->getTable() . '.branch_id', $user->branch_id);
    }
}
