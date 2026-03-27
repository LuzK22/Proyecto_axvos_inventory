<?php

namespace App\Services;

use App\Models\ExportLog;

/**
 * Servicio de registro de exportaciones — ISO 27001.
 *
 * Uso en cualquier controlador que genere reportes/descargas:
 *
 *   ExportLogger::log('assets', 'xlsx', $request->all(), $query->count());
 */
class ExportLogger
{
    /**
     * Registra la exportación y devuelve el log creado.
     *
     * @param string $entityType  Nombre de la entidad exportada (assets, collaborators, etc.)
     * @param string $format      Formato del archivo (xlsx, pdf, csv)
     * @param array  $filters     Filtros aplicados a la consulta
     * @param int    $rows        Número de registros exportados
     */
    public static function log(
        string $entityType,
        string $format    = 'xlsx',
        array  $filters   = [],
        int    $rows      = 0,
    ): ExportLog {
        // Limpiar filtros sensibles antes de persistir
        $safeFilters = collect($filters)
            ->except(['_token', 'password', 'document'])
            ->filter(fn($v) => !is_null($v) && $v !== '')
            ->toArray();

        $log = ExportLog::record(
            entityType: $entityType,
            format:     $format,
            filters:    $safeFilters,
            rowsExported: $rows,
        );

        // También registrar en el activity log de Spatie para visibilidad en auditoría
        activity()
            ->causedBy(auth()->user())
            ->withProperties([
                'entity_type'   => $entityType,
                'format'        => $format,
                'rows_exported' => $rows,
                'ip'            => request()->ip(),
            ])
            ->log("Exportación de {$entityType} ({$rows} registros, formato {$format})");

        return $log;
    }
}
