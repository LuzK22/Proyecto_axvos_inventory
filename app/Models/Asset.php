<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\AssetType;
use App\Models\Branch;
use App\Models\DeletionRequest;
use App\Models\Status;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use App\Models\Scopes\BranchScope;

class Asset extends Model
{
    use SoftDeletes, LogsActivity;

    protected static function booted(): void
    {
        static::addGlobalScope(new BranchScope());
    }

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['internal_code', 'asset_tag', 'brand', 'model', 'serial',
                       'fixed_asset_code', 'status_id', 'branch_id', 'property_type'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Activo {$eventName}");
    }
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
        'created_by',
        'updated_by',
        // ── NIIF / NIC 16 ──────────────────────────────────────
        'useful_life_years',       // Vida útil en años
        'residual_value',          // Valor de salvamento
        'depreciation_method',     // linea_recta | saldo_decreciente | unidades_produccion | no_deprecia
        'depreciation_start_date', // Fecha inicio depreciación
        'account_code',            // Código PUC Colombia (ej. 1524050501)
    ];

    protected $casts = [
        'purchase_date'           => 'date',
        'purchase_value'          => 'decimal:2',
        'residual_value'          => 'decimal:2',
        'depreciation_start_date' => 'date',
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

    // Solicitudes de baja
    public function deletionRequests()
    {
        return $this->hasMany(DeletionRequest::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(\App\Models\User::class, 'updated_by');
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

    // ─── Accessors NIIF / NIC 16 ───────────────────────────

    /**
     * Depreciación anual por línea recta (NIIF NIC 16).
     * Fórmula: (Valor adquisición − Valor residual) / Vida útil en años
     * Retorna null si faltan datos contables.
     */
    public function getAnnualDepreciationAttribute(): ?float
    {
        if (
            !$this->purchase_value ||
            !$this->useful_life_years ||
            $this->useful_life_years <= 0 ||
            $this->depreciation_method === 'no_deprecia'
        ) {
            return null;
        }

        $residual = (float) ($this->residual_value ?? 0);
        return round(((float) $this->purchase_value - $residual) / $this->useful_life_years, 2);
    }

    /**
     * Valor en libros actual (NIIF NIC 16).
     * Fórmula: Valor adquisición − (Depreciación anual × años transcurridos)
     * Nunca baja del valor residual.  Retorna null si faltan datos.
     */
    public function getCurrentBookValueAttribute(): ?float
    {
        $annual = $this->annual_depreciation;

        if ($annual === null || !$this->depreciation_start_date) {
            return $this->purchase_value ? (float) $this->purchase_value : null;
        }

        $yearsElapsed = (float) $this->depreciation_start_date->diffInDays(now()) / 365;
        $accumulated  = $annual * $yearsElapsed;
        $bookValue    = (float) $this->purchase_value - $accumulated;
        $residual     = (float) ($this->residual_value ?? 0);

        return round(max($bookValue, $residual), 2);
    }
}
