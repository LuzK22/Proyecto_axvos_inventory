<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Branch;
use App\Models\DeletionRequest;
use App\Models\Status;
use Illuminate\Http\Request;

class AssetController extends Controller
{
    // ─── Reglas de validación compartidas entre store y update ────────────
    private function validationRules(?int $assetId = null): array
    {
        return [
            'asset_type_id'    => 'required|exists:asset_types,id',
            'brand'            => 'required|string|max:100',
            'model'            => 'required|string|max:100',
            // El serial debe ser único; al editar excluimos el propio registro
            'serial'           => 'required|string|max:100|unique:assets,serial' . ($assetId ? ",{$assetId}" : ''),
            'asset_tag'        => 'nullable|string|max:100|unique:assets,asset_tag' . ($assetId ? ",{$assetId}" : ''),
            'fixed_asset_code' => 'nullable|string|max:100',
            'property_type'    => 'required|in:PROPIO,LEASING,ALQUILADO,OTRO',
            'purchase_value'   => 'nullable|numeric|min:0',
            'purchase_date'    => 'nullable|date|before_or_equal:today',
            'provider_name'    => 'nullable|string|max:200',
            'branch_id'        => 'required|exists:branches,id',
            'observations'     => 'nullable|string|max:2000',
        ];
    }

