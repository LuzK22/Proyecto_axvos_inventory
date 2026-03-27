<?php

namespace App\Http\Controllers;

use App\Models\BackupLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class BackupController extends Controller
{
    /** Vista principal del módulo de respaldo */
    public function index()
    {
        $backups = BackupLog::latest()->paginate(15);
        $lastOk  = BackupLog::where('status', 'completed')->latest()->first();
        $pending = BackupLog::where('status', 'running')->count();

        // Archivos reales en disco local
        $diskFiles = collect(Storage::disk('local')->allFiles(config('app.name')))
            ->filter(fn($f) => str_ends_with($f, '.zip'))
            ->map(fn($f) => [
                'path' => $f,
                'name' => basename($f),
                'size' => Storage::disk('local')->size($f),
                'date' => Storage::disk('local')->lastModified($f),
            ])
            ->sortByDesc('date')
            ->values();

        return view('admin.backup.index', compact('backups', 'lastOk', 'pending', 'diskFiles'));
    }

    /** Genera un backup manual inmediato */
    public function run(Request $request)
    {
        $log = BackupLog::create([
            'filename'      => 'Generando...',
            'size_bytes'    => 0,
            'type'          => 'manual',
            'status'        => 'running',
            'disk'          => 'local',
            'triggered_by'  => Auth::id(),
        ]);

        try {
            Artisan::call('backup:run --only-db');

            // Encontrar el archivo recién creado
            $files = collect(Storage::disk('local')->allFiles(config('app.name')))
                ->filter(fn($f) => str_ends_with($f, '.zip'))
                ->sortByDesc(fn($f) => Storage::disk('local')->lastModified($f))
                ->values();

            $latest = $files->first();
            $size   = $latest ? Storage::disk('local')->size($latest) : 0;

            $log->update([
                'filename'   => $latest ? basename($latest) : 'backup-manual.zip',
                'size_bytes' => $size,
                'status'     => 'completed',
            ]);

            activity()
                ->causedBy(Auth::user())
                ->withProperties(['filename' => $log->filename, 'size_mb' => round($size / 1048576, 2)])
                ->log('Respaldo manual generado');

            return redirect()->route('admin.backup.index')
                ->with('success', '✅ Respaldo generado correctamente: ' . $log->filename);

        } catch (\Throwable $e) {
            $log->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return redirect()->route('admin.backup.index')
                ->with('error', '❌ Error al generar el respaldo: ' . $e->getMessage());
        }
    }

    /** Descarga un archivo de respaldo */
    public function download(Request $request, string $filename): StreamedResponse
    {
        $path = config('app.name') . '/' . $filename;

        abort_unless(Storage::disk('local')->exists($path), 404, 'Respaldo no encontrado.');

        // Registrar descarga en el log
        $log = BackupLog::where('filename', $filename)->latest()->first();
        if ($log) {
            $log->update([
                'downloaded_at' => now(),
                'downloaded_by' => Auth::id(),
            ]);
        }

        activity()
            ->causedBy(Auth::user())
            ->withProperties(['filename' => $filename])
            ->log('Respaldo descargado');

        return Storage::disk('local')->download($path, $filename);
    }

    /** Elimina un registro del historial (no el archivo) */
    public function destroy(BackupLog $backup)
    {
        $filename = $backup->filename;
        $path     = config('app.name') . '/' . $filename;

        // Eliminar archivo físico si existe
        if (Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }

        $backup->delete();

        activity()
            ->causedBy(Auth::user())
            ->withProperties(['filename' => $filename])
            ->log('Respaldo eliminado');

        return redirect()->route('admin.backup.index')
            ->with('success', 'Respaldo eliminado del historial.');
    }
}
