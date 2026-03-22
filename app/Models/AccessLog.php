<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AccessLog extends Model
{
    public $timestamps = false;   // solo created_at, inmutable

    protected $fillable = [
        'user_id',
        'event_type',
        'email',
        'ip_address',
        'user_agent',
        'session_id',
        'created_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Impedir modificaciones: este log es inmutable por diseño.
     */
    public function save(array $options = []): bool
    {
        if ($this->exists) {
            throw new \RuntimeException('AccessLog records are immutable and cannot be updated.');
        }
        return parent::save($options);
    }

    public function delete(): bool|null
    {
        throw new \RuntimeException('AccessLog records cannot be deleted to preserve audit trail.');
    }

    // ── Helpers estáticos ───────────────────────────────────────────

    public static function recordLogin(User $user, string $sessionId): void
    {
        static::create([
            'user_id'    => $user->id,
            'event_type' => 'login',
            'email'      => $user->email,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => $sessionId,
            'created_at' => now(),
        ]);
    }

    public static function recordLogout(User $user): void
    {
        static::create([
            'user_id'    => $user->id,
            'event_type' => 'logout',
            'email'      => $user->email,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => session()->getId(),
            'created_at' => now(),
        ]);
    }

    public static function recordFailedLogin(string $email): void
    {
        static::create([
            'user_id'    => null,
            'event_type' => 'login_failed',
            'email'      => $email,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'session_id' => null,
            'created_at' => now(),
        ]);
    }
}
