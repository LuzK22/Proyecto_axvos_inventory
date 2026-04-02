<?php

namespace App\Http\Controllers;

use App\Models\Acta;
use App\Models\ActaSignature;
use App\Models\Area;
use App\Models\Asset;
use App\Models\AssetEvent;
use App\Models\Assignment;
use App\Models\AssignmentAsset;
use App\Models\Branch;
use App\Models\Collaborator;
use App\Models\Status;
use Illuminate\Http\Request;

class OtroAssetAssignmentController extends Controller
{
    public function index(Request $request)
    {
        $q = Assignment::with([
            'collaborator',
            'area',
            'assignedBy',
            'assignmentAssets.asset.type',
        ])->where(function ($query) {
            $query->where('asset_category', 'OTRO')
                ->orWhere(function ($legacy) {
                    $legacy->whereNull('asset_category')
                        ->whereHas('assignmentAssets.asset.type', fn($type) => $type->where('category', 'OTRO'));
                });
        });

        if ($request->filled('search')) {
            $s = $request->search;
            $q->where(function ($sq) use ($s) {
                $sq->whereHas('collaborator', fn($c) => $c->where('full_name', 'like', "%{$s}%"))
                    ->orWhereHas('area', fn($a) => $a->where('name', 'like', "%{$s}%"));
            });
        }

        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }

        if ($request->filled('branch_id')) {
            $q->where(function ($sq) use ($request) {
                $sq->whereHas('collaborator', fn($c) => $c->where('branch_id', $request->branch_id))
                    ->orWhereHas('area', fn($a) => $a->where('branch_id', $request->branch_id));
            });
        }

        $view = $request->get('view', 'grouped');
        if (!in_array($view, ['grouped', 'detail'], true)) {
            $view = 'grouped';
        }

        $groupBy = $request->get('group_by', 'area');
        if (!in_array($groupBy, ['area', 'collaborator', 'jefe'], true)) {
            $groupBy = 'area';
        }

        $allAssignments = (clone $q)->orderByDesc('assignment_date')->get();
        $assignments = (clone $q)->orderByDesc('assignment_date')->paginate(25)->withQueryString();

        $groupsCollection = match ($groupBy) {
            'collaborator' => $allAssignments
                ->where('destination_type', 'collaborator')
                ->whereNotNull('collaborator_id')
                ->groupBy('collaborator_id'),
            'jefe' => $allAssignments
                ->where('destination_type', 'jefe')
                ->whereNotNull('collaborator_id')
                ->groupBy('collaborator_id'),
            default => $allAssignments
                ->whereIn('destination_type', ['area', 'pool'])
                ->whereNotNull('area_id')
                ->groupBy('area_id'),
        };

        $groupedRows = $groupsCollection->map(function ($items, $key) use ($groupBy) {
            $first = $items->first();
            $assetsCount = $items->sum(fn($a) => $a->assignmentAssets->whereNull('returned_at')->count());
            $latest = $items->sortByDesc('assignment_date')->first();

            return [
                'key' => $key,
                'name' => $groupBy === 'area'
                    ? ($first->area?->name ?? 'Area sin nombre')
                    : ($first->collaborator?->full_name ?? 'Colaborador sin nombre'),
                'branch' => $groupBy === 'area'
                    ? ($first->area?->branch?->name ?? '-')
                    : ($first->collaborator?->branch?->name ?? '-'),
                'destination_label' => $groupBy === 'area'
                    ? 'Area / Pool'
                    : ($groupBy === 'jefe' ? 'Jefe responsable' : 'Colaborador'),
                'assignments_count' => $items->count(),
                'assets_count' => $assetsCount,
                'latest_assignment' => $latest,
                'sample_codes' => $items
                    ->flatMap(fn($a) => $a->assignmentAssets->whereNull('returned_at')->pluck('asset.internal_code'))
                    ->filter()
                    ->unique()
                    ->take(6)
                    ->values(),
            ];
        })->sortByDesc(fn($row) => $row['latest_assignment']->assignment_date)->values();

        $branches = Branch::where('active', true)->orderBy('name')->get();

