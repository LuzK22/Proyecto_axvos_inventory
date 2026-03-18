<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Assignment extends Model
{
    use HasFactory;

    protected $fillable = [
        'collaborator_id',
        'area_id',
        'asset_category',
        'assigned_by',
        'assignment_date',
        'work_modality',
        'notes',
        'status',
    ];

    protected $casts = [
        'assignment_date' => 'date',
    ];

    // ─── Relaciones ────────────────────────────────────────

    public function collaborator()
    {
        return $this->belongsTo(Collaborator::class);
    }

    public function area()
    {
        return $this->belongsTo(Area::class);
    }

    /**
     * Nombre del destinatario (colaborador o área)
     */
    public function getRecipientNameAttribute(): string
    {
        if ($this->collaborator_id) {
            return $this->collaborator?->full_name ?? '—';
        }
        return 'Área: ' . ($this->area?->name ?? '—');
    }

    public function actas()
    {
        return $this->hasMany(Acta::class);
    }

    public function assignedBy()
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    /**
     * Todos los registros de activos en esta asignación (activos + devueltos)
     */
    public function assignmentAssets()
    {
        return $this->hasMany(AssignmentAsset::class);
    }

    /**
     * Solo activos aún asignados (no devueltos)
     */
    public function activeAssets()
    {
        return $this->hasMany(AssignmentAsset::class)
                    ->whereNull('returned_at');
    }

    /**
     * Activos ya devueltos
     */
    public function returnedAssets()
    {
        return $this->hasMany(AssignmentAsset::class)
                    ->whereNotNull('returned_at');
    }

    // ─── Scopes ────────────────────────────────────────────

    public function scopeActiva($query)
    {
        return $query->where('status', 'activa');
    }

    public function scopeDevuelta($query)
    {
        return $query->where('status', 'devuelta');
    }

    // ─── Helpers ───────────────────────────────────────────

    /**
     * Verificar si todos los activos han sido devueltos
     * y actualizar el estado de la asignación
     */
    public function refreshStatus(): void
    {
        $pendingCount = $this->assignmentAssets()
                             ->whereNull('returned_at')
                             ->count();

        if ($pendingCount === 0 && $this->assignmentAssets()->count() > 0) {
            $this->update(['status' => 'devuelta']);
        }
    }
}