    /* =========================================================
     | LISTADO DE ACTIVOS TI
     ========================================================= */
    public function index(Request $request)
    {
        $query = Asset::with(['type', 'branch', 'status'])
            ->whereHas('type', fn($q) => $q->where('category', 'TI'));

        if ($q = $request->input('q')) {
            $query->where(function ($sub) use ($q) {
                $sub->where('internal_code', 'like', "%{$q}%")
                    ->orWhere('serial', 'like', "%{$q}%")
                    ->orWhere('brand', 'like', "%{$q}%")
                    ->orWhere('model', 'like', "%{$q}%")
                    ->orWhere('asset_tag', 'like', "%{$q}%")
                    ->orWhere('fixed_asset_code', 'like', "%{$q}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->whereHas('status', fn($s) => $s->where('name', $status));
        }

        if ($branch = $request->input('branch')) {
            $query->where('branch_id', $branch);
        }

        $assets   = $query->orderBy('id', 'desc')->get();
        $branches = Branch::where('active', true)->orderBy('name')->get();
        $statuses = Status::orderBy('name')->get();

        return view('tech.assets.index', compact('assets', 'branches', 'statuses'));
    }

    /* =========================================================
     | DETALLE DE ACTIVO TI
     ========================================================= */
    public function show(Asset $asset)
    {
        $asset->load(['type', 'branch', 'status', 'events.user', 'events.collaborator']);

        // Asignación activa con datos del colaborador
        $currentAssignment = $asset->currentAssignmentAsset?->load('assignment.collaborator');

        // Todo el historial de asignaciones, de más reciente a más antigua
        $assignmentHistory = $asset->assignmentAssets()
            ->with(['assignment.collaborator', 'returnedBy'])
            ->orderBy('assigned_at', 'desc')
            ->get();

        // Si hay una solicitud de eliminación pendiente la mostramos en la vista (bloquea el botón)
        $pendingDeletion = DeletionRequest::where('asset_id', $asset->id)
            ->where('status', 'pending')
            ->first();

        $branches = Branch::where('active', true)->orderBy('name')->get();

        return view('tech.assets.show', compact(
            'asset', 'currentAssignment', 'assignmentHistory',
            'pendingDeletion', 'branches'
        ));
    }

    /* =========================================================
     | FORMULARIO CREAR ACTIVO TI
     ========================================================= */
    public function create()
    {
        $types    = AssetType::where('category', 'TI')->where('active', true)->orderBy('name')->get();
        $branches = Branch::where('active', true)->orderBy('name')->get();

        return view('tech.assets.create', compact('types', 'branches'));
    }

    /* =========================================================
     | GUARDAR ACTIVO TI
     ========================================================= */
    public function store(Request $request)
    {
        $request->validate($this->validationRules());

        $type = AssetType::findOrFail($request->asset_type_id);

        // Buscamos el último activo de este tipo para continuar la secuencia numérica
        $lastAsset = Asset::where('asset_type_id', $type->id)
            ->whereNotNull('internal_code')
            ->orderBy('id', 'desc')
            ->first();

        $lastNumber = 0;
        if ($lastAsset?->internal_code) {
            // Extraemos el número al final del código: TI-POR-00042 → 42
            preg_match('/(\d+)$/', $lastAsset->internal_code, $matches);
            $lastNumber = (int) ($matches[1] ?? 0);
        }

        $internalCode    = $type->generateAssetCode($lastNumber + 1);
        $availableStatus = Status::where('name', 'Disponible')->firstOrFail();

        Asset::create([
            'internal_code'    => $internalCode,
            'asset_type_id'    => $type->id,
            'brand'            => $request->brand,
            'model'            => $request->model,
            'serial'           => $request->serial,
            'asset_tag'        => $request->asset_tag,
            'fixed_asset_code' => $request->fixed_asset_code,
            'property_type'    => $request->property_type,
            'purchase_value'   => $request->purchase_value,
            'purchase_date'    => $request->purchase_date,
            'provider_name'    => $request->provider_name,
            'status_id'        => $availableStatus->id, // todo activo nuevo nace como Disponible
            'branch_id'        => $request->branch_id,
            'observations'     => $request->observations,
        ]);

        return redirect()
            ->route('tech.assets.index')
            ->with('success', "Activo TI <strong>{$internalCode}</strong> registrado correctamente.");
    }

    /* =========================================================
     | FORMULARIO EDITAR ACTIVO TI
     ========================================================= */
    public function edit(Asset $asset)
    {
        $types    = AssetType::where('category', 'TI')->where('active', true)->orderBy('name')->get();
        $branches = Branch::where('active', true)->orderBy('name')->get();

        return view('tech.assets.edit', compact('asset', 'types', 'branches'));
    }

    /* =========================================================
     | ACTUALIZAR ACTIVO TI
     ========================================================= */
    public function update(Request $request, Asset $asset)
    {
        // Pasamos el ID para que la validación de unique ignore el propio registro
        $request->validate($this->validationRules($asset->id));

        $asset->update([
            'asset_type_id'    => $request->asset_type_id,
            'brand'            => $request->brand,
            'model'            => $request->model,
            'serial'           => $request->serial,
            'asset_tag'        => $request->asset_tag,
            'fixed_asset_code' => $request->fixed_asset_code,
            'property_type'    => $request->property_type,
            'purchase_value'   => $request->purchase_value,
            'purchase_date'    => $request->purchase_date,
            'provider_name'    => $request->provider_name,
            'branch_id'        => $request->branch_id,
            'observations'     => $request->observations,
            // Nota: internal_code y status_id no se modifican aquí
        ]);

        return redirect()
            ->route('tech.assets.show', $asset)
            ->with('success', "Activo <strong>{$asset->internal_code}</strong> actualizado correctamente.");
    }

    /* =========================================================
     | PLACEHOLDERS — módulos pendientes de desarrollo
     ========================================================= */
    public function history()   { return view('tech.history.index'); }
    public function disposals() { return view('tech.disposals.index'); }

    // Activos de categoría OTRO (muebles, equipos de oficina, etc.)
    public function assetsIndex()
    {
        $assets = Asset::whereHas('type', fn($q) => $q->where('category', 'OTRO'))
            ->with(['type','branch','status'])
            ->get();
        return view('assets.index', compact('assets'));
    }

    public function assetsAssignments() { return view('assets.assignments.index'); }
    public function assetsHistory()     { return view('assets.history.index'); }
    public function assetsDisposals()   { return view('assets.disposals.index'); }
}
