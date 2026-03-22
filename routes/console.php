<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// ─── Backup programado ─────────────────────────────────────────────────────
// Ejecuta backup completo de MySQL a las 2 AM cada día.
// Requiere: spatie/laravel-backup y configuración en config/backup.php.
Schedule::command('backup:run --only-db')
    ->dailyAt('02:00')
    ->onFailure(fn() => logger()->error('Backup diario falló'))
    ->appendOutputTo(storage_path('logs/backup.log'));

// Limpia backups viejos (retiene según config/backup.php max_storage_in_megabytes)
Schedule::command('backup:clean')
    ->dailyAt('02:30')
    ->appendOutputTo(storage_path('logs/backup.log'));
