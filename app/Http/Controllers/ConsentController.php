<?php

namespace App\Http\Controllers;

use App\Http\Middleware\RequiresConsent;
use App\Models\DataConsent;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ConsentController extends Controller
{
    /** Muestra la política de tratamiento de datos para aceptación */
    public function show(): View
    {
        $version  = Setting::get('consent_policy_version', RequiresConsent::CURRENT_VERSION);
        $company  = Setting::get('company_name', config('app.name'));

        return view('consent.index', compact('version', 'company'));
    }

    /** Registra la aceptación del usuario */
    public function accept(Request $request): RedirectResponse
    {
        $request->validate([
            'accepted' => ['required', 'accepted'],
        ], [
            'accepted.required' => 'Debes aceptar la política de tratamiento de datos para continuar.',
            'accepted.accepted' => 'Debes aceptar la política de tratamiento de datos para continuar.',
        ]);

        $version = Setting::get('consent_policy_version', RequiresConsent::CURRENT_VERSION);

        DataConsent::recordFor(
            user:          $request->user(),
            type:          'data_treatment',
            policyVersion: $version,
            ip:            $request->ip(),
            userAgent:     $request->userAgent(),
        );

        activity()
            ->causedBy($request->user())
            ->withProperties(['policy_version' => $version, 'ip' => $request->ip()])
            ->log('Consentimiento de tratamiento de datos aceptado');

        return redirect()->intended(route('dashboard'))
            ->with('success', 'Consentimiento registrado. Bienvenido.');
    }

    /** Muestra el historial de consentimientos del usuario (para su propio perfil) */
    public function history(Request $request): View
    {
        $consents = DataConsent::where('user_id', $request->user()->id)
            ->orderByDesc('accepted_at')
            ->get();

        return view('consent.history', compact('consents'));
    }
}
