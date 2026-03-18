<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AssignmentAsset extends Model
{
    protected $fillable = [
        'assignment_id',
        'asset_id',
        'assigned_at',
        'returned_at',
        'return_notes',
        'returned_by',
    ];

    protected $casts = [
        'assigned_at'  => 'datetime',
        'returned_at'  => 'datetime',
    ];

    // ─── Relaciones ────────────────────────────────────────

    public function assignment()
    {
        return $this->belongsTo(Assignment::class);
    }

    public function asset()
    {
        return $this->belongsTo(Asset::class);
    }

    public function returnedBy()
    {
        return $this->belongsTo(User::class, 'returned_by');
    }

    // ─── Helpers ───────────────────────────────────────────

    public function isReturned(): bool
    {
        return $this->returned_at !== null;
    }
}
