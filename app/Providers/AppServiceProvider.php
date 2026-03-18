<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // ── Logo dinámico desde Configuración ─────────────────────────
        // Si la empresa subió su logo en Administración → Configuración,
        // reemplaza el logo del sidebar de AdminLTE automáticamente.
        try {
            if (\Schema::hasTable('settings')) {
                $logo = \App\Models\Setting::where('key', 'company_logo')->value('value');

                if ($logo) {
                    // Reemplaza el logo en el sidebar
                    config(['adminlte.logo_img'    => $logo]);
                    config(['adminlte.logo_img_xl' => $logo]);
                }

                // Nombre del sistema dinámico (solo el título del browser, no el logo)
                $systemName = \App\Models\Setting::where('key', 'system_name')->value('value');
                if ($systemName) {
                    config(['adminlte.title' => $systemName]);
                    // El logo visual (sidebar) mantiene sus colores definidos en este config
                }
            }
        } catch (\Exception $e) {
            // Si la BD no está lista aún (migraciones), no rompe la app
        }

        // ── Compartir configuración con todas las vistas ───────────────
        View::composer('*', function ($view) {
            try {
                if (\Schema::hasTable('settings')) {
                    $view->with('appSettings', \App\Models\Setting::all()->keyBy('key'));
                }
            } catch (\Exception $e) {
                $view->with('appSettings', collect());
            }
        });
    }
}
