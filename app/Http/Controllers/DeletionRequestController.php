<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetEvent;
use App\Models\DeletionRequest;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeletionRequestController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | SOLICITAR ELIMINACIÓN (cualquier usuario con permiso de asignación)
    | No elimina el activo directamente — crea una solicitud que el Aprobador revisa
    |--------------------------------------------------------------------------
    */
    public function store(Request $request, Asset $asset)
    {
        $request->validate([
            'reason' => 'required|in:danado,obsoleto,perdido,venta,donacion,otro',
            'notes'  => 'required|string|max:1000',
        ]);

        // Evitamos duplicados: si ya hay una solicitud pendiente para este activo, no creamos otra
        $existing = DeletionRequest::where('asset_id', $asset->id)
            ->where('status', DeletionRequest::STATUS_PENDING)
            ->first();

        if ($existing) {
            return back()->with('error', 'Ya existe una solicitud de baja pendiente para este activo.');
        }

        DeletionRequest::create([
            'asset_id'     => $asset->id,
            'requested_by' => auth()->id(),
            'reason'       => $request->reason,
            'notes'        => $request->notes,
            'status'       => DeletionRequest::STATUS_PENDING,
        ]);

        return back()->with('success', 'Solicitud de baja enviada. Quedará pendiente de aprobación.');
    }

    /*
    |--------------------------------------------------------------------------
    | LISTADO (solo para el rol Aprobador)
    | Muestra todas las solicitudes con filtro por estado
    |--------------------------------------------------------------------------
    */
    public function index(Request $request)
    {
        $filter = $request->get('filter', 'pending');

        $query = DeletionRequest::with(['asset.type', 'asset.branch', 'requestedBy', 'resolvedBy'])
            ->latest();

        // Si el filtro es "all" mostramos todo; de lo contrario filtramos por estado
        if ($filter !== 'all') {
            $query->where('status', $filter);
        }

        $requests     = $query->paginate(20)->withQueryString();
        $pendingCount = DeletionRequest::where('status', 'pending')->count();

        return view('admin.deletion-requests.index', compact('requests', 'filter', 'pendingCount'));
    }

    /*
    |--------------------------------------------------------------------------
    | APROBAR → cambia el activo a estado Baja y registra el evento
    |--------------------------------------------------------------------------
    */
    public function approve(Request $request, DeletionRequest $deletionRequest)
    {
        // No procesamos solicitudes que ya fueron resueltas
        if ($deletionRequest->status !== DeletionRequest::STATUS_PENDING) {
            return back()->with('error', 'Esta solicitud ya fue procesada.');
        }

        DB::transaction(function () use ($deletionRequest) {
            $asset      = $deletionRequest->asset;
            $bajaStatus = Status::where('name', 'Baja')->firstOrFail();

            $deletionRequest->update([
                'status'      => DeletionRequest::STATUS_APPROVED,
                'resolved_by' => auth()->id(),
                'resolved_at' => now(),
            ]);

            // Dejamos trazabilidad en el historial del activo
            AssetEvent::log($asset, 'baja', 'Baja', [
                'notes' => "Baja aprobada. Motivo: {$deletionRequest->reason_label}. {$deletionRequest->notes}",
            ]);

            $asset->update(['status_id' => $bajaStatus->id]);
        });

        return back()->with('success', 'Solicitud aprobada. El activo fue dado de baja.');
    }

    /*
    |--------------------------------------------------------------------------
    | RECHAZAR → cierra la solicitud sin tocar el activo
    |--------------------------------------------------------------------------
    */
    public function reject(Request $request, DeletionRequest $deletionRequest)
    {
        $request->validate([
            'rejection_notes' => 'required|string|max:500',
        ]);

        if ($deletionRequest->status !== DeletionRequest::STATUS_PENDING) {
            return back()->with('error', 'Esta solicitud ya fue procesada.');
        }

        // El activo no se modifica; solo cerramos la solicitud con el motivo del rechazo
        $deletionRequest->update([
            'status'          => DeletionRequest::STATUS_REJECTED,
            'resolved_by'     => auth()->id(),
            'resolved_at'     => now(),
            'rejection_notes' => $request->rejection_notes,
        ]);

        return back()->with('success', 'Solicitud rechazada.');
    }
}
