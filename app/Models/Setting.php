<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group', 'label', 'type'];

    /**
     * Obtener un valor de configuración
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember("setting.{$key}", 3600, function () use ($key, $default) {
            $setting = static::where('key', $key)->first();
            return $setting?->value ?? $default;
        });
    }

    /**
     * Guardar/actualizar un valor de configuración
     */
    public static function set(string $key, mixed $value): void
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("setting.{$key}");
    }

    /**
     * Obtener todas las configuraciones de un grupo
     */
    public static function getGroup(string $group): array
    {
        return static::where('group', $group)
            ->get()
            ->pluck('value', 'key')
            ->toArray();
    }

    // ── Helpers de seguridad 2FA ────────────────────────────────────────────

    /** ¿Está habilitado el módulo 2FA globalmente? */
    public static function twoFactorEnabled(): bool
    {
        return static::get('security_2fa_enabled', '1') === '1';
    }

    /**
     * Roles que requieren 2FA según configuración.
     * @return string[]
     */
    public static function twoFactorRequiredRoles(): array
    {
        $raw = static::get('security_2fa_required_roles', 'Admin,Aprobador');
        return array_values(array_filter(array_map('trim', explode(',', $raw))));
    }

    /**
     * Modo de aplicación: 'required' (bloquea) o 'recommended' (avisa).
     */
    public static function twoFactorEnforcement(): string
    {
        return static::get('security_2fa_enforcement', 'required');
    }

    /**
     * Días de gracia desde la creación de cuenta antes de exigir 2FA.
     * 0 = inmediato.
     */
    public static function twoFactorGraceDays(): int
    {
        return (int) static::get('security_2fa_grace_days', 0);
    }
}
