<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Registro de consentimiento de tratamiento de datos personales.
 * Ley 1581/2012 Colombia — Decreto 1377/2013.
 *
 * Este modelo es INMUTABLE: los consentimientos no se editan, solo se revocan.
 */
class DataConsent extends Model
{
    public $timestamps = false; // useCurrent en la migración

    protected $fillable = [
        'user_id',
        'policy_version',
        'type',
        'accepted_at',
        'ip_address',
        'user_agent',
        'revoked_at',
        'revocation_reason',
    ];

    protected $casts = [
        'accepted_at' => 'datetime',
        'revoked_at'  => 'datetime',
    ];

    // ── Relaciones ──────────────────────────────────────────────

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // ── Helpers ─────────────────────────────────────────────────

    /** ¿El consentimiento está vigente (no revocado)? */
    public function isActive(): bool
    {
        return is_null($this->revoked_at);
    }

    /**
     * Registra un nuevo consentimiento para el usuario.
     * Si ya existe uno vigente para la misma versión y tipo, no crea duplicado.
     */
    public static function recordFor(
        User   $user,
        string $type           = 'data_treatment',
        string $policyVersion  = '1.0',
        ?string $ip            = null,
        ?string $userAgent     = null,
    ): static {
        // Evitar duplicados para la misma versión activa
        $existing = static::where('user_id', $user->id)
            ->where('policy_version', $policyVersion)
            ->where('type', $type)
            ->whereNull('revoked_at')
            ->first();

        if ($existing) {
            return $existing;
        }

        return static::create([
            'user_id'        => $user->id,
            'policy_version' => $policyVersion,
            'type'           => $type,
            'accepted_at'    => now(),
            'ip_address'     => $ip,
            'user_agent'     => $userAgent,
        ]);
    }

    /**
     * Verifica si el usuario ya aceptó la versión actual de la política.
     */
    public static function hasAccepted(User $user, string $policyVersion = '1.0'): bool
    {
        return static::where('user_id', $user->id)
            ->where('policy_version', $policyVersion)
            ->whereNull('revoked_at')
            ->exists();
    }
}
