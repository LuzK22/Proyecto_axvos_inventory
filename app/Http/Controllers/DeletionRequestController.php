<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetEvent;
use App\Models\AssetType;
use App\Models\Branch;
use App\Models\Collaborator;
use App\Models\DeletionRequest;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DeletionRequestController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | VISTAS TECH: listado y formulario de solicitudes para usuarios TI / OTRO
    |--------------------------------------------------------------------------
    */

    /** Listado de solicitudes TI visible para el rol técnico */
    public function techIndex(Request $request)
    {
        return $this->disposalIndex($request, 'TI', 'tech.disposals.index');
    }

    /** Formulario nueva solicitud TI */
    public function techCreate()
    {
        $assets   = Asset::with(['type','status','branch'])
            ->whereHas('type', fn($q) => $q->where('category', 'TI'))
            ->whereDoesntHave('deletionRequests', fn($q) => $q->where('status', 'pending'))
            ->whereHas('status', fn($q) => $q->where('name', '!=', 'Baja'))
            ->orderBy('internal_code')
            ->get();
        $branches = Branch::where('active', true)->orderBy('name')->get();

        return view('tech.disposals.create', compact('assets', 'branches'));
    }

    /** Listado de solicitudes OTRO visible para el rol de activos */
    public function assetsIndex(Request $request)
    {
        return $this->disposalIndex($request, 'OTRO', 'assets.disposals.index');
    }

    /** Formulario nueva solicitud OTRO */
    public function assetsCreate()
    {
        $assets   = Asset::with(['type','status','branch'])
            ->whereHas('type', fn($q) => $q->where('category', 'OTRO'))
            ->whereDoesntHave('deletionRequests', fn($q) => $q->where('status', 'pending'))
            ->whereHas('status', fn($q) => $q->where('name', '!=', 'Baja'))
            ->orderBy('internal_code')
            ->get();
        $branches = Branch::where('active', true)->orderBy('name')->get();

        return view('assets.disposals.create', compact('assets', 'branches'));
    }

    /** Helper compartido para los índices de solicitudes por categoría */
    private function disposalIndex(Request $request, string $category, string $selfRoute)
    {
        $filter = $request->get('filter', 'pending');

        $query = DeletionRequest::with(['asset.type','asset.branch','requestedBy','resolvedBy'])
            ->whereHas('asset.type', fn($q) => $q->where('category', $category))
            ->latest();

        if ($filter !== 'all') {
            $query->where('status', $filter);
        }

        $requests     = $query->paginate(20)->withQueryString();
        $pendingCount = DeletionRequest::whereHas('asset.type', fn($q) => $q->where('category', $category))
            ->where('status', 'pending')->count();

        return view('tech.disposals.index', compact('requests', 'filter', 'pendingCount', 'category', 'selfRoute'));
    }

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

        // Bloquear aprobación si el activo está actualmente asignado
        $deletionRequest->load('asset.status');
        if ($deletionRequest->asset->status?->name === 'Asignado') {
            return back()->with('error',
                "No se puede aprobar la baja: el activo <strong>{$deletionRequest->asset->internal_code}</strong> ".
                "está asignado actualmente. Primero retírelo de la asignación activa."
            );
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
