<?php

namespace App\Http\Controllers;

use App\Models\Acta;
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

        $assignments = $q->orderByDesc('assignment_date')->paginate(25)->withQueryString();
        $branches = Branch::where('active', true)->orderBy('name')->get();

        return view('assets.assignments.index', compact('assignments', 'branches'));
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
            'area_id' => 'required_if:destination_type,area|nullable|exists:areas,id',
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

        Acta::generateDeliveryForAssignment($assignment, 'OTRO', auth()->user());

        return redirect()
            ->route('assets.assignments.show', $assignment)
            ->with('success', 'Asignacion creada correctamente.');
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

        return redirect()
            ->route('assets.assignments.show', $assignment)
            ->with('success', 'Devolucion registrada correctamente.');
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
