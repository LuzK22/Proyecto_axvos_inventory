<?php

namespace App\Http\Middleware;

use App\Models\UserSession;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Registra o actualiza la sesión activa del usuario en la tabla user_sessions.
 * Ejecuta solo para usuarios autenticados, cada vez que hacen una petición web.
 */
class TrackUserSession
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Solo rastrear sesiones de usuarios autenticados con sesión activa
        if ($request->user() && $request->hasSession()) {
            $sessionId = $request->session()->getId();

            UserSession::updateOrCreate(
                ['session_id' => $sessionId],
                [
                    'user_id'        => $request->user()->id,
                    'ip_address'     => $request->ip(),
                    'user_agent'     => substr($request->userAgent() ?? '', 0, 500),
                    'last_active_at' => now(),
                    'created_at'     => now(),   // solo se aplica al crear (updateOrCreate no sobreescribe si ya existe)
                ]
            );
        }

        return $response;
    }
}
