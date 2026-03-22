<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class TwoFactorController extends Controller
{
    private Google2FA $google2fa;

    public function __construct()
    {
        $this->google2fa = new Google2FA();
    }

    // ─── Setup ────────────────────────────────────────────────────────────────

    /**
     * Muestra la página de configuración 2FA con QR generado en servidor.
     */
    public function setup()
    {
        $user = auth()->user();

        // Si ya tiene 2FA confirmado: redirigir al lugar correcto
        if ($user->hasTwoFactorEnabled()) {
            return session('2fa_verified')
                ? redirect()->route('dashboard')->with('info', 'Tu 2FA ya está activo.')
                : redirect()->route('2fa.verify');
        }

        // Generar y persistir el secreto si aún no existe
        if (!$user->getRawOriginal('two_factor_secret')) {
            $user->update([
                'two_factor_secret' => $this->google2fa->generateSecretKey(),
            ]);
            $user->refresh();
        }

        $secret = $user->twoFactorSecretSafe();

        if (!$secret) {
            $user->update(['two_factor_secret' => $this->google2fa->generateSecretKey()]);
            $user->refresh();
            $secret = $user->twoFactorSecretSafe();
        }

        // QR generado en servidor (SVG) — el secreto nunca sale del servidor
        $qrCodeUrl = $this->google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        $qrCode = QrCode::format('svg')
            ->size(220)
            ->errorCorrection('M')
            ->generate($qrCodeUrl);

        // Clave manual en grupos de 4 para ingreso manual en la app
        $manualKey = implode(' ', str_split($secret, 4));

        return view('auth.2fa.setup', compact('qrCode', 'manualKey'));
    }

    /**
     * Confirma el primer código TOTP, activa 2FA y genera códigos de recuperación.
     */
    public function confirm(Request $request)
    {
        $request->validate([
            'code' => ['required', 'digits:6'],
        ], [
            'code.required' => 'Ingresa el código de 6 dígitos.',
            'code.digits'   => 'El código debe tener exactamente 6 dígitos numéricos.',
        ]);

        $user   = auth()->user();
        $secret = $user->twoFactorSecretSafe();

        if (!$secret) {
            return redirect()->route('2fa.setup')
                ->withErrors(['code' => 'Secreto no encontrado. Vuelve a generar el QR.']);
        }

        if (!$this->google2fa->verifyKey($secret, $request->code)) {
            return back()->withErrors(['code' => 'Código incorrecto. Intenta de nuevo.']);
        }

        // Generar códigos de recuperación de un solo uso
        $recoveryCodes = $this->generateRecoveryCodes();

        $user->update([
            'two_factor_confirmed_at'   => now(),
            'two_factor_recovery_codes' => json_encode($recoveryCodes),
        ]);

        session(['2fa_verified' => true]);

        activity()->causedBy($user)->performedOn($user)->log('2FA activado');

        // Pasar los códigos a la sesión para mostrarlos UNA SOLA VEZ
        return redirect()->route('security.index')
            ->with('success', 'Autenticación de dos factores activada correctamente.')
            ->with('recovery_codes', $recoveryCodes);
    }

    // ─── Verificación por sesión ──────────────────────────────────────────────

    /**
     * Muestra el formulario de verificación TOTP para la sesión actual.
     */
    public function verify()
    {
        if (session('2fa_verified')) {
            return redirect()->route('dashboard');
        }

        if (!auth()->user()->hasTwoFactorEnabled()) {
            return redirect()->route('2fa.setup');
        }

        return view('auth.2fa.verify');
    }

    /**
     * Valida el código TOTP o un código de recuperación de un solo uso.
     */
    public function validateCode(Request $request)
    {
        $request->validate([
            'code' => ['required', 'string'],
        ]);

        $user   = auth()->user();
        $code   = preg_replace('/[\s\-]+/', '', strtoupper($request->code));
        $secret = $user->twoFactorSecretSafe();

        // ── Intentar con código TOTP (6 dígitos numéricos) ─────────────────
        if (ctype_digit($code) && strlen($code) === 6) {
            if ($secret && $this->google2fa->verifyKey($secret, $code)) {
                session(['2fa_verified' => true]);
                return redirect()->intended(route('dashboard'));
            }
            return back()->withErrors(['code' => 'Código incorrecto o expirado.']);
        }

        // ── Intentar con código de recuperación ─────────────────────────────
        $recoveryCodes = $user->twoFactorRecoveryCodes();
        $matchIndex    = array_search($code, $recoveryCodes);

        if ($matchIndex !== false) {
            // Eliminar el código usado (solo sirve una vez)
            array_splice($recoveryCodes, $matchIndex, 1);
            $user->update(['two_factor_recovery_codes' => json_encode($recoveryCodes)]);

            activity()->causedBy($user)->performedOn($user)
                ->log('2FA verificado con código de recuperación');

            session(['2fa_verified' => true]);
            return redirect()->intended(route('dashboard'))
                ->with('warning', 'Ingresaste con un código de recuperación. Te quedan ' . count($recoveryCodes) . ' código(s). Genera nuevos en el Centro de Seguridad.');
        }

        return back()->withErrors(['code' => 'Código incorrecto, expirado o ya utilizado.']);
    }

    // ─── Desactivar ───────────────────────────────────────────────────────────

    /**
     * Desactiva el 2FA del usuario (requiere confirmar con código TOTP actual).
     */
    public function disable(Request $request)
    {
        $request->validate([
            'code' => ['required', 'digits:6'],
        ]);

        $user   = auth()->user();
        $secret = $user->twoFactorSecretSafe();

        if (!$user->hasTwoFactorEnabled() || !$secret) {
            return back()->withErrors(['code' => 'No tienes 2FA activo.']);
        }

        if (!$this->google2fa->verifyKey($secret, $request->code)) {
            return back()->withErrors(['code' => 'Código incorrecto.']);
        }

        $user->update([
            'two_factor_secret'         => null,
            'two_factor_recovery_codes' => null,
            'two_factor_confirmed_at'   => null,
        ]);

        session()->forget('2fa_verified');

        activity()->causedBy($user)->performedOn($user)->log('2FA desactivado');

        return redirect()->route('security.index')
            ->with('success', '2FA desactivado correctamente.');
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    /**
     * Genera 8 códigos de recuperación de un solo uso en formato XXXXX-XXXXX.
     * Los códigos se guardan en texto plano en JSON (no contienen info sensible).
     * @return string[]
     */
    public static function generateRecoveryCodes(): array
    {
        return collect(range(1, 8))
            ->map(fn() => strtoupper(Str::random(5) . '-' . Str::random(5)))
            ->all();
    }
}
