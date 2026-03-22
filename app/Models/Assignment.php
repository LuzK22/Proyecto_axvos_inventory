<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Assignment extends Model
{
    use HasFactory, SoftDeletes, LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['collaborator_id', 'status', 'work_modality', 'assigned_by'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Asignación {$eventName}");
    }

    protected $fillable = [
        'collaborator_id',
        'area_id',
        'destination_type',  // collaborator | jefe | area | pool
        'asset_category',    // TI | OTRO
        'assigned_by',
        'assignment_date',
        'work_modality',     // presencial | remoto | hibrido
        'notes',
        'status',            // activa | devuelta | parcial
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
     * Etiqueta legible del tipo de destino
     */
    public static function destinationLabel(string $type): string
    {
        return match($type) {
            'collaborator' => 'Colaborador',
            'jefe'         => 'Jefe / Responsable de Área',
            'area'         => 'Área Compartida',
            'pool'         => 'Pool Compartido',
            default        => 'Colaborador',
        };
    }

    /**
     * Nombre del destinatario según el tipo de destino
     */
    public function getRecipientNameAttribute(): string
    {
        return match($this->destination_type) {
            'collaborator' => $this->collaborator?->full_name ?? '—',
            'jefe'         => 'Jefe: ' . ($this->collaborator?->full_name ?? '—'),
            'area'         => 'Área: ' . ($this->area?->name ?? '—'),
            'pool'         => 'Pool compartido' . ($this->area ? ' — ' . $this->area->name : ''),
            default        => $this->collaborator?->full_name ?? '—',
        };
    }

    /**
     * Ícono FontAwesome para el tipo de destino
     */
    public function getDestinationIconAttribute(): string
    {
        return match($this->destination_type) {
            'collaborator' => 'fa-user',
            'jefe'         => 'fa-user-tie',
            'area'         => 'fa-map-marker-alt',
            'pool'         => 'fa-sync-alt',
            default        => 'fa-user',
        };
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
