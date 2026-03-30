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

    public static function createCollaboratorSignature(Acta $acta, string $name, ?string $email = null, int $expiresDays = 7): self
    {
        return self::create([
            'acta_id'          => $acta->id,
            'signer_role'      => 'collaborator',
            'signer_name'      => $name,
            'signer_email'     => $email,
            'token'            => self::generateToken(),
            'token_expires_at' => now()->addDays($expiresDays),
        ]);
    }

    public static function createResponsibleSignature(Acta $acta, User $user, int $expiresDays = 7): self
    {
        $hasDefaultSignature = !empty($user->default_signature_data);

        return self::create([
            'acta_id'          => $acta->id,
            'signer_role'      => 'responsible',
            'signer_name'      => $user->name,
            'signer_email'     => $user->email,
            'signer_user_id'   => $user->id,
            'token'            => $hasDefaultSignature ? null : self::generateToken(),
            'token_expires_at' => $hasDefaultSignature ? null : now()->addDays($expiresDays),
            'signed_at'        => $hasDefaultSignature ? now() : null,
            'signature_type'   => $hasDefaultSignature ? ($user->default_signature_type ?: 'drawn') : null,
            'signature_data'   => $hasDefaultSignature ? $user->default_signature_data : null,
            'signed_ip'        => $hasDefaultSignature ? 'AUTO_PROFILE' : null,
        ]);
    }
}
