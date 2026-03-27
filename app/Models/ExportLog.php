<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de exportaciones de datos.
 * ISO 27001 — Control A.8.2.3 Manejo de activos de información.
 * Permite detectar accesos masivos o exfiltración de datos.
 */
class ExportLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'entity_type',
        'format',
        'filters',
        'rows_exported',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'filters'    => 'array',
        'created_at' => 'datetime',
    ];

    // ── Relaciones ──────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Factory method ──────────────────────────────────────────

    /**
     * Registra una exportación con todos los datos de trazabilidad.
     */
    public static function record(
        string  $entityType,
        string  $format       = 'xlsx',
        array   $filters      = [],
        int     $rowsExported = 0,
        ?string $ip           = null,
        ?string $userAgent    = null,
    ): static {
        return static::create([
            'user_id'       => auth()->id(),
            'entity_type'   => $entityType,
            'format'        => $format,
            'filters'       => $filters,
            'rows_exported' => $rowsExported,
            'ip_address'    => $ip ?? request()->ip(),
            'user_agent'    => $userAgent ?? request()->userAgent(),
            'created_at'    => now(),
        ]);
    }
}
