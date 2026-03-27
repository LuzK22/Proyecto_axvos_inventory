<?php

namespace App\Http\Middleware;

use App\Models\DataConsent;
use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware — Verificación de consentimiento Ley 1581/2012 Colombia.
 *
 * Si el usuario autenticado no ha aceptado la versión actual de la política
 * de tratamiento de datos, lo redirige a la página de consentimiento.
 *
 * Rutas excluidas: 2fa.*, logout, consent.*, password.*, sign.*
 */
class RequiresConsent
{
    /** Versión actual de la política (actualizar al cambiar el texto de la política) */
    const CURRENT_VERSION = '1.0';

    /** Rutas que no requieren consentimiento previo */
    const BYPASS_ROUTES = [
        'consent.*',
        '2fa.*',
        'logout',
        'password.*',
        'sign.*',
        'verification.*',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Solo aplica a usuarios autenticados
        if (!$user) {
            return $next($request);
        }

        // En entorno local/desarrollo se omite el consentimiento
        if (app()->isLocal()) {
            return $next($request);
        }

        // Rutas de bypass
        foreach (self::BYPASS_ROUTES as $pattern) {
            if ($request->routeIs($pattern)) {
                return $next($request);
            }
        }

        // Versión configurable desde settings (si el admin la cambió)
        $version = Setting::get('consent_policy_version', self::CURRENT_VERSION);

        // Verificar si ya aceptó la versión vigente
        if (!DataConsent::hasAccepted($user, $version)) {
            return redirect()->route('consent.show')
                ->with('info', 'Por favor revisa y acepta nuestra política de tratamiento de datos para continuar.');
        }

        return $next($request);
    }
}
