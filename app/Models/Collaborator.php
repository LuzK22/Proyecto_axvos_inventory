<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;

class Collaborator extends Model
{
    use SoftDeletes, LogsActivity;

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

    protected $casts = [];

    /**
     * Cédula: intenta descifrar, devuelve valor plano si falla.
     * Permite migración gradual de valores sin cifrar.
     */
    protected function document(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (empty($value)) return $value;
                try {
                    return decrypt($value);
                } catch (\Exception $e) {
                    return $value; // valor plano (registros antiguos)
                }
            },
            set: fn ($value) => !empty($value) ? encrypt($value) : $value,
        );
    }

    /**
     * Teléfono: misma lógica tolerante al cifrado.
     */
    protected function phone(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if (empty($value)) return $value;
                try {
                    return decrypt($value);
                } catch (\Exception $e) {
                    return $value;
                }
            },
            set: fn ($value) => !empty($value) ? encrypt($value) : $value,
        );
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['full_name', 'document', 'email', 'position', 'area', 'branch_id', 'active', 'modalidad_trabajo'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Colaborador {$eventName}");
    }

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
