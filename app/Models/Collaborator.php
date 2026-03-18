<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Collaborator extends Model
{
    protected $fillable = [
        'full_name',
        'document',
        'email',
        'phone',
        'position',
        'area',
        'modalidad_trabajo',
        'branch_id',
        'active',
    ];

    // ─── Relaciones ────────────────────────────────────────

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    /**
     * Historial completo de asignaciones
     */
    public function assignments()
    {
        return $this->hasMany(Assignment::class);
    }

    /**
     * Asignaciones activas (con al menos un activo pendiente)
     */
    public function activeAssignments()
    {
        return $this->hasMany(Assignment::class)->where('status', 'activa');
    }

    /**
     * Activos actualmente asignados (a través de assignment_assets)
     */
    public function assignedAssets()
    {
        return Asset::whereHas('assignmentAssets', function ($q) {
            $q->whereNull('returned_at')
              ->whereHas('assignment', fn($a) => $a->where('collaborator_id', $this->id));
        });
    }

    public function workstationsInCharge()
    {
        return $this->hasMany(Workstation::class, 'responsible_id');
    }

    // ─── Helpers ───────────────────────────────────────────

    public function getModalidadLabelAttribute(): string
    {
        return match($this->modalidad_trabajo) {
            'remoto'     => 'Remoto',
            'hibrido'    => 'Híbrido',
            'presencial' => 'Presencial',
            default      => 'Presencial',
        };
    }

    public function getModalidadBadgeAttribute(): string
    {
        return match($this->modalidad_trabajo) {
            'remoto'     => 'badge bg-info',
            'hibrido'    => 'badge bg-warning text-dark',
            'presencial' => 'badge bg-success',
            default      => 'badge bg-success',
        };
    }
}
