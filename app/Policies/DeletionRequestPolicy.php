<?php

namespace App\Policies;

use App\Models\DeletionRequest;
use App\Models\User;

class DeletionRequestPolicy
{
    /**
     * Solo el Aprobador (o Admin) puede aprobar una solicitud de baja.
     */
    public function approve(User $user, DeletionRequest $deletionRequest): bool
    {
        // Solo se puede aprobar si está pendiente
        if ($deletionRequest->status !== DeletionRequest::STATUS_PENDING) {
            return false;
        }

        return $user->hasPermissionTo('tech.assets.disposal.approve')
            || $user->hasPermissionTo('assets.disposal.approve');
    }

    /**
     * Solo el Aprobador (o Admin) puede rechazar una solicitud de baja.
     */
    public function reject(User $user, DeletionRequest $deletionRequest): bool
    {
        if ($deletionRequest->status !== DeletionRequest::STATUS_PENDING) {
            return false;
        }

        return $user->hasPermissionTo('tech.assets.disposal.approve')
            || $user->hasPermissionTo('assets.disposal.approve');
    }
}
