<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class ActaExcelTemplate extends Model
{
    protected $table = 'acta_excel_templates';

    protected $fillable = [
        'name',
        'file_path',
        'template_type',
        'acta_type',
        'asset_category',
        'active',
        'assets_start_row',
        'uploaded_by',
    ];

    protected $casts = [
        'active'           => 'boolean',
        'assets_start_row' => 'integer',
    ];

    /*
    |--------------------------------------------------------------------------
    | Campos conocidos por AXVOS
    | auto = true  → el sistema lo rellena solo
    | auto = false → el gestor lo completa desde la web
    | iterable     → va en la tabla de activos (una fila por activo)
    |--------------------------------------------------------------------------
    */
    public const KNOWN_FIELDS = [
        // ── Cabecera (no iterables) ──────────────────────────────────────────
        'acta_number'           => ['label' => 'Número de acta',         'auto' => true,  'iterable' => false],
        'acta_type'             => ['label' => 'Tipo de acta',           'auto' => true,  'iterable' => false],
        'asset_category'        => ['label' => 'Categoría activo',       'auto' => true,  'iterable' => false],
        'delivery_date'         => ['label' => 'Fecha del acta',         'auto' => true,  'iterable' => false],
        'assignment_date'       => ['label' => 'Fecha de asignación',    'auto' => true,  'iterable' => false],
        'collaborator_name'     => ['label' => 'Nombre colaborador',     'auto' => true,  'iterable' => false],
        'collaborator_document' => ['label' => 'Documento de identidad', 'auto' => true,  'iterable' => false],
        'collaborator_position' => ['label' => 'Cargo',                  'auto' => true,  'iterable' => false],
        'collaborator_email'    => ['label' => 'Correo electrónico',     'auto' => true,  'iterable' => false],
        'area_name'             => ['label' => 'Área',                   'auto' => true,  'iterable' => false],
        'branch_name'           => ['label' => 'Sucursal',               'auto' => true,  'iterable' => false],
        'city_name'             => ['label' => 'Ciudad',                 'auto' => true,  'iterable' => false],
        'user_domain'           => ['label' => 'Usuario - Dominio',      'auto' => true,  'iterable' => false],
        'responsible_name'      => ['label' => 'Nombre responsable TI',  'auto' => true,  'iterable' => false],
        'responsible_email'     => ['label' => 'Correo responsable TI',  'auto' => true,  'iterable' => false],
        'recipient_name'        => ['label' => 'Nombre receptor',        'auto' => true,  'iterable' => false],
        'incident_number'       => ['label' => 'Numero de incidente',    'auto' => false, 'iterable' => false],
        'software_os'           => ['label' => 'Sistema operativo',      'auto' => false, 'iterable' => false],
        'software_office'       => ['label' => 'Office',                 'auto' => false, 'iterable' => false],
        'software_antivirus'    => ['label' => 'Antivirus',              'auto' => false, 'iterable' => false],
        'software_apps'         => ['label' => 'Aplicativos',            'auto' => false, 'iterable' => false],
        'software_browsers'     => ['label' => 'Navegadores',            'auto' => false, 'iterable' => false],
        'software_others'       => ['label' => 'Software otros',         'auto' => false, 'iterable' => false],
        'observations'          => ['label' => 'Observaciones',          'auto' => false, 'iterable' => false],
        // ── Tabla de activos (iterables) ─────────────────────────────────────
        'asset_type'            => ['label' => 'Tipo / Descripción',     'auto' => true,  'iterable' => true],
        'asset_brand'           => ['label' => 'Marca',                  'auto' => true,  'iterable' => true],
        'asset_model'           => ['label' => 'Modelo',                 'auto' => true,  'iterable' => true],
        'asset_brand_model'     => ['label' => 'Marca y Modelo',         'auto' => true,  'iterable' => true],
        'asset_serial'          => ['label' => 'Serial',                 'auto' => true,  'iterable' => true],
        'asset_hostname'        => ['label' => 'Nombre del equipo',      'auto' => true,  'iterable' => true],
        'fixed_asset_code'      => ['label' => 'Activo Fijo / Placa',    'auto' => true,  'iterable' => true],
        'asset_tag'             => ['label' => 'Etiqueta inventario',    'auto' => true,  'iterable' => true],
        'asset_status'          => ['label' => 'Estado',                 'auto' => true,  'iterable' => true],
        'asset_internal_code'   => ['label' => 'Código interno AXVOS',   'auto' => true,  'iterable' => true],
        'asset_quantity'        => ['label' => 'Cantidad',               'auto' => true,  'iterable' => true],
        'asset_category_col'    => ['label' => 'Categoría (por activo)', 'auto' => true,  'iterable' => true],
    ];

    // ─────────────────────────────────────────────────────────────────────────
    // Relations
    // ─────────────────────────────────────────────────────────────────────────

    public function fields(): HasMany
    {
        return $this->hasMany(ActaExcelTemplateField::class, 'acta_excel_template_id')
            ->orderBy('sort_order')
            ->orderBy('id');
    }

    public function uploadedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Auto-scan de marcadores {{}} en el archivo xlsx
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Escanea el archivo xlsx y devuelve todos los marcadores {{field_key}}
     * encontrados, con su celda de referencia y si son iterables o no.
     *
     * @return array  [ ['key', 'label', 'cell_ref', 'row', 'iterable', 'sort_order'], ... ]
     */
    public function scanXlsxPlaceholders(): array
    {
        $fullPath = storage_path('app/' . $this->file_path);
        if (!file_exists($fullPath)) {
            return [];
        }

        try {
            $spreadsheet = IOFactory::load($fullPath);
            $sheet       = $spreadsheet->getActiveSheet();
        } catch (\Exception $e) {
            return [];
        }

        $found = [];
        $seen  = []; // evitar duplicados del mismo key

        foreach ($sheet->getRowIterator() as $rowIndex => $row) {
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(true);

            foreach ($cellIterator as $cell) {
                $val = (string) ($cell->getValue() ?? '');
                if (!str_contains($val, '{{')) {
                    continue;
                }

                preg_match_all('/\{\{(\w+)\}\}/', $val, $matches);

                foreach ($matches[1] as $key) {
                    if (isset($seen[$key])) {
                        continue; // usar solo la primera celda donde aparece
                    }
                    $seen[$key] = true;

                    $info      = self::KNOWN_FIELDS[$key] ?? null;
                    $iterable  = (bool) ($info['iterable'] ?? false);
                    $label     = $info['label'] ?? $key;
                    $colIndex  = Coordinate::columnIndexFromString($cell->getColumn());

                    // Para iterables: cell_ref = "B{row}" (la columna, fila variable)
                    // Para no iterables: cell_ref = "B6" (celda exacta)
                    $cellRef = $iterable
                        ? ($cell->getColumn() . '{row}')
                        : $cell->getCoordinate();

                    $found[] = [
                        'key'        => $key,
                        'label'      => $label,
                        'cell_ref'   => $cellRef,
                        'row'        => $rowIndex,
                        'iterable'   => $iterable,
                        'sort_order' => ($rowIndex * 1000) + $colIndex,
                    ];
                }
            }
        }

        return $found;
    }

    /**
     * Escanea el archivo docx y devuelve marcadores {{field_key}} encontrados.
     * Requiere phpoffice/phpword.
     */
    public function scanDocxPlaceholders(): array
    {
        if (!class_exists(\PhpOffice\PhpWord\IOFactory::class)) {
            return [];
        }

        $fullPath = storage_path('app/' . $this->file_path);
        if (!file_exists($fullPath)) {
            return [];
        }

        try {
            $phpWord  = \PhpOffice\PhpWord\IOFactory::load($fullPath);
        } catch (\Exception $e) {
            return [];
        }

        $found = [];
        $seen  = [];

        foreach ($phpWord->getSections() as $section) {
            foreach ($section->getElements() as $element) {
                $this->extractDocxMarkers($element, $found, $seen);
            }
        }

        return $found;
    }

    private function extractDocxMarkers($element, array &$found, array &$seen): void
    {
        $text = '';

        if ($element instanceof \PhpOffice\PhpWord\Element\TextRun) {
            foreach ($element->getElements() as $child) {
                if ($child instanceof \PhpOffice\PhpWord\Element\Text) {
                    $text .= $child->getText();
                }
            }
        } elseif ($element instanceof \PhpOffice\PhpWord\Element\Text) {
            $text = $element->getText();
        } elseif ($element instanceof \PhpOffice\PhpWord\Element\Table) {
            foreach ($element->getRows() as $row) {
                foreach ($row->getCells() as $cell) {
                    foreach ($cell->getElements() as $child) {
                        $this->extractDocxMarkers($child, $found, $seen);
                    }
                }
            }
            return;
        }

        if (!str_contains($text, '{{')) {
            return;
        }

        preg_match_all('/\{\{(\w+)\}\}/', $text, $matches);
        foreach ($matches[1] as $key) {
            if (isset($seen[$key])) {
                continue;
            }
            $seen[$key] = true;
            $info       = self::KNOWN_FIELDS[$key] ?? null;
            $iterable   = (bool) ($info['iterable'] ?? false);

            $found[] = [
                'key'        => $key,
                'label'      => $info['label'] ?? $key,
                'cell_ref'   => $key,   // para docx no hay coordenada; usamos el key
                'row'        => 0,
                'iterable'   => $iterable,
                'sort_order' => count($found) * 10,
            ];
        }
    }
}
