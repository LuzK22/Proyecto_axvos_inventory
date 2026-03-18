<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActaSignature extends Model
{
    protected $fillable = [
        'acta_id',
        'signer_role',
        'signer_name',
        'signer_email',
        'signer_user_id',
        'token',
        'token_expires_at',
        'signed_at',
        'signature_type',
        'signature_data',
        'signed_ip',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'signed_at'        => 'datetime',
    ];

    // ─── Relaciones ────────────────────────────────────────

    public function acta(): BelongsTo
    {
        return $this->belongsTo(Acta::class);
    }

    public function signerUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'signer_user_id');
    }

    // ─── Helpers ───────────────────────────────────────────

    public function isSigned(): bool
    {
        return $this->signed_at !== null;
    }

    public function isTokenValid(): bool
    {
        return $this->token !== null
            && ($this->token_expires_at === null || $this->token_expires_at->isFuture());
    }

    public function getRoleLabelAttribute(): string
    {
        return match ($this->signer_role) {
            'collaborator' => 'Colaborador / Receptor',
            'responsible'  => 'Responsable del Sistema',
            'manager'      => 'Gestor',
            default        => $this->signer_role,
        };
    }

    /**
     * Genera un token único de 48 caracteres hexadecimales
     */
    public static function generateToken(): string
    {
        return bin2hex(random_bytes(24));
    }
}
