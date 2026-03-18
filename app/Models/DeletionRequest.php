<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeletionRequest extends Model
{
    protected $fillable = [
        'asset_id', 'requested_by', 'resolved_by',
        'status', 'reason', 'notes', 'rejection_notes', 'resolved_at',
    ];

    protected $casts = ['resolved_at' => 'datetime'];

    const STATUS_PENDING  = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    const REASONS = [
        'danado'    => 'Dañado / Inservible',
        'obsoleto'  => 'Obsoleto',
        'perdido'   => 'Perdido / Extraviado',
        'venta'     => 'Venta',
        'donacion'  => 'Donación',
        'otro'      => 'Otro',
    ];

    public function getReasonLabelAttribute(): string
    {
        return self::REASONS[$this->reason] ?? ucfirst($this->reason);
    }

    public function getStatusLabelAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING  => 'Pendiente',
            self::STATUS_APPROVED => 'Aprobada',
            self::STATUS_REJECTED => 'Rechazada',
            default               => $this->status,
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING  => 'warning',
            self::STATUS_APPROVED => 'success',
            self::STATUS_REJECTED => 'danger',
            default               => 'secondary',
        };
    }

    public function asset()       { return $this->belongsTo(Asset::class); }
    public function requestedBy() { return $this->belongsTo(User::class, 'requested_by'); }
    public function resolvedBy()  { return $this->belongsTo(User::class, 'resolved_by'); }
}
