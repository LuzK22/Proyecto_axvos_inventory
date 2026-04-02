<?php

namespace App\Http\Controllers;

use App\Mail\ActaPdfFinalMail;
use App\Models\Acta;
use App\Models\ActaFieldValue;
use App\Models\ActaSignature;
use App\Models\Assignment;
use App\Models\Loan;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Symfony\Component\Process\Process;

class ActaController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | LISTADO DE ACTAS
    |--------------------------------------------------------------------------
    */

    /**
     * Actas pendientes de firma
     */
    public function index(Request $request)
    {
        $filter   = $request->get('filter', 'all');
        $typeTab  = $request->get('type', 'all'); // entrega | devolucion | baja | all
        $category = $request->get('category', null); // 'TI', 'OTRO', or null (all)
        if (!in_array($category, ['TI', 'OTRO'])) {
            $category = null;
        }

        $query = Acta::with(['assignment.collaborator', 'generatedBy', 'signatures'])
            ->latest();

        // Filtro por estado
        if ($filter === 'pending') {
            $query->whereNotIn('status', [Acta::STATUS_COMPLETADA, Acta::STATUS_ANULADA]);
        } elseif ($filter === 'signed') {
            $query->where('status', Acta::STATUS_COMPLETADA);
        } elseif ($filter === 'draft') {
            $query->where('status', Acta::STATUS_BORRADOR);
        }

        // Filtro por tipo de acta
        $validTypes = [Acta::TYPE_ENTREGA, Acta::TYPE_DEVOLUCION, Acta::TYPE_BAJA, 'prestamo'];
        if (in_array($typeTab, $validTypes)) {
            $query->where('acta_type', $typeTab);
        }

        // Filtro por categoría de activo
        if ($category !== null) {
            $query->where('asset_category', $category);
        }

        $actas = $query->paginate(20)->withQueryString();

        // Conteos por tipo para los tabs (filtrados por categoría si aplica)
        $counts = [
            'all'                 => Acta::when($category, fn($q) => $q->where('asset_category', $category))->count(),
            Acta::TYPE_ENTREGA    => Acta::where('acta_type', Acta::TYPE_ENTREGA)->when($category, fn($q) => $q->where('asset_category', $category))->count(),
            Acta::TYPE_DEVOLUCION => Acta::where('acta_type', Acta::TYPE_DEVOLUCION)->when($category, fn($q) => $q->where('asset_category', $category))->count(),
            'prestamo'            => Acta::where('acta_type', 'prestamo')->when($category, fn($q) => $q->where('asset_category', $category))->count(),
            Acta::TYPE_BAJA       => Acta::where('acta_type', Acta::TYPE_BAJA)->when($category, fn($q) => $q->where('asset_category', $category))->count(),
        ];

        return view('documents.actas.index', compact('actas', 'filter', 'typeTab', 'counts', 'category'));
    }

    /**
     * Detalle de un acta
     */
    public function show(Acta $acta)
    {
        $acta->load([
            'assignment.collaborator',
            'assignment.area',
            'assignment.assignmentAssets.asset.type',
            'collaborator',             // actas consolidadas (assignment_id nullable)
            'assignments.collaborator', // pivot acta_assignments
            'loan.asset.type',          // actas de préstamo
            'loan.asset.branch',
            'loan.collaborator',
            'loan.destinationBranch',
            'generatedBy',
            'signatures.signerUser',
            'fieldValues',
        ]);

        $template = $acta->activeExcelTemplate();
        if ($template) {
            $template->load('fields');
        }

        $actaAssets = $acta->scopedAssignmentAssets();
        $manual = $acta->fieldValues->pluck('value', 'field_key')->toArray();

        // Solo mostrar campos NO auto-completados y NO iterables (los que debe completar el gestor)
        $editableFields = collect($template?->fields ?? [])
            ->where('is_iterable', false)
            ->filter(fn($f) => !$f->is_auto)
            ->map(function ($field) use ($acta, $manual) {
                $default = $manual[$field->field_key] ?? $this->resolveBaseFieldValue($acta, $field->field_key);
                return [
                    'key'        => $field->field_key,
                    'label'      => $field->field_label,
                    'value'      => $default,
                    'input_type' => $this->guessFieldInputType($field->field_key, $field->field_label),
                ];
            })
            ->values();

        // Valores auto para mostrar como preview en la vista (solo lectura)
        $autoPreview = collect($template?->fields ?? [])
            ->where('is_iterable', false)
            ->filter(fn($f) => $f->is_auto)
            ->map(fn($f) => [
                'label' => $f->field_label,
                'value' => $this->resolveBaseFieldValue($acta, $f->field_key),
            ])
            ->values();

        $mySignature = $acta->signatures->where('signer_user_id', auth()->id())->first()
            ?? $acta->signatures->where('signer_role', 'responsible')->where('signed_at', null)->first();

        return view('documents.actas.show', compact(
            'acta',
            'template',
            'actaAssets',
            'editableFields',
            'autoPreview',
            'mySignature'
        ));
}

    /*
    |--------------------------------------------------------------------------
    | EXCEL (plantilla configurable)
    |--------------------------------------------------------------------------
    */

    public function generateExcelDraft(Request $request, Acta $acta)
    {
        $acta->load([
            'assignment.collaborator',
            'assignment.area',
            'assignment.assignmentAssets.asset.type',
            'assignment.assignmentAssets.asset.branch',
            'loan.asset.type',
            'loan.asset.branch',
            'loan.collaborator',
            'generatedBy',
            'fieldValues',
        ]);

        $template = $acta->activeExcelTemplate();
        if (!$template) {
            return back()->with('error', 'No hay una plantilla activa para este tipo/categoría de acta. Súbela en Admin → Plantillas.');
        }

        $template->load('fields');

        $assets = $acta->scopedAssignmentAssets()->values();
        if ($assets->isEmpty()) {
            return back()->with('error', 'Esta acta no tiene activos de la categoría correspondiente.');
        }

        // Resolver colaborador (préstamo no tiene assignment)
        $collaborator  = $acta->assignment?->collaborator ?? $acta->loan?->collaborator;
        $baseDate      = $acta->assignment?->assignment_date ?? $acta->loan?->start_date;
        $recipientName = $collaborator?->full_name
            ?? ($acta->assignment?->area ? 'Área: ' . $acta->assignment->area->name : '—');
        $branchName    = $collaborator?->branch?->name
            ?? $acta->assignment?->assignmentAssets->first()?->asset?->branch?->name
            ?? $acta->loan?->asset?->branch?->name
            ?? '';
        $cityName      = $collaborator?->branch?->city
            ?? $acta->assignment?->assignmentAssets->first()?->asset?->branch?->city
            ?? $acta->loan?->asset?->branch?->city
            ?? '';
        $userDomain    = $this->resolveActaUserDomain($acta, $collaborator, $assets);

        // Mapa de valores base: todos los campos que AXVOS conoce
        $base = [
            'acta_number'           => $acta->acta_number,
            'acta_type'             => $acta->type_label,
            'asset_category'        => strtoupper($acta->asset_category ?? 'TI'),
            'delivery_date'         => now()->format('d/m/Y'),
            'assignment_date'       => optional($baseDate)->format('d/m/Y') ?? '',
            'collaborator_name'     => $collaborator?->full_name ?? '',
            'collaborator_document' => $collaborator?->document ?? '',
            'collaborator_position' => $collaborator?->position ?? '',
            'collaborator_email'    => $collaborator?->email ?? '',
            'user_domain'           => $userDomain,
            'username_domain'       => $userDomain,
            'area_name'             => $acta->assignment?->area?->name ?? '',
            'branch_name'           => $branchName,
            'city_name'             => $cityName,
            'city'                  => $cityName,
            'recipient_name'        => $recipientName,
            'responsible_name'      => $acta->generatedBy?->name ?? '',
            'responsible_email'     => $acta->generatedBy?->email ?? '',
        ];

        // Valores manuales guardados desde la edición web
        $manual = $acta->fieldValues->pluck('value', 'field_key')->toArray();

        // Combinar: manual tiene prioridad sobre base
        $allValues = array_merge($base, $manual);

        $ext = strtolower($template->template_type ?? 'xlsx');

        if ($ext === 'docx') {
            return $this->generateDocxDraft($acta, $template, $allValues, $assets);
        }

        return $this->generateXlsxDraft($acta, $template, $allValues, $assets);
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Generación XLSX — búsqueda y reemplazo de {{marcadores}}
    // ─────────────────────────────────────────────────────────────────────────

    private function generateXlsxDraft(Acta $acta, $template, array $allValues, $assets)
    {
        $spreadsheet = IOFactory::load(storage_path('app/' . $template->file_path));
        $sheet       = $spreadsheet->getActiveSheet();

        // ── Paso 1: Detectar la fila de la tabla de activos ──────────────────
        $iterRowIndex = null;
        $iterColMap   = []; // 'B' => 'asset_serial', etc.

        foreach ($sheet->getRowIterator() as $rowIndex => $row) {
            $cellIter = $row->getCellIterator();
            $cellIter->setIterateOnlyExistingCells(true);
            $rowColMap = [];

            foreach ($cellIter as $cell) {
                $val = trim((string) ($cell->getValue() ?? ''));
                if (preg_match('/^\{\{(\w+)\}\}$/', $val, $m)) {
                    $info = \App\Models\ActaExcelTemplate::KNOWN_FIELDS[$m[1]] ?? null;
                    if ($info && ($info['iterable'] ?? false)) {
                        $rowColMap[$cell->getColumn()] = $m[1];
                    }
                }
            }

            if (!empty($rowColMap)) {
                $iterRowIndex = $rowIndex;
                $iterColMap   = $rowColMap;
                break;
            }
        }

        // ── Paso 2: Reemplazar {{marcadores}} no iterables en todas las celdas ─
        foreach ($sheet->getRowIterator() as $rowIndex => $row) {
            if ($rowIndex === $iterRowIndex) {
                continue; // la fila de activos se trata aparte
            }

            $cellIter = $row->getCellIterator();
            $cellIter->setIterateOnlyExistingCells(true);

            foreach ($cellIter as $cell) {
                $val = (string) ($cell->getValue() ?? '');
                if (!str_contains($val, '{{')) {
                    continue;
                }

                $newVal = preg_replace_callback('/\{\{(\w+)\}\}/', function ($m) use ($allValues) {
                    return $allValues[$m[1]] ?? '';
                }, $val);

                if ($newVal !== $val) {
                    $cell->setValue($newVal);
                }
            }
        }

        // ── Paso 3: Llenar tabla de activos ──────────────────────────────────
        if ($iterRowIndex !== null) {
            foreach ($assets as $idx => $aa) {
                $targetRow = $iterRowIndex + $idx;
                foreach ($iterColMap as $col => $key) {
                    $sheet->setCellValue($col . $targetRow, $this->resolveIterableValue($key, $aa));
                }
            }
        } elseif ($template->assets_start_row) {
            // Fallback: usar el mapeo clásico de la BD (cell_ref = "B{row}")
            $startRow = $template->assets_start_row;
            foreach ($template->fields->where('is_iterable', true) as $field) {
                foreach ($assets as $idx => $aa) {
                    $row  = $startRow + $idx;
                    $cell = str_replace('{row}', (string) $row, $field->cell_ref);
                    $sheet->setCellValue($cell, $this->resolveIterableValue($field->field_key, $aa));
                }
            }
        }

        // ── Guardar ──────────────────────────────────────────────────────────
        // Autodiligenciado inteligente por etiquetas visibles (sin marcadores).
        $this->fillXlsxByLabelHeuristics($sheet, $allValues, $assets);

        $filename = $acta->acta_number . '-draft.xlsx';
        $path     = 'actas/excel-drafts/' . $filename;

        Storage::makeDirectory('actas/excel-drafts');
        $tmp    = tempnam(sys_get_temp_dir(), 'acta_xlsx_');
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($tmp);
        Storage::put($path, file_get_contents($tmp));
        @unlink($tmp);

        $acta->update(['xlsx_draft_path' => $path]);

        return redirect()->route('actas.show', $acta)->with('success', 'Documento generado correctamente.');
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Generación DOCX — búsqueda y reemplazo de {{marcadores}}
    // ─────────────────────────────────────────────────────────────────────────

    private function generateDocxDraft(Acta $acta, $template, array $allValues, $assets)
    {
        if (!class_exists(\PhpOffice\PhpWord\IOFactory::class)) {
            return back()->with('error', 'Soporte para .docx no disponible. Ejecuta: composer require phpoffice/phpword');
        }

        $phpWord = \PhpOffice\PhpWord\IOFactory::load(storage_path('app/' . $template->file_path));

        // Reemplazos simples en todo el documento
        $replacements = [];
        foreach ($allValues as $key => $value) {
            $replacements['{{' . $key . '}}'] = (string) $value;
        }

        // Reemplazar en secciones/párrafos/tablas
        foreach ($phpWord->getSections() as $section) {
            $this->replaceDocxSection($section, $replacements, $assets);
        }

        $filename = $acta->acta_number . '-draft.docx';
        $path     = 'actas/excel-drafts/' . $filename;

        Storage::makeDirectory('actas/excel-drafts');
        $tmp    = tempnam(sys_get_temp_dir(), 'acta_docx_');
        $writer = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $writer->save($tmp);
        Storage::put($path, file_get_contents($tmp));
        @unlink($tmp);

        $acta->update(['xlsx_draft_path' => $path]);

        return redirect()->route('actas.show', $acta)->with('success', 'Documento Word generado correctamente.');
    }

    private function replaceDocxSection($section, array $replacements, $assets): void
    {
        foreach ($section->getElements() as $element) {
            if ($element instanceof \PhpOffice\PhpWord\Element\Table) {
                foreach ($element->getRows() as $rowIdx => $row) {
                    $isAssetRow = false;
                    // Detectar si la fila tiene marcadores iterables
                    foreach ($row->getCells() as $cell) {
                        foreach ($cell->getElements() as $par) {
                            $text = $this->docxElementText($par);
                            foreach (\App\Models\ActaExcelTemplate::KNOWN_FIELDS as $key => $info) {
                                if (($info['iterable'] ?? false) && str_contains($text, '{{' . $key . '}}')) {
                                    $isAssetRow = true;
                                    break 3;
                                }
                            }
                        }
                    }

                    if ($isAssetRow) {
                        // Llenar la primera fila con activo 0, y simplemente reemplazar
                        $firstAsset = $assets->first();
                        if ($firstAsset) {
                            $assetRepl = [];
                            foreach (\App\Models\ActaExcelTemplate::KNOWN_FIELDS as $key => $info) {
                                if ($info['iterable'] ?? false) {
                                    $assetRepl['{{' . $key . '}}'] = $this->resolveIterableValue($key, $firstAsset);
                                }
                            }
                            $this->replaceDocxRow($row, array_merge($replacements, $assetRepl));
                        }
                    } else {
                        $this->replaceDocxRow($row, $replacements);
                    }
                }
            } elseif ($element instanceof \PhpOffice\PhpWord\Element\TextRun ||
                      $element instanceof \PhpOffice\PhpWord\Element\Paragraph) {
                $this->replaceDocxParagraph($element, $replacements);
            }
        }
    }

    private function replaceDocxRow($row, array $replacements): void
    {
        foreach ($row->getCells() as $cell) {
            foreach ($cell->getElements() as $par) {
                $this->replaceDocxParagraph($par, $replacements);
            }
        }
    }

    private function replaceDocxParagraph($element, array $replacements): void
    {
        if (!method_exists($element, 'getElements')) {
            return;
        }
        foreach ($element->getElements() as $child) {
            if ($child instanceof \PhpOffice\PhpWord\Element\Text) {
                $text    = $child->getText();
                $newText = strtr($text, $replacements);
                if ($newText !== $text) {
                    $child->setText($newText);
                }
            }
        }
    }

    private function docxElementText($element): string
    {
        $text = '';
        if (!method_exists($element, 'getElements')) {
            return $text;
        }
        foreach ($element->getElements() as $child) {
            if ($child instanceof \PhpOffice\PhpWord\Element\Text) {
                $text .= $child->getText();
            }
        }
        return $text;
    }

    public function downloadExcelDraft(Acta $acta)
    {
        if (!$acta->xlsx_draft_path || !Storage::exists($acta->xlsx_draft_path)) {
            return back()->with('error', 'No hay Excel borrador generado para esta acta.');
        }

        return response()->download(storage_path('app/' . $acta->xlsx_draft_path), basename($acta->xlsx_draft_path));
    }

    public function uploadExcelFinal(Request $request, Acta $acta)
    {
        if (in_array($acta->status, [Acta::STATUS_COMPLETADA, Acta::STATUS_ANULADA], true)) {
            return back()->with('error', 'No se puede reemplazar el Excel de un acta completada o anulada.');
        }

        $request->validate([
            'excel_final' => 'required|file|mimes:xlsx|max:10240',
        ]);

        $this->deleteStoredFilePaths([$acta->xlsx_final_path, $acta->pdf_path]);

        $filename = $acta->acta_number . '-final.xlsx';
        $path = $request->file('excel_final')->storeAs('actas/excel-final', $filename);

        $acta->update([
            'xlsx_final_path' => $path,
            'pdf_path'        => null,
        ]);

        return redirect()
            ->route('actas.show', $acta)
            ->with('success', 'Excel final subido correctamente.');
    }

    public function downloadExcelFinal(Acta $acta)
    {
        if (!$acta->xlsx_final_path || !Storage::exists($acta->xlsx_final_path)) {
            return back()->with('error', 'No hay Excel final subido para esta acta.');
        }

        return response()->download(storage_path('app/' . $acta->xlsx_final_path), basename($acta->xlsx_final_path));
    }

    public function updateWebFields(Request $request, Acta $acta)
    {
        if (in_array($acta->status, [Acta::STATUS_COMPLETADA, Acta::STATUS_ANULADA], true)) {
            return back()->with('error', 'No se pueden editar campos en una acta completada o anulada.');
        }

        $template = $acta->activeExcelTemplate();
        if (!$template) {
            return back()->with('error', 'No existe una plantilla activa para editar esta acta desde la web.');
        }

        $template->load('fields');

        $allowedKeys = $template->fields
            ->where('is_iterable', false)
            ->pluck('field_key')
            ->all();

        $data = $request->input('fields', []);

        foreach ($allowedKeys as $key) {
            ActaFieldValue::updateOrCreate(
                ['acta_id' => $acta->id, 'field_key' => $key],
                [
                    'value'      => $data[$key] ?? null,
                    'updated_by' => auth()->id(),
                ]
            );
        }

        $this->resetGeneratedDocuments($acta);

        // Al guardar campos web, regeneramos de inmediato el Excel borrador
        // para que el usuario pueda continuar directo con PDF final.
        return $this->generateExcelDraft($request, $acta);
    }

    private function fillXlsxByLabelHeuristics($sheet, array $allValues, $assets): void
    {
        $maxRow      = (int) $sheet->getHighestRow();
        $maxColIndex = Coordinate::columnIndexFromString($sheet->getHighestColumn());

        // 1) Cabecera: buscar etiquetas visibles y escribir el dato a la derecha.
        for ($row = 1; $row <= $maxRow; $row++) {
            for ($col = 1; $col <= $maxColIndex; $col++) {
                $raw = trim((string) ($sheet->getCellByColumnAndRow($col, $row)->getValue() ?? ''));
                if ($raw === '' || strlen($raw) > 70) {
                    continue;
                }

                $label = $this->normalizeTemplateLabel($raw);
                $value = null;

                if (str_contains($label, 'nombre completo') && str_contains($label, 'recibe')) {
                    $value = $allValues['recipient_name'] ?? null;
                } elseif (str_contains($label, 'nombre completo') && str_contains($label, 'entrega')) {
                    $value = $allValues['responsible_name'] ?? null;
                } elseif (str_contains($label, 'nombre completo')) {
                    $value = $allValues['collaborator_name'] ?? null;
                } elseif (str_contains($label, 'documento')) {
                    $value = $allValues['collaborator_document'] ?? null;
                } elseif (str_contains($label, 'correo')) {
                    $value = $allValues['collaborator_email'] ?? null;
                } elseif (str_contains($label, 'usuario dominio') || str_contains($label, 'usuario - dominio')) {
                    $value = $allValues['user_domain'] ?? null;
                } elseif ($label === 'area' || str_contains($label, ' area')) {
                    $value = $allValues['area_name'] ?? null;
                } elseif (str_contains($label, 'cargo')) {
                    $value = $allValues['collaborator_position'] ?? null;
                } elseif (str_contains($label, 'ciudad')) {
                    $value = $allValues['city_name'] ?? null;
                } elseif (str_contains($label, 'sucursal') || str_contains($label, 'sede')) {
                    $value = $allValues['branch_name'] ?? null;
                } elseif (str_starts_with($label, 'fecha')) {
                    $value = $allValues['delivery_date'] ?? null;
                }

                if ($value !== null && $value !== '') {
                    $this->writeValueToRightEmptyCell($sheet, $row, $col, (string) $value, $maxColIndex);
                }
            }
        }

        // 2) Tabla de activos: detectar encabezados y llenar filas.
        $headerAliases = [
            'asset_type'        => ['descripcion', 'tipo'],
            'asset_brand_model' => ['marca y modelo', 'marca modelo'],
            'asset_serial'      => ['serial', 'serie'],
            'asset_hostname'    => ['nombre de equipo', 'nombre del equipo', 'nombre equipo'],
            'fixed_asset_code'  => ['activo fijo', 'placa'],
            'asset_status'      => ['estado'],
        ];

        $headerRow = null;
        $colMap    = [];
        for ($row = 1; $row <= $maxRow; $row++) {
            $rowMap = [];
            for ($col = 1; $col <= $maxColIndex; $col++) {
                $label = $this->normalizeTemplateLabel((string) ($sheet->getCellByColumnAndRow($col, $row)->getValue() ?? ''));
                if ($label === '') {
                    continue;
                }
                foreach ($headerAliases as $field => $aliases) {
                    foreach ($aliases as $alias) {
                        if ($label === $alias || str_contains($label, $alias)) {
                            $rowMap[$col] = $field;
                            break 2;
                        }
                    }
                }
            }

            $fieldsFound = array_values(array_unique(array_values($rowMap)));
            if (count($fieldsFound) >= 3 && in_array('asset_serial', $fieldsFound, true)) {
                $headerRow = $row;
                $colMap    = $rowMap;
                break;
            }
        }

        if ($headerRow !== null && !empty($colMap)) {
            foreach ($assets as $idx => $assignmentAsset) {
                $targetRow = $headerRow + 1 + $idx;
                foreach ($colMap as $col => $fieldKey) {
                    $coordinate = Coordinate::stringFromColumnIndex((int) $col) . $targetRow;
                    $value      = $this->resolveIterableValue($fieldKey, $assignmentAsset);
                    if ($value === '') {
                        continue;
                    }
                    $existing = trim((string) ($sheet->getCell($coordinate)->getValue() ?? ''));
                    if ($existing === '') {
                        $sheet->setCellValue($coordinate, $value);
                    }
                }
            }
        }
    }

    private function writeValueToRightEmptyCell($sheet, int $row, int $labelCol, string $value, int $maxColIndex): void
    {
        for ($col = $labelCol + 1; $col <= $maxColIndex; $col++) {
            $current = trim((string) ($sheet->getCellByColumnAndRow($col, $row)->getValue() ?? ''));
            if ($current !== '') {
                continue;
            }
            $sheet->setCellValue(Coordinate::stringFromColumnIndex($col) . $row, $value);
            return;
        }
    }

    private function normalizeTemplateLabel(string $text): string
    {
        $normalized = strtolower(trim($text));
        $normalized = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ñ', 'Á', 'É', 'Í', 'Ó', 'Ú', 'Ñ'],
            ['a', 'e', 'i', 'o', 'u', 'n', 'a', 'e', 'i', 'o', 'u', 'n'],
            $normalized
        );
        $normalized = preg_replace('/[^a-z0-9]+/i', ' ', $normalized) ?? '';
        return trim(preg_replace('/\s+/', ' ', $normalized) ?? '');
    }

    private function deriveUserDomain(?string $email): string
    {
        if (!$email || !str_contains($email, '@')) {
            return '';
        }

        [$user] = explode('@', $email, 2);
        return trim($user);
    }

    private function resolveActaUserDomain(Acta $acta, $collaborator = null, $assets = null): string
    {
        $assetsCollection = $assets ?? $acta->scopedAssignmentAssets();
        $assetDomainUser = trim((string) (
            $assetsCollection->first()?->asset?->domain_user
            ?? $acta->loan?->asset?->domain_user
            ?? ''
        ));

        if ($assetDomainUser !== '') {
            return $assetDomainUser;
        }

        return $this->deriveUserDomain($collaborator?->email);
    }

    private function resolveIterableValue(string $key, $assignmentAsset): string
    {
        $asset = $assignmentAsset->asset;
        return match ($key) {
            'asset_internal_code', 'asset_code'
                                    => (string) ($asset?->internal_code ?? ''),
            'asset_category_col'    => (string) ($asset?->type?->category ?? ''),
            'asset_category'        => (string) ($asset?->type?->category ?? ''),
            'asset_type'            => (string) ($asset?->type?->name ?? ''),
            'asset_brand'           => (string) ($asset?->brand ?? ''),
            'asset_model'           => (string) ($asset?->model ?? ''),
            'asset_brand_model'     => trim(($asset?->brand ?? '') . ' ' . ($asset?->model ?? '')),
            'asset_serial'          => (string) ($asset?->serial ?? ''),
            'asset_hostname'        => (string) ($asset?->hostname ?? ''),
            'asset_domain_user'     => (string) ($asset?->domain_user ?? ''),
            'asset_tag', 'inventory_tag'
                                    => (string) ($asset?->asset_tag ?? ''),
            'fixed_asset_code'      => (string) ($asset?->fixed_asset_code ?? ''),
            'asset_status'          => (string) ($asset?->status?->name ?? ''),
            'asset_quantity'        => '1',
            default                 => '',
        };
    }

    private function resolveBaseFieldValue(Acta $acta, string $key): string
    {
        $acta->loadMissing([
            'assignment.collaborator',
            'assignment.area',
            'loan.collaborator',
            'generatedBy',
        ]);

        // Actas de préstamo no tienen assignment — resolver desde loan
        $collaborator  = $acta->assignment?->collaborator ?? $acta->loan?->collaborator;
        $recipientName = $collaborator?->full_name
            ?? ($acta->assignment?->area ? ('Área: ' . $acta->assignment->area->name) : '—');
        $baseDate = $acta->assignment?->assignment_date ?? $acta->loan?->start_date;

        $branchName = $collaborator?->branch?->name
            ?? $acta->loan?->asset?->branch?->name
            ?? '';
        $cityName = $collaborator?->branch?->city
            ?? $acta->loan?->asset?->branch?->city
            ?? '';
        $userDomain = $this->resolveActaUserDomain($acta, $collaborator);

        return (string) match ($key) {
            'acta_number'           => $acta->acta_number,
            'acta_type'             => $acta->type_label,
            'asset_category'        => $acta->asset_category_label,
            'collaborator_name'     => $collaborator?->full_name ?? '',
            'collaborator_document' => $collaborator?->document ?? '',
            'collaborator_position' => $collaborator?->position ?? '',
            'collaborator_email'    => $collaborator?->email ?? '',
            'user_domain'           => $userDomain,
            'username_domain'       => $userDomain,
            'area_name'             => $acta->assignment?->area?->name ?? '',
            'branch_name'           => $branchName,
            'city_name'             => $cityName,
            'city'                  => $cityName,
            'recipient_name'        => $recipientName,
            'assignment_date'       => optional($baseDate)->format('Y-m-d') ?? '',
            'delivery_date'         => now()->format('Y-m-d'),
            'responsible_name'      => $acta->generatedBy?->name ?? '',
            'responsible_email'     => $acta->generatedBy?->email ?? '',
            default                 => '',
        };
    }

    private function guessFieldInputType(string $key, string $label): string
    {
        $haystack = strtolower($key . ' ' . $label);

        if (str_contains($haystack, 'fecha') || str_contains($haystack, '_date') || str_contains($haystack, 'date_')) {
            return 'date';
        }

        if (str_contains($haystack, 'nota') || str_contains($haystack, 'observ') || str_contains($haystack, 'claus') || str_contains($haystack, 'footer') || str_contains($haystack, 'header')) {
            return 'textarea';
        }

        if (str_contains($haystack, 'email') || str_contains($haystack, 'correo')) {
            return 'email';
        }

        return 'text';
    }

    private function resetGeneratedDocuments(Acta $acta): void
    {
        $this->deleteStoredFilePaths([
            $acta->xlsx_draft_path,
            $acta->xlsx_final_path,
            $acta->pdf_path,
        ]);

        $acta->update([
            'xlsx_draft_path' => null,
            'xlsx_final_path' => null,
            'pdf_path'        => null,
        ]);
    }

    private function deleteStoredFilePaths(array $paths): void
    {
        foreach (array_filter(array_unique($paths)) as $path) {
            if (Storage::exists($path)) {
                Storage::delete($path);
            }
        }
    }

    /*
    |--------------------------------------------------------------------------
    | GENERAR ACTA
    |--------------------------------------------------------------------------
    */

    /**
     * Genera un acta de entrega para una asignación dada
     */
    public function generate(Request $request, Assignment $assignment)
    {
        // Categoría: TI, OTRO o ALL/MIXTA
        $category = strtoupper($request->input('category', 'TI'));
        if (!in_array($category, ['TI', 'OTRO', 'ALL'])) $category = 'TI';

        $acta = Acta::generateDeliveryForAssignment($assignment, $category, auth()->user());

        if (!$acta) {
            return back()->with('error', "Esta asignación no tiene activos compatibles para el tipo de acta {$category}.");
        }

        if (!$acta->wasRecentlyCreated) {
            return redirect()
                ->route('actas.show', $acta)
                ->with('info', 'Ya existe un acta activa para esta asignación.');
        }

        return redirect()
            ->route('actas.show', $acta)
            ->with('success', 'Acta generada correctamente. Puede enviarla para firma.');
    }

    /**
     * Genera (o reutiliza) un Acta de Préstamo para un Loan dado.
     * Accesible desde la vista show del préstamo TI o Otros Activos.
     */
    public function generateFromLoan(Loan $loan)
    {
        $loan->load(['asset.type', 'collaborator', 'destinationBranch']);

        $acta = Acta::generateForLoan($loan, auth()->user());

        $message = $acta->wasRecentlyCreated
            ? 'Carta de préstamo generada correctamente.'
            : 'Ya existe una carta de préstamo activa para este préstamo.';

        return redirect()->route('actas.show', $acta)->with('success', $message);
    }

    /*
    |--------------------------------------------------------------------------
    | ENVIAR PARA FIRMA
    |--------------------------------------------------------------------------
    */

    /**
     * Envía el email de firma al colaborador (y opcionalmente al responsable)
     */
    public function send(Request $request, Acta $acta)
    {
        if ($acta->status === Acta::STATUS_ANULADA) {
            return back()->with('error', 'No se puede enviar un acta anulada.');
        }

        // Requerimos PDF final para adjuntar
        if (!$acta->pdf_path || !Storage::exists($acta->pdf_path)) {
            return back()->with('error', 'Primero genera el PDF final para poder enviarlo por correo.');
        }

        // Validar emails ingresados
        $request->validate([
            'emails'   => ['required', 'array'],
            'emails.*' => ['required', 'email'],
            'third_email' => ['nullable', 'email'],
        ], [
            'emails.*.required' => 'Todos los correos son obligatorios.',
            'emails.*.email'    => 'Ingresa un correo válido.',
        ]);

        $customEmails = $request->input('emails', []);
        $thirdEmail   = $request->input('third_email');

        $recipients = [];
        foreach ($acta->signatures as $signature) {
            if ($signature->isSigned()) continue;

            // Usar el email del formulario si fue enviado para este firmante
            $email = $customEmails[$signature->id] ?? $signature->signer_email;

            if (!$email) continue;

            // Actualizar el email en la firma si fue cambiado
            if ($email !== $signature->signer_email) {
                $signature->update(['signer_email' => $email]);
            }

            $recipients[] = $email;
        }

        if ($thirdEmail) {
            $recipients[] = $thirdEmail;
        }

        $recipients = array_values(array_unique(array_filter($recipients)));
        if (empty($recipients)) {
            return back()->with('error', 'No hay correos destino para enviar.');
        }

        $pdfAbsolutePath = storage_path('app/' . $acta->pdf_path);
        Mail::to($recipients)->send(new ActaPdfFinalMail($acta, $pdfAbsolutePath));

        $acta->update([
            'status'  => Acta::STATUS_ENVIADA,
            'sent_at' => now(),
        ]);

        return back()->with('success', 'Acta enviada por correo con el PDF adjunto.');
    }

    /*
    |--------------------------------------------------------------------------
    | FIRMA PÚBLICA (sin login)
    |--------------------------------------------------------------------------
    */

    /**
     * Muestra la página pública de firma con el token
     */
    public function signPage(string $token)
    {
        $signature = ActaSignature::where('token', $token)->firstOrFail();

        if ($signature->isSigned()) {
            return view('sign.acta_already_signed', compact('signature'));
        }

        if (!$signature->isTokenValid()) {
            return view('sign.acta_expired', compact('signature'));
        }

        $signature->load([
            'acta.assignment.collaborator',
            'acta.assignment.area',
            'acta.assignment.assignmentAssets.asset.type',
            'acta.generatedBy',
            'acta.signatures',
        ]);

        return view('sign.acta', compact('signature'));
    }

    /**
     * Guarda la firma enviada desde la página pública
     */
    public function submitSign(Request $request, string $token)
    {
        $signature = ActaSignature::where('token', $token)->firstOrFail();

        if ($signature->isSigned()) {
            return response()->json(['error' => 'Este enlace ya fue utilizado.'], 422);
        }

        if (!$signature->isTokenValid()) {
            return response()->json(['error' => 'El enlace ha expirado.'], 422);
        }

        $request->validate([
            'signature_type' => 'required|in:drawn,image',
            'signature_data' => 'required|string',
        ]);

        $signature->update([
            'signed_at'      => now(),
            'signature_type' => $request->signature_type,
            'signature_data' => $request->signature_data,
            'signed_ip'      => $request->ip(),
        ]);

        // Actualizar el estado del acta
        $signature->acta->load('signatures');
        $signature->acta->refreshStatus();

        return response()->json(['success' => true]);
    }

    /*
    |--------------------------------------------------------------------------
    | FIRMA INTERNA (usuario autenticado)
    |--------------------------------------------------------------------------
    */

    /**
     * El gestor/responsable firma directamente desde el sistema
     */
    public function signInternal(Request $request, Acta $acta)
    {
        $request->validate([
            'signature_type' => 'required|in:drawn,image',
            'signature_data' => 'required|string',
        ]);

        // Buscar firma del responsable para este usuario
        $signature = $acta->signatures()
            ->where('signer_user_id', auth()->id())
            ->first();

        if (!$signature) {
            // También permitir si es el responsible sin user_id asignado
            $signature = $acta->signatures()
                ->where('signer_role', 'responsible')
                ->whereNull('signed_at')
                ->first();
        }

        if (!$signature) {
            return back()->with('error', 'No se encontró una firma pendiente para su usuario.');
        }

        if ($signature->isSigned()) {
            return back()->with('error', 'Usted ya firmó este acta.');
        }

        $signature->update([
            'signed_at'      => now(),
            'signature_type' => $request->signature_type,
            'signature_data' => $request->signature_data,
            'signed_ip'      => $request->ip(),
        ]);

        $acta->load('signatures');
        $acta->refreshStatus();

        return back()->with('success', 'Firma guardada correctamente.');
    }

    /*
    |--------------------------------------------------------------------------
    | PDF
    |--------------------------------------------------------------------------
    */

    /**
     * Descarga o visualiza el PDF del acta
     */
    public function downloadPdf(Acta $acta)
    {
        // Si ya existe un PDF final guardado, lo descargamos tal cual
        if ($acta->pdf_path && Storage::exists($acta->pdf_path)) {
            return response()->download(storage_path('app/' . $acta->pdf_path), basename($acta->pdf_path));
        }

        $acta->load([
            'assignment.collaborator',
            'assignment.area',
            'assignment.assignmentAssets.asset.type',
            'generatedBy',
            'signatures',
        ]);

        $pdf = Pdf::loadView('documents.actas.pdf', compact('acta'))
            ->setPaper('letter', 'portrait');

        $filename = $acta->acta_number . '.pdf';

        return $pdf->download($filename);
    }

    /**
     * Vista previa del PDF en el navegador
     */
    public function previewPdf(Acta $acta)
    {
        // Si ya existe un PDF final guardado, lo mostramos en el navegador
        if ($acta->pdf_path && Storage::exists($acta->pdf_path)) {
            return response()->file(storage_path('app/' . $acta->pdf_path));
        }

        $acta->load([
            'assignment.collaborator',
            'assignment.area',
            'assignment.assignmentAssets.asset.type',
            'generatedBy',
            'signatures',
        ]);

        $pdf = Pdf::loadView('documents.actas.pdf', compact('acta'))
            ->setPaper('letter', 'portrait');

        return $pdf->stream($acta->acta_number . '.pdf');
    }

    /**
     * Genera el PDF final a partir del Excel (final si existe, si no borrador).
     * Requiere LibreOffice (soffice) disponible en el sistema.
     */
    public function generatePdfFinal(Request $request, Acta $acta)
    {
        $xlsxPath = $acta->xlsx_final_path ?: $acta->xlsx_draft_path;
        if (!$xlsxPath || !Storage::exists($xlsxPath)) {
            return back()->with('error', 'Primero genera el Excel borrador y/o sube el Excel final.');
        }

        $soffice = env('LIBREOFFICE_BIN', 'soffice');

        $inputFile  = storage_path('app/' . $xlsxPath);
        $outDir     = storage_path('app/actas/pdf-final');
        $tmpOutDir  = storage_path('app/actas/pdf-final/tmp');

        if (!is_dir($tmpOutDir)) {
            @mkdir($tmpOutDir, 0777, true);
        }

        // Convertir Excel → PDF (headless)
        $process = new Process([
            $soffice,
            '--headless',
            '--nologo',
            '--nofirststartwizard',
            '--convert-to', 'pdf',
            '--outdir', $tmpOutDir,
            $inputFile,
        ]);
        $process->setTimeout(120);
        $process->run();

        if (!$process->isSuccessful()) {
            return back()->with('error',
                'No se pudo generar el PDF desde Excel. Verifica que LibreOffice esté instalado y que el comando "soffice" exista, o define LIBREOFFICE_BIN en .env.'
            );
        }

        // LibreOffice genera un PDF con el mismo nombre base del archivo
        $expected = pathinfo($inputFile, PATHINFO_FILENAME) . '.pdf';
        $generated = $tmpOutDir . DIRECTORY_SEPARATOR . $expected;

        if (!file_exists($generated)) {
            return back()->with('error', 'La conversión se ejecutó pero no se encontró el PDF resultante.');
        }

        if (!is_dir($outDir)) {
            @mkdir($outDir, 0777, true);
        }

        $finalName = $acta->acta_number . '.pdf';
        $finalPath = 'actas/pdf-final/' . $finalName;
        Storage::put($finalPath, file_get_contents($generated));
        @unlink($generated);

        $acta->update(['pdf_path' => $finalPath]);

        return redirect()
            ->route('actas.show', $acta)
            ->with('success', 'PDF final generado correctamente desde el Excel.');
    }

    /*
    |--------------------------------------------------------------------------
    | ANULAR
    |--------------------------------------------------------------------------
    */

    public function void(Acta $acta)
    {
        if ($acta->status === Acta::STATUS_COMPLETADA) {
            return back()->with('error', 'No se puede anular un acta completada.');
        }

        $acta->update(['status' => Acta::STATUS_ANULADA]);

        return back()->with('success', 'Acta anulada correctamente.');
    }
}
