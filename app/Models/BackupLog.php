<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BackupLog extends Model
{
    protected $fillable = [
        'filename', 'size_bytes', 'type', 'status',
        'disk', 'triggered_by', 'error_message',
        'downloaded_at', 'downloaded_by',
    ];

    protected $casts = [
        'downloaded_at' => 'datetime',
        'size_bytes'    => 'integer',
    ];

    public function triggeredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'triggered_by');
    }

    public function downloadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'downloaded_by');
    }

    /** Tamaño legible para humanos */
    public function getSizeHumanAttribute(): string
    {
        $bytes = $this->size_bytes;
        if ($bytes >= 1073741824) return round($bytes / 1073741824, 2) . ' GB';
        if ($bytes >= 1048576)    return round($bytes / 1048576, 2) . ' MB';
        if ($bytes >= 1024)       return round($bytes / 1024, 2) . ' KB';
        return $bytes . ' B';
    }
}
