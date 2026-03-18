<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Acta extends Model
{
    protected $fillable = [
        'assignment_id',
        'acta_number',
        'acta_type',
        'status',
        'pdf_path',
        'generated_by',
        'notes',
        'sent_at',
        'completed_at',
    ];

    protected $casts = [
        'sent_at'       => 'datetime',
        'completed_at'  => 'datetime',
    ];

    // ─── Constantes de estado ──────────────────────────────────
    const STATUS_BORRADOR             = 'borrador';
    const STATUS_ENVIADA              = 'enviada';
    const STATUS_FIRMADA_COLABORADOR  = 'firmada_colaborador';
    const STATUS_FIRMADA_RESPONSABLE  = 'firmada_responsable';
    const STATUS_COMPLETADA           = 'completada';
    const STATUS_ANULADA              = 'anulada';

    // ─── Relaciones ────────────────────────────────────────────

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function signatures(): HasMany
    {
        return $this->hasMany(ActaSignature::class);
    }

    public function collaboratorSignature()
    {
        return $this->hasOne(ActaSignature::class)
                    ->where('signer_role', 'collaborator');
    }

    public function responsibleSignature()
    {
        return $this->hasOne(ActaSignature::class)
                    ->where('signer_role', 'responsible');
    }

    // ─── Helpers ───────────────────────────────────────────────

    public function isSigned(): bool
    {
        return $this->status === self::STATUS_COMPLETADA;
    }

    public function isPending(): bool
    {
        return in_array($this->status, [
            self::STATUS_BORRADOR,
            self::STATUS_ENVIADA,
            self::STATUS_FIRMADA_COLABORADOR,
            self::STATUS_FIRMADA_RESPONSABLE,
        ]);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_BORRADOR            => 'Borrador',
            self::STATUS_ENVIADA             => 'Enviada para firma',
            self::STATUS_FIRMADA_COLABORADOR => 'Firmada por colaborador',
            self::STATUS_FIRMADA_RESPONSABLE => 'Firmada por responsable',
            self::STATUS_COMPLETADA          => 'Completada',
            self::STATUS_ANULADA             => 'Anulada',
            default                          => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_BORRADOR            => 'secondary',
            self::STATUS_ENVIADA             => 'info',
            self::STATUS_FIRMADA_COLABORADOR,
            self::STATUS_FIRMADA_RESPONSABLE => 'warning',
            self::STATUS_COMPLETADA          => 'success',
            self::STATUS_ANULADA             => 'danger',
            default                          => 'secondary',
        };
    }

    // ─── Tipos de acta ────────────────────────────────────────
    const TYPE_ENTREGA       = 'entrega';
    const TYPE_DEVOLUCION    = 'devolucion';
    const TYPE_BAJA          = 'baja';
    const TYPE_DONACION      = 'donacion';
    const TYPE_VENTA         = 'venta';
    const TYPE_ACTUALIZACION = 'actualizacion';

    public static function typePrefix(string $type): string
    {
        return match($type) {
            self::TYPE_ENTREGA       => 'ENT',
            self::TYPE_DEVOLUCION    => 'DEV',
            self::TYPE_BAJA          => 'BAJ',
            self::TYPE_DONACION      => 'DON',
            self::TYPE_VENTA         => 'VEN',
            self::TYPE_ACTUALIZACION => 'ACT',
            default                  => 'ACT',
        };
    }

    public function getTypeLabelAttribute(): string
    {
        return match($this->acta_type) {
            self::TYPE_ENTREGA       => 'Entrega',
            self::TYPE_DEVOLUCION    => 'Devolución',
            self::TYPE_BAJA          => 'Baja',
            self::TYPE_DONACION      => 'Donación',
            self::TYPE_VENTA         => 'Venta',
            self::TYPE_ACTUALIZACION => 'Actualización',
            default                  => ucfirst($this->acta_type),
        };
    }

    public function getTypeColorAttribute(): string
    {
        return match($this->acta_type) {
            self::TYPE_ENTREGA       => 'primary',
            self::TYPE_DEVOLUCION    => 'warning',
            self::TYPE_BAJA          => 'danger',
            self::TYPE_DONACION      => 'info',
            self::TYPE_VENTA         => 'dark',
            self::TYPE_ACTUALIZACION => 'secondary',
            default                  => 'secondary',
        };
    }

    /**
     * Genera el número de acta secuencial.
     * Formato: ACT-TI-ENT-2026-0012  /  ACT-OT-ENT-2026-0012
     */
    public static function generateActaNumber(string $category = 'TI', string $type = 'entrega'): string
    {
        $year      = now()->year;
        $catPrefix = $category === 'OTRO' ? 'OT' : 'TI';
        $prefix    = self::typePrefix($type);
        $count     = self::whereYear('created_at', $year)
                         ->where('asset_category', $category)
                         ->where('acta_type', $type)
                         ->count();
        $seq = str_pad($count + 1, 4, '0', STR_PAD_LEFT);
        return "ACT-{$catPrefix}-{$prefix}-{$year}-{$seq}";
    }

    /**
     * Actualiza el estado del acta según las firmas presentes
     */
    public function refreshStatus(): void
    {
        $collSigned = $this->collaboratorSignature?->signed_at !== null;
        $respSigned = $this->responsibleSignature?->signed_at  !== null;

        if ($collSigned && $respSigned) {
            $this->update([
                'status'       => self::STATUS_COMPLETADA,
                'completed_at' => now(),
            ]);
        } elseif ($collSigned) {
            $this->update(['status' => self::STATUS_FIRMADA_COLABORADOR]);
        } elseif ($respSigned) {
            $this->update(['status' => self::STATUS_FIRMADA_RESPONSABLE]);
        }
    }
}
