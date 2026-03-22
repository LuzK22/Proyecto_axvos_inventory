<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Activitylog\LogOptions;
use Spatie\Activitylog\Traits\LogsActivity;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasRoles, LogsActivity;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'branch_id',
        'two_factor_secret',
        'two_factor_recovery_codes',
        'two_factor_confirmed_at',
        'password_changed_at',
        'failed_login_attempts',
        'locked_until',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at'       => 'datetime',
            'two_factor_confirmed_at' => 'datetime',
            'password_changed_at'     => 'datetime',
            'locked_until'            => 'datetime',
            'password'                => 'hashed',
            'two_factor_secret'       => 'encrypted',
        ];
    }

    // ── Activity log ───────────────────────────────────────────────

    public function getActivitylogOptions(): LogOptions
    {
        return LogOptions::defaults()
            ->logOnly(['name', 'username', 'email', 'branch_id'])
            ->logOnlyDirty()
            ->dontSubmitEmptyLogs()
            ->setDescriptionForEvent(fn(string $eventName) => "Usuario {$eventName}");
    }

    // ── Relaciones ──────────────────────────────────────────────────

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function sessions(): HasMany
    {
        return $this->hasMany(UserSession::class);
    }

    // ── Helpers ─────────────────────────────────────────────────────

    // ── Integración AdminLTE usermenu ────────────────────────────────

    /**
     * Descripción que aparece bajo el nombre en el dropdown del navbar.
     * Muestra el rol principal del usuario.
     */
    public function adminlte_desc(): string
    {
        return $this->roles->first()?->name ?? 'Sin rol';
    }

    /** El usuario tiene 2FA configurado y confirmado */
    public function hasTwoFactorEnabled(): bool
    {
        return !is_null($this->two_factor_confirmed_at);
    }

    /**
     * Devuelve el secreto 2FA descifrado con fallback para datos legacy.
     *
     * El cast 'encrypted' descifra automáticamente al acceder a $this->two_factor_secret.
     * Si en DB existe un valor en texto plano (guardado antes del cast), el cast lanza
     * DecryptException. Este método lo captura y devuelve el valor crudo como fallback.
     * En producción, los secretos nuevos siempre se guardarán cifrados.
     */
    public function twoFactorSecretSafe(): ?string
    {
        $raw = $this->getRawOriginal('two_factor_secret');

        if (empty($raw)) {
            return null;
        }

        try {
            // El cast 'encrypted' ya descifra: acceder a la propiedad del modelo
            return $this->two_factor_secret;
        } catch (\Illuminate\Contracts\Encryption\DecryptException) {
            // Fallback: valor guardado como texto plano (datos legacy pre-cifrado)
            return $raw;
        }
    }

    // ── Bloqueo de cuenta ───────────────────────────────────────────

    const MAX_LOGIN_ATTEMPTS = 3;
    const LOCKOUT_MINUTES    = 30;

    /** ¿La cuenta está bloqueada actualmente? */
    public function isLocked(): bool
    {
        return $this->locked_until !== null && $this->locked_until->isFuture();
    }

    /** Tiempo restante de bloqueo en minutos (0 si no está bloqueada). */
    public function lockRemainingMinutes(): int
    {
        if (!$this->isLocked()) return 0;
        return (int) now()->diffInMinutes($this->locked_until, false);
    }

    /** Registra un intento fallido y bloquea si se alcanza el límite. */
    public function incrementLoginAttempts(): void
    {
        $attempts = $this->failed_login_attempts + 1;

        $data = ['failed_login_attempts' => $attempts];

        if ($attempts >= self::MAX_LOGIN_ATTEMPTS) {
            $data['locked_until'] = now()->addMinutes(self::LOCKOUT_MINUTES);
        }

        $this->update($data);
    }

    /** Restablece los contadores de intentos y desbloquea la cuenta. */
    public function resetLoginAttempts(): void
    {
        $this->update([
            'failed_login_attempts' => 0,
            'locked_until'          => null,
        ]);
    }

    /** Desbloqueo manual (por Admin). */
    public function unlock(): void
    {
        $this->update([
            'failed_login_attempts' => 0,
            'locked_until'          => null,
        ]);
    }

    /** La contraseña lleva más de N días sin cambiar */
    public function passwordExpired(int $days = 90): bool
    {
        $reference = $this->password_changed_at ?? $this->created_at;
        return $reference?->addDays($days)->isPast() ?? false;
    }

    /**
     * Devuelve los códigos de recuperación 2FA como array.
     * @return string[]
     */
    public function twoFactorRecoveryCodes(): array
    {
        if (empty($this->two_factor_recovery_codes)) {
            return [];
        }
        $decoded = json_decode($this->two_factor_recovery_codes, true);
        return is_array($decoded) ? $decoded : [];
    }

    /** ¿Tiene códigos de recuperación disponibles? */
    public function hasTwoFactorRecoveryCodes(): bool
    {
        return count($this->twoFactorRecoveryCodes()) > 0;
    }
}

