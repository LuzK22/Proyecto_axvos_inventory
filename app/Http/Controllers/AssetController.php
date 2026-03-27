<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetEvent;
use App\Models\AssetType;
use App\Models\Branch;
use App\Models\DeletionRequest;
use App\Models\Assignment;
use App\Models\Loan;
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
            'fixed_asset_code' => 'nullable|string|max:100|unique:assets,fixed_asset_code' . ($assetId ? ",{$assetId}" : ''),
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

        $asset = Asset::create([
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
            'status_id'        => $availableStatus->id,
            'branch_id'        => $request->branch_id,
            'observations'     => $request->observations,
            'created_by'       => auth()->id(),
        ]);

        AssetEvent::log($asset->load('status'), 'creacion', 'Disponible', [
            'notes' => "Activo TI registrado: {$internalCode}.",
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
            'updated_by'       => auth()->id(),
        ]);

        AssetEvent::log($asset->fresh()->load('status'), 'actualizacion', $asset->fresh()->status?->name ?? '', [
            'notes' => 'Datos del activo actualizados.',
        ]);

        return redirect()
            ->route('tech.assets.show', $asset)
            ->with('success', "Activo <strong>{$asset->internal_code}</strong> actualizado correctamente.");
    }

    /* =========================================================
     | PLACEHOLDERS — módulos pendientes de desarrollo
     ========================================================= */
    public function history()   { return view('tech.history.index'); }
    public function disposals() { return view('tech.disposals.hub'); }

    /* =========================================================
     | OTROS ACTIVOS — LISTADO
     ========================================================= */
    public function assetsIndex(Request $request)
    {
        $query = Asset::with(['type', 'branch', 'status'])
            ->whereHas('type', fn($q) => $q->where('category', 'OTRO'));

        // Búsqueda por texto libre
        if ($q = $request->input('q')) {
            $query->where(function ($sub) use ($q) {
                $sub->where('internal_code', 'like', "%{$q}%")
                    ->orWhere('serial',       'like', "%{$q}%")
                    ->orWhere('brand',        'like', "%{$q}%")
                    ->orWhere('model',        'like', "%{$q}%")
                    ->orWhere('asset_tag',    'like', "%{$q}%");
            });
        }

        // Filtro por estado
        if ($status = $request->input('status')) {
            $query->whereHas('status', fn($s) => $s->where('name', $status));
        }

        // Filtro por sucursal
        if ($branch = $request->input('branch')) {
            $query->where('branch_id', $branch);
        }

        // Filtro por subcategoría (tipo agrupado)
        if ($subcategory = $request->input('subcategory')) {
            $query->whereHas('type', fn($t) => $t->where('subcategory', $subcategory));
        }

        $assets       = $query->orderBy('id', 'desc')->get();
        $branches     = Branch::where('active', true)->orderBy('name')->get();
        $statuses     = Status::orderBy('name')->get();
        // Subcategorías únicas disponibles para el filtro
        $subcategories = AssetType::where('category', 'OTRO')
            ->whereNotNull('subcategory')
            ->distinct()->pluck('subcategory')->sort()->values();

        return view('assets.index', compact('assets', 'branches', 'statuses', 'subcategories'));
    }

    /* =========================================================
     | OTROS ACTIVOS — CREAR
     ========================================================= */
    public function assetsCreate()
    {
        // Solo tipos activos de categoría OTRO
        $types    = AssetType::where('category', 'OTRO')->where('active', true)
                        ->orderBy('subcategory')->orderBy('name')->get();
        $branches = Branch::where('active', true)->orderBy('name')->get();

        return view('assets.create', compact('types', 'branches'));
    }

    /* =========================================================
     | OTROS ACTIVOS — GUARDAR
     ========================================================= */
    public function assetsStore(Request $request)
    {
        // Reutilizamos las mismas reglas de validación que TI
        // Diferencia: serial puede ser nullable para algunos activos OTRO (ej: silla)
        $rules = $this->validationRules();
        $rules['serial'] = 'nullable|string|max:100|unique:assets,serial';

        $request->validate($rules);

        $type = AssetType::findOrFail($request->asset_type_id);

        // Generar código secuencial: OTRO-SIL-00001
        $lastAsset = Asset::where('asset_type_id', $type->id)
            ->whereNotNull('internal_code')
            ->orderBy('id', 'desc')
            ->first();

        $lastNumber = 0;
        if ($lastAsset?->internal_code) {
            preg_match('/(\d+)$/', $lastAsset->internal_code, $matches);
            $lastNumber = (int) ($matches[1] ?? 0);
        }

        $internalCode    = $type->generateAssetCode($lastNumber + 1);
        $availableStatus = Status::where('name', 'Disponible')->firstOrFail();

        $asset = Asset::create([
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
            'status_id'        => $availableStatus->id,
            'branch_id'        => $request->branch_id,
            'observations'     => $request->observations,
            'created_by'       => auth()->id(),
        ]);

        AssetEvent::log($asset->load('status'), 'creacion', 'Disponible', [
            'notes' => "Activo OTRO registrado: {$internalCode}.",
        ]);

        return redirect()
            ->route('assets.index')
            ->with('success', "Activo <strong>{$internalCode}</strong> registrado correctamente.");
    }

    /* =========================================================
     | OTROS ACTIVOS — DETALLE
     ========================================================= */
    public function assetsShow(Asset $asset)
    {
        // Verificar que pertenece a OTRO
        abort_unless($asset->type?->category === 'OTRO', 404);

        $asset->load(['type', 'branch', 'status', 'events.user', 'events.collaborator']);

        // Asignación activa
        $currentAssignment = $asset->currentAssignmentAsset?->load(
            'assignment.collaborator',
            'assignment.area'
        );

        // Historial completo de asignaciones
        $assignmentHistory = $asset->assignmentAssets()
            ->with(['assignment.collaborator', 'assignment.area', 'returnedBy'])
            ->orderBy('assigned_at', 'desc')
            ->get();

        $pendingDeletion = DeletionRequest::where('asset_id', $asset->id)
            ->where('status', 'pending')
            ->first();

        $branches = Branch::where('active', true)->orderBy('name')->get();

        return view('assets.show', compact(
            'asset', 'currentAssignment', 'assignmentHistory',
            'pendingDeletion', 'branches'
        ));
    }

    /* =========================================================
     | OTROS ACTIVOS — EDITAR
     ========================================================= */
    public function assetsEdit(Asset $asset)
    {
        abort_unless($asset->type?->category === 'OTRO', 404);

        $types    = AssetType::where('category', 'OTRO')->where('active', true)
                        ->orderBy('subcategory')->orderBy('name')->get();
        $branches = Branch::where('active', true)->orderBy('name')->get();

        return view('assets.edit', compact('asset', 'types', 'branches'));
    }

    /* =========================================================
     | OTROS ACTIVOS — ACTUALIZAR
     ========================================================= */
    public function assetsUpdate(Request $request, Asset $asset)
    {
        abort_unless($asset->type?->category === 'OTRO', 404);

        $rules = $this->validationRules($asset->id);
        $rules['serial'] = 'nullable|string|max:100|unique:assets,serial,' . $asset->id;

        $request->validate($rules);

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
            'updated_by'       => auth()->id(),
        ]);

        AssetEvent::log($asset->fresh()->load('status'), 'actualizacion', $asset->fresh()->status?->name ?? '', [
            'notes' => 'Datos del activo actualizados.',
        ]);

        return redirect()
            ->route('assets.show', $asset)
            ->with('success', "Activo <strong>{$asset->internal_code}</strong> actualizado correctamente.");
    }

    public function assetsAssignments() { return view('assets.assignments.index'); }

    public function assetsHistory(Request $request)
    {
        $eventsQuery = AssetEvent::query()
            ->whereHas('asset.type', fn($q) => $q->where('category', 'OTRO'))
            ->with(['asset.type', 'user', 'collaborator', 'assignment']);

        if ($request->filled('event_type')) {
            $eventsQuery->where('event_type', $request->event_type);
        }

        if ($request->filled('q')) {
            $term = $request->string('q')->trim()->value();
            $eventsQuery->where(function ($q) use ($term) {
                $q->where('notes', 'like', "%{$term}%")
                    ->orWhereHas('asset', fn($asset) => $asset
                        ->where('internal_code', 'like', "%{$term}%")
                        ->orWhere('brand', 'like', "%{$term}%")
                        ->orWhere('model', 'like', "%{$term}%"));
            });
        }

        $events = $eventsQuery->orderByDesc('created_at')
            ->paginate(20, ['*'], 'events_page')
            ->withQueryString();

        $assets = Asset::query()
            ->with(['type', 'branch', 'status'])
            ->whereHas('type', fn($q) => $q->where('category', 'OTRO'))
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->string('q')->trim()->value();
                $q->where(function ($sub) use ($term) {
                    $sub->where('internal_code', 'like', "%{$term}%")
                        ->orWhere('brand', 'like', "%{$term}%")
                        ->orWhere('model', 'like', "%{$term}%")
                        ->orWhere('serial', 'like', "%{$term}%");
                });
            })
            ->orderByDesc('created_at')
            ->paginate(15, ['*'], 'assets_page')
            ->withQueryString();

        $assignmentsHistory = Assignment::query()
            ->with(['collaborator', 'area', 'assignedBy', 'assignmentAssets.asset.type'])
            ->where(function ($query) {
                $query->where('asset_category', 'OTRO')
                    ->orWhere(function ($legacy) {
                        $legacy->whereNull('asset_category')
                            ->whereHas('assignmentAssets.asset.type', fn($type) => $type->where('category', 'OTRO'));
                    });
            })
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = $request->string('q')->trim()->value();
                $q->where(function ($sub) use ($term) {
                    $sub->whereHas('collaborator', fn($c) => $c->where('full_name', 'like', "%{$term}%"))
                        ->orWhereHas('area', fn($a) => $a->where('name', 'like', "%{$term}%"));
                });
            })
            ->orderByDesc('assignment_date')
            ->paginate(15, ['*'], 'assignments_page')
            ->withQueryString();

        $activeAssignmentsCount = Assignment::query()
            ->where(function ($query) {
                $query->where('asset_category', 'OTRO')
                    ->orWhere(function ($legacy) {
                        $legacy->whereNull('asset_category')
                            ->whereHas('assignmentAssets.asset.type', fn($type) => $type->where('category', 'OTRO'));
                    });
            })
            ->where('status', 'activa')
            ->count();

        $activeLoansCount = Loan::query()
            ->where(function ($query) {
                $query->where('category', 'OTRO')
                    ->orWhere(function ($legacy) {
                        $legacy->whereNull('category')
                            ->whereHas('asset.type', fn($type) => $type->where('category', 'OTRO'));
                    });
            })
            ->whereIn('status', ['activo', 'vencido'])
            ->count();

        $totalAssetsCount = Asset::query()
            ->whereHas('type', fn($q) => $q->where('category', 'OTRO'))
            ->count();

        $availableAssetsCount = Asset::query()
            ->whereHas('type', fn($q) => $q->where('category', 'OTRO'))
            ->whereHas('status', fn($q) => $q->where('name', 'Disponible'))
            ->count();

        $eventTypes = collect(AssetEvent::TYPES)->only([
            'creacion',
            'asignacion',
            'devolucion',
            'prestamo',
            'actualizacion',
            'traslado',
            'mantenimiento',
            'garantia',
            'baja',
            'donacion',
            'venta',
        ]);

        return view('assets.history.index', compact(
            'events',
            'assets',
            'assignmentsHistory',
            'eventTypes',
            'totalAssetsCount',
            'activeAssignmentsCount',
            'activeLoansCount',
            'availableAssetsCount'
        ));
    }

    public function assetsDisposals()   { return view('assets.disposals.hub'); }
}