        return view('assets.assignments.index', compact('assignments', 'branches', 'view', 'groupBy', 'groupedRows'));
    }

    public function create()
    {
        $assets = Asset::with('type')
            ->whereHas('type', fn($q) => $q->where('category', 'OTRO'))
            ->whereDoesntHave('assignmentAssets', fn($aa) => $aa->whereNull('returned_at'))
            ->orderBy('internal_code')
            ->get();

        $collaborators = Collaborator::where('active', true)->orderBy('full_name')->get();
        $areas = Area::active()->with('branch')->orderBy('name')->get();
        $branches = Branch::where('active', true)->orderBy('name')->get();
        $selectedCollaboratorId = request()->integer('collaborator_id') ?: null;
        $destinationType = request()->string('destination')->value() ?: 'collaborator';

        if (!in_array($destinationType, ['collaborator', 'jefe', 'area', 'pool'], true)) {
            $destinationType = 'collaborator';
        }

        return view('assets.assignments.create', compact(
            'assets',
            'collaborators',
            'areas',
            'branches',
            'selectedCollaboratorId',
            'destinationType'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'destination_type' => 'required|in:collaborator,jefe,area,pool',
            'collaborator_id' => 'required_if:destination_type,collaborator|required_if:destination_type,jefe|nullable|exists:collaborators,id',
            'area_id' => 'required_if:destination_type,area,pool|nullable|exists:areas,id',
            'assignment_date' => 'required|date',
            'assets' => 'required|array|min:1',
            'assets.*' => 'exists:assets,id',
            'notes' => 'nullable|string|max:500',
        ], [
            'collaborator_id.required_if' => 'Selecciona un colaborador o jefe responsable.',
            'area_id.required_if' => 'Selecciona el area donde quedara el activo.',
            'assets.required' => 'Selecciona al menos un activo.',
        ]);

        $collaboratorId = in_array($request->destination_type, ['collaborator', 'jefe'], true)
            ? $request->collaborator_id
            : null;

        $areaId = in_array($request->destination_type, ['area', 'pool'], true)
            ? $request->area_id
            : null;

        $assignedStatus = Status::where('name', 'Asignado')->first();

        $assignment = Assignment::create([
            'collaborator_id' => $collaboratorId,
            'area_id' => $areaId,
            'destination_type' => $request->destination_type,
            'asset_category' => 'OTRO',
            'assigned_by' => auth()->id(),
            'assignment_date' => $request->assignment_date,
            'work_modality' => $request->work_modality ?? 'presencial',
            'notes' => $request->notes,
            'status' => 'activa',
        ]);

        foreach ($request->assets as $assetId) {
            $asset = Asset::withoutGlobalScopes()->with('type', 'status')->findOrFail($assetId);
            abort_unless($asset->type?->category === 'OTRO', 403, 'El activo no pertenece a la categoria OTRO.');
            abort_if(
                $asset->assignmentAssets()->whereNull('returned_at')->exists(),
                422,
                'Uno de los activos seleccionados ya tiene una asignacion activa.'
            );

            AssignmentAsset::create([
                'assignment_id' => $assignment->id,
                'asset_id' => $assetId,
                'assigned_at' => now(),
            ]);

            if ($assignedStatus) {
                $asset->update(['status_id' => $assignedStatus->id]);
            }

            $asset->refresh()->load('status');
            AssetEvent::log($asset, 'asignacion', $asset->status?->name ?? 'Asignado', [
                'assignment_id' => $assignment->id,
                'collaborator_id' => $assignment->collaborator_id,
                'notes' => 'Asignacion de Otros Activos registrada.',
            ]);
        }

        // Redirigir al expediente del destinatario con modal post-asignación
        if (in_array($request->destination_type, ['collaborator', 'jefe'], true) && $collaboratorId) {
            $collaborator = \App\Models\Collaborator::find($collaboratorId);
            return redirect()
                ->route('assets.expediente.collaborator', [
                    'collaborator'     => $collaboratorId,
                    'nuevo_assignment' => $assignment->id,
                    'mostrar_modal'    => 1,
                ])
                ->with('success', 'Asignación creada correctamente.');
        }

        if (in_array($request->destination_type, ['area', 'pool'], true) && $areaId) {
            return redirect()
                ->route('assets.expediente.area', [
                    'area'             => $areaId,
                    'nuevo_assignment' => $assignment->id,
                    'mostrar_modal'    => 1,
                ])
                ->with('success', 'Asignación creada correctamente.');
        }

        return redirect()
            ->route('assets.assignments.show', $assignment)
            ->with('success', 'Asignación creada correctamente.');
    }

    public function show(Assignment $assignment)
    {
        abort_unless($this->isOtroAssignment($assignment), 404);

        $assignment->load([
            'collaborator.branch',
            'area.branch',
            'assignedBy',
            'assignmentAssets.asset.type',
            'assignmentAssets.returnedBy',
            'actas',
        ]);

        return view('assets.assignments.show', compact('assignment'));
    }

    public function returnAssets(Assignment $assignment)
    {
        abort_unless($this->isOtroAssignment($assignment), 404);

        $assignment->load('assignmentAssets.asset.type');

        return view('assets.assignments.return', compact('assignment'));
    }

    public function processReturn(Request $request, Assignment $assignment)
    {
        abort_unless($this->isOtroAssignment($assignment), 404);

        $request->validate([
            'assets' => 'required|array|min:1',
            'assets.*' => 'exists:assignment_assets,id',
            'notes' => 'nullable|string|max:500',
        ]);

        $availableStatus = Status::where('name', 'Disponible')->first();

        $returnedAssignmentAssetIds = [];

        foreach ($request->assets as $aaId) {
            $aa = AssignmentAsset::with('asset.status')
                ->where('id', $aaId)
                ->where('assignment_id', $assignment->id)
                ->whereNull('returned_at')
                ->first();

            if (!$aa) {
                continue;
            }

            $aa->update([
                'returned_at' => now(),
                'return_notes' => $request->notes,
                'returned_by' => auth()->id(),
            ]);
            $returnedAssignmentAssetIds[] = $aa->id;

            if ($availableStatus && $aa->asset) {
                $aa->asset->update(['status_id' => $availableStatus->id]);
                $aa->asset->refresh()->load('status');
            }

            if ($aa->asset) {
                AssetEvent::log($aa->asset, 'devolucion', $aa->asset->status?->name ?? 'Disponible', [
                    'assignment_id' => $assignment->id,
                    'collaborator_id' => $assignment->collaborator_id,
                    'notes' => $request->notes,
                ]);
            }
        }

        $assignment->refreshStatus();

        if (!empty($returnedAssignmentAssetIds)) {
            $acta = Acta::create([
                'assignment_id' => $assignment->id,
                'acta_number' => Acta::generateActaNumber('OTRO', Acta::TYPE_DEVOLUCION),
                'acta_type' => Acta::TYPE_DEVOLUCION,
                'asset_category' => 'OTRO',
                'asset_scope' => ['assignment_asset_ids' => array_values($returnedAssignmentAssetIds)],
                'status' => Acta::STATUS_BORRADOR,
                'generated_by' => auth()->id(),
                'notes' => $request->notes,
            ]);

            $recipientName = $assignment->collaborator?->full_name
                ?? ($assignment->area ? ('Area: ' . $assignment->area->name) : '—');
            $recipientEmail = $assignment->collaborator?->email;

            ActaSignature::createCollaboratorSignature($acta, $recipientName, $recipientEmail, 7);
            ActaSignature::createResponsibleSignature($acta, auth()->user(), 7);

            return redirect()
                ->route('actas.show', $acta)
                ->with('success', 'Devolucion registrada. Se genero el Acta de Devolucion para los activos seleccionados.');
        }

        return redirect()
            ->route('assets.assignments.show', $assignment)
            ->with('success', 'Devolucion registrada correctamente.');
    }

    // =========================================================================
    // BUSCAR DESTINATARIO — página de búsqueda con 3 secciones
    // =========================================================================

    public function searchPage(Request $request)
    {
        $q   = trim($request->get('q', ''));
        $tab = $request->get('tab', 'all'); // all | collaborator | manager | area
        if (!in_array($tab, ['all', 'collaborator', 'manager', 'area'], true)) {
            $tab = 'all';
        }

        // ── 1. Recoger todos los AssignmentAsset activos de OTRO ──────────────
        $activeAa = AssignmentAsset::with('assignment')
            ->whereNull('returned_at')
            ->whereHas('assignment', fn($sq) =>
                $sq->where('asset_category', 'OTRO')->where('status', 'activa')
            )
            ->get();

        // Agrupar por colaborador (tipo collaborator + jefe)
        $aaByCollaborator = $activeAa
            ->filter(fn($aa) => in_array($aa->assignment?->destination_type, ['collaborator', 'jefe']))
            ->groupBy(fn($aa) => $aa->assignment->collaborator_id);

        // Agrupar por área (tipo area + pool)
        $aaByArea = $activeAa
            ->filter(fn($aa) => in_array($aa->assignment?->destination_type, ['area', 'pool']))
            ->groupBy(fn($aa) => $aa->assignment->area_id);

        // ── 2. Cargar entidades ───────────────────────────────────────────────
        $collaboratorIds = $aaByCollaborator->keys()->filter()->all();
        $areaIds         = $aaByArea->keys()->filter()->all();

        $collaboratorQuery = Collaborator::whereIn('id', $collaboratorIds)->where('active', true)->with('branch');
        $areaQuery         = Area::whereIn('id', $areaIds)->with('branch');

        if ($q !== '') {
            $lower = $q;
            $collaboratorQuery->where(fn($sq) =>
                $sq->where('full_name', 'like', "%{$lower}%")
                   ->orWhere('document', 'like', "%{$lower}%")
            );
            $areaQuery->where('name', 'like', "%{$lower}%");
        }

        $collaborators = $collaboratorQuery->orderBy('full_name')->get();
        $areas         = $areaQuery->orderBy('name')->get();

        // ── 3. Última asignación por clave ────────────────────────────────────
        $latestByCollaborator = Assignment::whereIn('collaborator_id', $collaboratorIds)
            ->where('asset_category', 'OTRO')->where('status', 'activa')
            ->orderByDesc('assignment_date')
            ->get()
            ->groupBy('collaborator_id')
            ->map(fn($g) => $g->first());

        $latestByArea = Assignment::whereIn('area_id', $areaIds)
            ->where('asset_category', 'OTRO')->where('status', 'activa')
            ->orderByDesc('assignment_date')
            ->get()
            ->groupBy('area_id')
            ->map(fn($g) => $g->first());

        // ── 4. Construir filas ─────────────────────────────────────────────────
        $collaboratorRows = $collaborators->map(fn($c) => [
            'id'               => $c->id,
            'name'             => $c->full_name,
            'sub'              => 'CC ' . $c->document,
            'branch'           => $c->branch?->name ?? '—',
            'modality'         => $c->modalidad_trabajo ?? 'presencial',
            'destination_type' => $aaByCollaborator->get($c->id, collect())
                                    ->first()?->assignment->destination_type ?? 'collaborator',
            'assets_count'     => $aaByCollaborator->get($c->id, collect())->count(),
            'latest'           => $latestByCollaborator->get($c->id),
            'route'            => route('assets.expediente.collaborator', $c->id),
            'create_route'     => route('assets.assignments.create', ['collaborator_id' => $c->id]),
        ])->values();

        $areaRows = $areas->map(fn($a) => [
            'id'               => $a->id,
            'name'             => $a->name,
            'sub'              => $a->branch?->name ?? '—',
            'branch'           => $a->branch?->name ?? '—',
            'modality'         => null,
            'destination_type' => 'area',
            'assets_count'     => $aaByArea->get($a->id, collect())->count(),
            'latest'           => $latestByArea->get($a->id),
            'route'            => route('assets.expediente.area', $a->id),
            'create_route'     => route('assets.assignments.create', ['area_id' => $a->id, 'destination' => 'area']),
        ])->values();

        return view('assets.assignments.search', compact(
            'collaboratorRows', 'areaRows', 'q', 'tab'
        ));
    }

    private function isOtroAssignment(Assignment $assignment): bool
    {
        if ($assignment->asset_category === 'OTRO') {
            return true;
        }

        return $assignment->assignmentAssets()
            ->whereHas('asset.type', fn($query) => $query->where('category', 'OTRO'))
            ->exists();
    }
}
