<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Middleware\RequiresTwoFactor;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthenticatedSessionController extends Controller
{
    /**
     * Muestra el formulario de login.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Procesa el intento de autenticación.
     *
     * Flujo de bloqueo:
     *  - Si la cuenta está bloqueada → rechazar con tiempo restante.
     *  - Credenciales incorrectas → incrementar contador; bloquear al llegar a 3.
     *  - Credenciales correctas → resetear contador.
     *
     * Flujo 2FA post-login:
     *  - Tiene 2FA activo  → /2fa/verify
     *  - No tiene 2FA aún  → /2fa/setup
     *  - Rol sin 2FA       → dashboard
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'login'    => ['required', 'string'],
            'password' => ['required', 'string'],
        ]);

        $login = $request->login;
        $field = filter_var($login, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        // Buscar usuario para verificar bloqueo ANTES de intentar autenticar
        $candidate = User::where($field, $login)->first();

        if ($candidate && $candidate->isLocked()) {
            $minutes = $candidate->lockRemainingMinutes();
            return back()->withErrors([
                'login' => "Tu cuenta está bloqueada por {$minutes} minuto(s) debido a múltiples intentos fallidos. Contacta al administrador o espera.",
            ])->onlyInput('login');
        }

        // Intentar autenticación
        if (!Auth::attempt([$field => $login, 'password' => $request->password], $request->boolean('remember'))) {

            // Incrementar intentos fallidos si el usuario existe
            if ($candidate) {
                $candidate->incrementLoginAttempts();

                $remaining = User::MAX_LOGIN_ATTEMPTS - $candidate->fresh()->failed_login_attempts;

                if ($candidate->fresh()->isLocked()) {
                    activity()->causedBy($candidate)->performedOn($candidate)
                        ->log('Cuenta bloqueada por intentos fallidos de login');
                    return back()->withErrors([
                        'login' => 'Cuenta bloqueada por demasiados intentos fallidos. Espera ' . User::LOCKOUT_MINUTES . ' minutos o contacta al administrador.',
                    ])->onlyInput('login');
                }

                return back()->withErrors([
                    'login' => "Usuario o contraseña incorrectos. Te quedan {$remaining} intento(s) antes del bloqueo.",
                ])->onlyInput('login');
            }

            return back()->withErrors([
                'login' => 'Usuario o contraseña incorrectos.',
            ])->onlyInput('login');
        }

        $request->session()->regenerate();

        $user = Auth::user();

        // Resetear intentos fallidos en login exitoso
        $user->resetLoginAttempts();

        activity()->causedBy($user)->performedOn($user)->log('Inicio de sesión exitoso');

        // ── Flujo 2FA ──────────────────────────────────────────────────────────
        $twoFactorRoles = Setting::twoFactorEnabled()
            ? Setting::twoFactorRequiredRoles()
            : [];

        if (!empty($twoFactorRoles) && $user->hasAnyRole($twoFactorRoles)) {
            if ($user->hasTwoFactorEnabled()) {
                // ① Tiene 2FA → verificar antes de entrar
                return redirect()->route('2fa.verify');
            }
            // ② No tiene 2FA → configurar
            return redirect()->route('2fa.setup')
                ->with('info', 'Por seguridad, configura la autenticación de dos factores.');
        }

        // ③ Rol sin 2FA requerido → acceso directo
        return redirect()->intended(route('dashboard'));
    }

    /**
     * Cierra la sesión autenticada.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $user = Auth::user();

        Auth::guard('web')->logout();

        // Limpiar registro de sesión activa
        if ($user) {
            $user->sessions()->where('session_id', $request->session()->getId())->delete();
            activity()->causedBy($user)->performedOn($user)->log('Cierre de sesión');
        }

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
