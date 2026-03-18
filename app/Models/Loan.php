<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Loan extends Model
{
    protected $fillable = [
        'asset_id',
        'collaborator_id',
        'start_date',
        'end_date',       // fecha comprometida de devolución
        'returned_at',
        'status',         // activo | vencido | devuelto
        'notes',
        'created_by',
        'returned_by',
    ];

    protected $casts = [
        'start_date'  => 'date',
        'end_date'    => 'date',
        'returned_at' => 'datetime',
    ];

    // ── Relaciones ─────────────────────────────────────────────────────
    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function collaborator()
    {
        return $this->belongsTo(Collaborator::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function returnedBy()
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    // ── Helpers ────────────────────────────────────────────────────────

    // Días restantes para la devolución (negativo = vencido)
    public function daysRemaining(): int
    {
        return (int) now()->startOfDay()->diffInDays($this->end_date, false);
    }

    // Verdadero si el préstamo ya pasó su fecha límite
    public function isOverdue(): bool
    {
        return $this->status === 'activo' && $this->end_date->isPast();
    }

    // ── Scopes ─────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('status', 'activo');
    }

    // Próximos a vencer: activos y vencen en los próximos $days días
    public function scopeDueSoon($query, int $days = 7)
    {
        return $query->where('status', 'activo')
                     ->where('end_date', '>=', now()->startOfDay())
                     ->where('end_date', '<=', now()->addDays($days)->endOfDay());
    }

    public function scopeOverdue($query)
    {
        return $query->where('status', 'activo')
                     ->where('end_date', '<', now()->startOfDay());
    }
}
