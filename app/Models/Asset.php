<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\AssetType;
use App\Models\Branch;
use App\Models\Status;

class Asset extends Model
{
    protected $fillable = [
        'asset_type_id',
        'internal_code',      // código autogenerado: TI-POR-00001
        'asset_tag',          // sticker físico pegado al equipo
        'brand',
        'model',
        'serial',
        'fixed_asset_code',   // código contable para cruce con SAP/Siigo
        'property_type',      // PROPIO | LEASING | ALQUILADO | OTRO
        'purchase_value',
        'purchase_date',
        'provider_name',      // solo aplica si es LEASING o ALQUILADO
        'status_id',
        'branch_id',
        'observations',
    ];

    protected $casts = [
        'purchase_date'  => 'date',
        'purchase_value' => 'decimal:2',
    ];

    // ─── Relaciones ────────────────────────────────────────

    public function type()
    {
        return $this->belongsTo(AssetType::class, 'asset_type_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function status()
    {
        return $this->belongsTo(Status::class);
    }

    // Todos los registros de asignación (histórico completo)
    public function assignmentAssets()
    {
        return $this->hasMany(AssignmentAsset::class);
    }

    // Solo el registro de asignación activo (sin fecha de devolución)
    public function currentAssignmentAsset()
    {
        return $this->hasOne(AssignmentAsset::class)->whereNull('returned_at')->latest();
    }

    // Atajo directo a la asignación activa a través del pivot
    public function currentAssignment()
    {
        return $this->hasOneThrough(
            Assignment::class,
            AssignmentAsset::class,
            'asset_id',
            'id',
            'id',
            'assignment_id'
        )->whereNull('assignment_assets.returned_at');
    }

    // Historial de cambios de estado (mantenimiento, traslados, bajas, etc.)
    public function events()
    {
        return $this->hasMany(\App\Models\AssetEvent::class)->latest();
    }

    // ─── Helpers de estado ─────────────────────────────────

    public function isAvailable(): bool
    {
        return $this->status?->name === 'Disponible';
    }

    public function isAssigned(): bool
    {
        return $this->status?->name === 'Asignado';
    }

    // Activos retirados definitivamente — no se pueden reasignar
    public function isRetired(): bool
    {
        return in_array($this->status?->name, ['Baja', 'Donado', 'Vendido']);
    }

    // ─── Accessor: clase CSS del badge según estado ─────────

    public function getStatusBadgeAttribute(): string
    {
        return match($this->status?->name) {
            'Disponible'    => 'badge bg-success',
            'Asignado'      => 'badge bg-primary',
            'En Bodega'     => 'badge bg-secondary',
            'Préstamo'      => 'badge bg-warning text-dark',
            'Baja'          => 'badge bg-danger',
            'En Garantía'   => 'badge bg-info',
            'Mantenimiento' => 'badge bg-warning text-dark',
            'En Traslado'   => 'badge bg-info',
            'Donado'        => 'badge bg-dark',
            'Vendido'       => 'badge bg-dark',
            default         => 'badge bg-light text-dark',
        };
    }
}
