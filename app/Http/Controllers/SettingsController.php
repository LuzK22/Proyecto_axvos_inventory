<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = Setting::orderBy('group')->orderBy('key')->get()->keyBy('key');
        $roles    = Role::orderBy('name')->pluck('name');
        return view('admin.settings', compact('settings', 'roles'));
    }

    public function update(Request $request)
    {
        $allRoles = Role::pluck('name')->toArray();

        $request->validate([
            'company_name'              => 'required|string|max:255',
            'company_nit'               => 'nullable|string|max:50',
            'company_address'           => 'nullable|string|max:500',
            'company_phone'             => 'nullable|string|max:50',
            'company_email'             => 'nullable|email|max:255',
            'system_name'               => 'required|string|max:255',
            'system_slogan'             => 'nullable|string|max:255',
            'acta_header_text'          => 'nullable|string',
            'acta_footer_text'          => 'nullable|string',
            'company_logo'              => 'nullable|image|mimes:png,jpg,jpeg,svg|max:2048',
            'user_email_domains'        => 'nullable|string|max:500',
            // 2FA
            'security_2fa_enabled'      => 'nullable|in:0,1',
            'security_2fa_required_roles' => 'nullable|array',
            'security_2fa_required_roles.*' => 'in:' . implode(',', $allRoles),
            'security_2fa_enforcement'  => 'nullable|in:required,recommended',
            'security_2fa_grace_days'   => 'nullable|integer|min:0|max:365',
        ]);

        // ── Guardar logo si se subió ───────────────────────────────────
        if ($request->hasFile('company_logo')) {
            $path = $request->file('company_logo')->store('public/logos');
            Setting::set('company_logo', Storage::url($path));
        }

        // ── Guardar campos de texto ────────────────────────────────────
        $textFields = [
            'company_name', 'company_nit', 'company_address',
            'company_phone', 'company_email',
            'system_name', 'system_slogan',
            'user_email_domains',
        ];

        foreach ($textFields as $field) {
            if ($request->has($field)) {
                Setting::set($field, $request->input($field, ''));
            }
        }

        // Textarea — guardamos aunque estén vacíos
        Setting::set('acta_header_text', $request->input('acta_header_text', ''));
        Setting::set('acta_footer_text', $request->input('acta_footer_text', ''));

        // ── Guardar configuración 2FA ──────────────────────────────────
        Setting::set('security_2fa_enabled', $request->has('security_2fa_enabled') ? '1' : '0');

        $selectedRoles = $request->input('security_2fa_required_roles', []);
        Setting::set('security_2fa_required_roles', implode(',', array_filter($selectedRoles)));

        Setting::set('security_2fa_enforcement', $request->input('security_2fa_enforcement', 'required'));
        Setting::set('security_2fa_grace_days', (string) (int) $request->input('security_2fa_grace_days', 0));

        // Limpiar caché de settings
        Cache::flush();

        activity()
            ->causedBy(auth()->user())
            ->withProperties([
                'empresa'          => $request->company_name,
                '2fa_habilitado'   => $request->has('security_2fa_enabled') ? 'sí' : 'no',
                '2fa_roles'        => implode(', ', $request->input('security_2fa_required_roles', [])),
                '2fa_modo'         => $request->input('security_2fa_enforcement', 'required'),
            ])
            ->log('Configuración del sistema actualizada');

        return redirect()->route('admin.settings')
            ->with('success', 'Configuración guardada correctamente.');
    }
}
