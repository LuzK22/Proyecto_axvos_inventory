<?php

namespace App\Http\Controllers;

use App\Http\Controllers\TwoFactorController;
use App\Models\UserSession;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class SecurityController extends Controller
{
    /**
     * Muestra el Centro de Seguridad del usuario autenticado.
     */
    public function index(Request $request)
    {
        $user = auth()->user();

        // Sesiones activas — ordenadas por actividad reciente
        $currentSessionId = $request->session()->getId();
        $sessions = $user->sessions()
            ->orderByDesc('last_active_at')
            ->get()
            ->map(function (UserSession $s) use ($currentSessionId) {
                $s->is_current = $s->session_id === $currentSessionId;
                return $s;
            });

        // Actividad reciente del log (últimas 15 entradas)
        $recentActivity = Activity::causedBy($user)
            ->latest()
            ->take(15)
            ->get();

        // Puntuación de seguridad (0-100)
        $score = $this->securityScore($user);

        // Códigos de recuperación flash (solo se muestran al activar 2FA)
        $recoveryCodes = session('recovery_codes', []);

        return view('security.index', compact(
            'user',
            'sessions',
            'currentSessionId',
            'recentActivity',
            'score',
            'recoveryCodes',
        ));
    }

    /**
     * Revoca una sesión específica del usuario.
     */
    public function revokeSession(Request $request, int $id)
    {
        $session = auth()->user()->sessions()->findOrFail($id);

        // No permitir revocar la sesión actual por esta vía
        if ($session->session_id === $request->session()->getId()) {
            return back()->withErrors(['session' => 'No puedes revocar tu sesión actual desde aquí. Usa "Cerrar sesión".']);
        }

        $session->delete();

        activity()->causedBy(auth()->user())
            ->withProperties(['ip' => $session->ip_address, 'device' => $session->deviceName()])
            ->log('Sesión remota revocada');

        return back()->with('success', 'Sesión cerrada correctamente.');
    }

    /**
     * Revoca todas las sesiones del usuario excepto la actual.
     */
    public function revokeAllOtherSessions(Request $request)
    {
        $currentSessionId = $request->session()->getId();
        $count = auth()->user()->sessions()
            ->where('session_id', '!=', $currentSessionId)
            ->count();

        auth()->user()->sessions()
            ->where('session_id', '!=', $currentSessionId)
            ->delete();

        activity()->causedBy(auth()->user())
            ->withProperties(['count' => $count])
            ->log('Todas las otras sesiones revocadas');

        return back()->with('success', "Se cerraron {$count} sesión(es) remota(s).");
    }

    /**
     * Regenera los códigos de recuperación 2FA.
     * Los códigos anteriores quedan invalidados.
     */
    public function regenerateRecoveryCodes()
    {
        $user = auth()->user();

        if (!$user->hasTwoFactorEnabled()) {
            return back()->withErrors(['error' => 'No tienes 2FA activo.']);
        }

        $codes = TwoFactorController::generateRecoveryCodes();
        $user->update(['two_factor_recovery_codes' => json_encode($codes)]);

        activity()->causedBy($user)->performedOn($user)
            ->log('Códigos de recuperación 2FA regenerados');

        return back()->with('recovery_codes', $codes)
            ->with('success', 'Códigos de recuperación regenerados. Guárdalos en un lugar seguro.');
    }

    // ─── Helper ───────────────────────────────────────────────────────────────

    /**
     * Calcula una puntuación de seguridad entre 0 y 100.
     * - 2FA activo: +50 pts
     * - Contraseña actualizada en los últimos 90 días: +30 pts
     * - Tiene códigos de recuperación: +20 pts
     */
    private function securityScore($user): int
    {
        $score = 0;

        if ($user->hasTwoFactorEnabled()) {
            $score += 50;
        }

        if (!$user->passwordExpired(90)) {
            $score += 30;
        }

        if ($user->hasTwoFactorRecoveryCodes()) {
            $score += 20;
        }

        return $score;
    }
}
