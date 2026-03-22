<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware de dos factores (2FA).
 *
 * Roles y modo de aplicación se leen desde la tabla `settings` (configurables
 * por el administrador en el panel de Configuración → Seguridad 2FA).
 *
 * FLUJO:
 *  1. Si 2FA está deshabilitado globalmente → libre acceso.
 *  2. Si el rol del usuario no está en la lista de roles requeridos → libre acceso.
 *  3. Rutas 2fa.* y logout siempre pasan (evita bucles).
 *  4. Dentro del periodo de gracia → libre acceso.
 *  5. Si 2FA no configurado:
 *       - enforcement=required → flash aviso, pasa (login ya redirigió a setup).
 *       - enforcement=recommended → igual, pasa con aviso.
 *  6. Si 2FA configurado pero sesión no verificada → bloquea en /2fa/verify.
 *  7. Todo OK → pasa.
 */
class RequiresTwoFactor
{
    /** Fallback estático usado por AuthenticatedSessionController si Setting falla */
    const REQUIRED_ROLES = ['Admin', 'Aprobador'];

    /** Rutas siempre accesibles sin importar estado 2FA */
    const BYPASS_ROUTES = ['2fa.*', 'logout'];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        // ── 1. 2FA deshabilitado globalmente ──────────────────────────────────
        if (!Setting::twoFactorEnabled()) {
            return $next($request);
        }

        // ── 2. Rol no requerido ───────────────────────────────────────────────
        $requiredRoles = Setting::twoFactorRequiredRoles();
        if (empty($requiredRoles) || !$user->hasAnyRole($requiredRoles)) {
            return $next($request);
        }

        // ── 3. Rutas de bypass ────────────────────────────────────────────────
        foreach (self::BYPASS_ROUTES as $pattern) {
            if ($request->routeIs($pattern)) {
                return $next($request);
            }
        }

        // ── 4. Periodo de gracia ──────────────────────────────────────────────
        $graceDays = Setting::twoFactorGraceDays();
        if ($graceDays > 0 && $user->created_at->diffInDays(now()) < $graceDays) {
            session()->flash('2fa_pending_setup', true);
            return $next($request);
        }

        // ── 5. 2FA no configurado ─────────────────────────────────────────────
        if (!$user->hasTwoFactorEnabled()) {
            session()->flash('2fa_pending_setup', true);
            return $next($request);
        }

        // ── 6. 2FA configurado, sesión no verificada ──────────────────────────
        if (!session('2fa_verified')) {
            return redirect()->route('2fa.verify')
                ->with('info', 'Ingresa tu código de autenticación para continuar.');
        }

        // ── 7. Todo correcto ──────────────────────────────────────────────────
        return $next($request);
    }
}
