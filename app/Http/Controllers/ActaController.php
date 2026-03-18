<?php

namespace App\Http\Controllers;

use App\Mail\ActaSigningRequest;
use App\Models\Acta;
use App\Models\ActaSignature;
use App\Models\Assignment;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

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
            'assignment.activeAssets.asset.assetType',
            'generatedBy',
            'signatures.signerUser',
        ]);

        return view('documents.actas.show', compact('acta'));
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
        // Categoría: TI (activos tecnológicos) u OTRO (otros activos)
        $category = strtoupper($request->input('category', 'TI'));
        if (!in_array($category, ['TI', 'OTRO'])) $category = 'TI';

        // Verificar que la asignación tiene activos de esa categoría
        $hasAssets = $assignment->assignmentAssets()
            ->whereNull('returned_at')
            ->whereHas('asset.type', fn($q) => $q->where('category', $category))
            ->exists();

        if (!$hasAssets) {
            return back()->with('error', "Esta asignación no tiene activos de categoría {$category}.");
        }

        // Si ya existe un acta activa para esta asignación+categoría, redirigir
        $existing = $assignment->actas()
            ->where('asset_category', $category)
            ->whereNotIn('status', [Acta::STATUS_ANULADA])
            ->latest()
            ->first();

        if ($existing) {
            return redirect()
                ->route('actas.show', $existing)
                ->with('info', 'Ya existe un acta activa para esta asignación.');
        }

        $acta = Acta::create([
            'assignment_id'  => $assignment->id,
            'acta_number'    => Acta::generateActaNumber($category),
            'acta_type'      => 'entrega',
            'asset_category' => $category,
            'status'         => Acta::STATUS_BORRADOR,
            'generated_by'   => auth()->id(),
        ]);

        // Crear firmas pendientes: colaborador + responsable (usuario actual)
        $collaborator = $assignment->collaborator;

        ActaSignature::create([
            'acta_id'      => $acta->id,
            'signer_role'  => 'collaborator',
            'signer_name'  => $collaborator->full_name,
            'signer_email' => $collaborator->email,
            'token'        => ActaSignature::generateToken(),
            'token_expires_at' => now()->addDays(7),
        ]);

        ActaSignature::create([
            'acta_id'       => $acta->id,
            'signer_role'   => 'responsible',
            'signer_name'   => auth()->user()->name,
            'signer_email'  => auth()->user()->email,
            'signer_user_id' => auth()->id(),
            'token'         => ActaSignature::generateToken(),
            'token_expires_at' => now()->addDays(7),
        ]);

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

        // Validar emails ingresados
        $request->validate([
            'emails'   => ['required', 'array'],
            'emails.*' => ['required', 'email'],
        ], [
            'emails.*.required' => 'Todos los correos son obligatorios.',
            'emails.*.email'    => 'Ingresa un correo válido.',
        ]);

        $customEmails = $request->input('emails', []);

        $sent = 0;
        foreach ($acta->signatures as $signature) {
            if ($signature->isSigned()) continue;

            // Usar el email del formulario si fue enviado para este firmante
            $email = $customEmails[$signature->id] ?? $signature->signer_email;

            if (!$email) continue;

            // Actualizar el email en la firma si fue cambiado
            if ($email !== $signature->signer_email) {
                $signature->update(['signer_email' => $email]);
            }

            Mail::to($email)->send(new ActaSigningRequest($signature));
            $sent++;
        }

        $acta->update([
            'status'  => Acta::STATUS_ENVIADA,
            'sent_at' => now(),
        ]);

        return back()->with('success', "Acta enviada para firma — {$sent} correo(s) despachados.");
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

        $signature->load(['acta.assignment.collaborator', 'acta.assignment.activeAssets.asset.assetType']);

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
        $acta->load([
            'assignment.collaborator',
            'assignment.activeAssets.asset.assetType',
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
        $acta->load([
            'assignment.collaborator',
            'assignment.activeAssets.asset.assetType',
            'generatedBy',
            'signatures',
        ]);

        $pdf = Pdf::loadView('documents.actas.pdf', compact('acta'))
            ->setPaper('letter', 'portrait');

        return $pdf->stream($acta->acta_number . '.pdf');
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
