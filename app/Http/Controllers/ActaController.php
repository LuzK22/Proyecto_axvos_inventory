<?php

namespace App\Http\Controllers;

use App\Mail\ActaPdfFinalMail;
use App\Models\Acta;
use App\Models\ActaFieldValue;
use App\Models\ActaSignature;
use App\Models\Assignment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
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
        $filter = $request->get('filter', 'all');

        $query = Acta::with(['assignment.collaborator', 'generatedBy', 'signatures'])
            ->latest();

        if ($filter === 'pending') {
            $query->whereNotIn('status', [Acta::STATUS_COMPLETADA, Acta::STATUS_ANULADA]);
        } elseif ($filter === 'signed') {
            $query->where('status', Acta::STATUS_COMPLETADA);
        } elseif ($filter === 'draft') {
            $query->where('status', Acta::STATUS_BORRADOR);
        }

        $actas = $query->paginate(20)->withQueryString();

        return view('documents.actas.index', compact('actas', 'filter'));
    }

    /**
     * Detalle de un acta
     */
    public function show(Acta $acta)
    {
        $acta->load([
            'assignment.collaborator',
            'assignment.assignmentAssets.asset.type',
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

        $editableFields = collect($template?->fields ?? [])
            ->where('is_iterable', false)
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

        $mySignature = $acta->signatures->where('signer_user_id', auth()->id())->first()
            ?? $acta->signatures->where('signer_role', 'responsible')->where('signed_at', null)->first();

        return view('documents.actas.show', compact('acta', 'template', 'actaAssets', 'editableFields', 'mySignature'));
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
            'generatedBy',
            'fieldValues',
        ]);

        $template = $acta->activeExcelTemplate();
        if (!$template) {
            return back()->with('error', 'No hay una plantilla Excel activa para este tipo/categoría de acta.');
        }

        $template->load('fields');

        $spreadsheet = IOFactory::load(storage_path('app/' . $template->file_path));
        $sheet = $spreadsheet->getActiveSheet();

        $category = strtoupper($acta->asset_category ?? 'TI');
        $assets = $acta->scopedAssignmentAssets()->values();

        if ($assets->isEmpty()) {
            return back()->with('error', 'Esta acta no tiene activos de la categoría correspondiente.');
        }

        // Mapa de valores base (no iterables)
        $recipientName = $acta->assignment->collaborator?->full_name
            ?? ($acta->assignment->area ? ('Área: ' . $acta->assignment->area->name) : '—');

        $base = [
            'acta_number'          => $acta->acta_number,
            'acta_type'            => $acta->acta_type,
            'asset_category'       => $category,
            'collaborator_name'    => $acta->assignment->collaborator?->full_name,
            'collaborator_document'=> $acta->assignment->collaborator?->document,
            'collaborator_email'   => $acta->assignment->collaborator?->email,
            'area_name'            => $acta->assignment->area?->name,
            'recipient_name'       => $recipientName,
            'assignment_date'      => optional($acta->assignment->assignment_date)->format('d/m/Y'),
            'delivery_date'        => now()->format('d/m/Y'),
            'responsible_name'     => $acta->generatedBy?->name,
            'responsible_email'    => $acta->generatedBy?->email,
        ];

        // Valores manuales/dinámicos guardados por acta
        $manual = $acta->fieldValues->pluck('value', 'field_key')->toArray();

        $startRow = $template->assets_start_row ?? 1;

        foreach ($template->fields as $field) {
            $key = $field->field_key;

            if ($field->is_iterable) {
                // Ej: A{row}, B{row}
                foreach ($assets as $idx => $aa) {
                    $row = $startRow + $idx;
                    $cell = str_replace('{row}', (string) $row, $field->cell_ref);
                    $sheet->setCellValue($cell, $this->resolveIterableValue($key, $aa));
                }
            } else {
                $value = $manual[$key] ?? $base[$key] ?? '';
                $sheet->setCellValue($field->cell_ref, $value);
            }
        }

        $filename = $acta->acta_number . '-draft.xlsx';
        $path = 'actas/excel-drafts/' . $filename;

        Storage::makeDirectory('actas/excel-drafts');
        $tmp = tempnam(sys_get_temp_dir(), 'acta_xlsx_');
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $writer->save($tmp);
        Storage::put($path, file_get_contents($tmp));
        @unlink($tmp);

        $acta->update(['xlsx_draft_path' => $path]);

        return redirect()
            ->route('actas.show', $acta)
            ->with('success', 'Excel borrador generado correctamente.');
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

        return redirect()
            ->route('actas.show', $acta)
            ->with('success', 'Campos del acta guardados correctamente. Ahora puedes generar el Excel final desde la web.');
    }

    private function resolveIterableValue(string $key, $assignmentAsset): string
    {
        $asset = $assignmentAsset->asset;
        return match ($key) {
            'asset_internal_code', 'asset_code' => (string) ($asset?->internal_code ?? ''),
            'asset_category' => (string) ($asset?->type?->category ?? ''),
            'asset_type' => (string) ($asset?->type?->name ?? ''),
            'asset_brand' => (string) ($asset?->brand ?? ''),
            'asset_model' => (string) ($asset?->model ?? ''),
            'asset_serial' => (string) ($asset?->serial ?? ''),
            'asset_tag', 'inventory_tag' => (string) ($asset?->asset_tag ?? ''),
            'fixed_asset_code' => (string) ($asset?->fixed_asset_code ?? ''),
            default => '',
        };
    }

    private function resolveBaseFieldValue(Acta $acta, string $key): string
    {
        $acta->loadMissing([
            'assignment.collaborator',
            'assignment.area',
            'generatedBy',
        ]);

        $recipientName = $acta->assignment->collaborator?->full_name
            ?? ($acta->assignment->area ? ('Área: ' . $acta->assignment->area->name) : '—');

        return (string) match ($key) {
            'acta_number'           => $acta->acta_number,
            'acta_type'             => $acta->type_label,
            'asset_category'        => $acta->asset_category_label,
            'collaborator_name'     => $acta->assignment->collaborator?->full_name ?? '',
            'collaborator_document' => $acta->assignment->collaborator?->document ?? '',
            'collaborator_email'    => $acta->assignment->collaborator?->email ?? '',
            'area_name'             => $acta->assignment->area?->name ?? '',
            'recipient_name'        => $recipientName,
            'assignment_date'       => optional($acta->assignment->assignment_date)->format('Y-m-d') ?? '',
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
