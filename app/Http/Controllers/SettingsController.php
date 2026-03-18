<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

class SettingsController extends Controller
{
    public function index()
    {
        $settings = Setting::orderBy('group')->orderBy('key')->get()->keyBy('key');
        return view('admin.settings', compact('settings'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'company_name'     => 'required|string|max:255',
            'company_nit'      => 'nullable|string|max:50',
            'company_address'  => 'nullable|string|max:500',
            'company_phone'    => 'nullable|string|max:50',
            'company_email'    => 'nullable|email|max:255',
            'system_name'      => 'required|string|max:255',
            'system_slogan'    => 'nullable|string|max:255',
            'acta_header_text' => 'nullable|string',
            'acta_footer_text' => 'nullable|string',
            'company_logo'     => 'nullable|image|mimes:png,jpg,jpeg,svg|max:2048',
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
        ];

        foreach ($textFields as $field) {
            if ($request->has($field)) {
                Setting::set($field, $request->input($field, ''));
            }
        }

        // Textarea — guardamos aunque estén vacíos
        Setting::set('acta_header_text', $request->input('acta_header_text', ''));
        Setting::set('acta_footer_text', $request->input('acta_footer_text', ''));

        // Limpiar caché de settings
        Cache::flush();

        return redirect()->route('admin.settings')
            ->with('success', 'Configuración guardada correctamente.');
    }
}
