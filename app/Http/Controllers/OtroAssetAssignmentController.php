<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Acta;
use App\Models\Asset;
use App\Models\Assignment;
use App\Models\AssignmentAsset;
use App\Models\Branch;
use App\Models\Collaborator;
use Illuminate\Http\Request;

class OtroAssetAssignmentController extends Controller
{
    /* ─── Listado ──────────────────────────────────────────────────── */

    public function index(Request $request)
    {
        $q = Assignment::with(['collaborator', 'area', 'assignedBy',
                               'assignmentAssets.asset.type'])
            ->where('asset_category', 'OTRO');

        if ($request->filled('search')) {
            $s = $request->search;
            $q->where(function ($sq) use ($s) {
                $sq->whereHas('collaborator', fn($c) => $c->where('full_name', 'like', "%{$s}%"))
                   ->orWhereHas('area', fn($a) => $a->where('name', 'like', "%{$s}%"));
            });
        }
        if ($request->filled('status'))    $q->where('status', $request->status);
        if ($request->filled('branch_id')) {
            $q->where(function ($sq) use ($request) {
                $sq->whereHas('collaborator', fn($c) => $c->where('branch_id', $request->branch_id))
                   ->orWhereHas('area', fn($a) => $a->where('branch_id', $request->branch_id));
            });
        }

        $assignments = $q->orderByDesc('assignment_date')->paginate(25)->withQueryString();
        $branches    = Branch::where('active', true)->orderBy('name')->get();

        return view('assets.assignments.index', compact('assignments', 'branches'));
    }

    /* ─── Crear ────────────────────────────────────────────────────── */

    public function create()
    {
        $assets        = Asset::with('type')
            ->whereHas('type', fn($q) => $q->where('category', 'OTRO'))
            ->where(function ($q) {
                // Solo activos disponibles (no asignados activamente)
                $q->whereDoesntHave('assignmentAssets', fn($aa) => $aa->whereNull('returned_at'));
            })
            ->orderBy('internal_code')
            ->get();

        $collaborators = Collaborator::where('active', true)->orderBy('full_name')->get();
        $areas         = Area::active()->with('branch')->orderBy('name')->get();
        $branches      = Branch::where('active', true)->orderBy('name')->get();

        return view('assets.assignments.create', compact('assets', 'collaborators', 'areas', 'branches'));
    }

    /* ─── Guardar ──────────────────────────────────────────────────── */

    public function store(Request $request)
    {
        /*
         * Destinos posibles:
         *   collaborator → colaborador individual
         *   jefe         → jefe/responsable de área (sigue siendo un colaborador)
         *   area         → espacio físico compartido
         *   pool         → pool rotativo (puede o no tener área asociada)
         */
        $request->validate([
            'destination_type' => 'required|in:collaborator,jefe,area,pool',
            // Colaborador requerido para destinos collaborator y jefe
            'collaborator_id'  => 'required_if:destination_type,collaborator|required_if:destination_type,jefe|nullable|exists:collaborators,id',
            // Área requerida solo para destino area; opcional para pool
            'area_id'          => 'required_if:destination_type,area|nullable|exists:areas,id',
            'assignment_date'  => 'required|date',
            'assets'           => 'required|array|min:1',
            'assets.*'         => 'exists:assets,id',
            'notes'            => 'nullable|string|max:500',
        ], [
            'collaborator_id.required_if' => 'Selecciona un colaborador o jefe responsable.',
            'area_id.required_if'         => 'Selecciona el área donde quedará el activo.',
            'assets.required'             => 'Selecciona al menos un activo.',
        ]);

        // Para pool sin área específica se permite null en area_id
        $collaboratorId = in_array($request->destination_type, ['collaborator', 'jefe'])
            ? $request->collaborator_id
            : null;

        $areaId = in_array($request->destination_type, ['area', 'pool'])
            ? $request->area_id
            : null;

        $assignment = Assignment::create([
            'collaborator_id'  => $collaboratorId,
            'area_id'          => $areaId,
            'destination_type' => $request->destination_type,
            'asset_category'   => 'OTRO',
            'assigned_by'      => auth()->id(),
            'assignment_date'  => $request->assignment_date,
            'work_modality'    => $request->work_modality ?? 'presencial',
            'notes'            => $request->notes,
            'status'           => 'activa',
        ]);

        foreach ($request->assets as $assetId) {
            AssignmentAsset::create([
                'assignment_id' => $assignment->id,
                'asset_id'      => $assetId,
                'assigned_at'   => now(),
            ]);
        }

        // Generar acta de ENTREGA automática para OTRO (si aplica y sin duplicar)
        Acta::generateDeliveryForAssignment($assignment, 'OTRO', auth()->user());

        return redirect()
            ->route('assets.assignments.show', $assignment)
            ->with('success', 'Asignación creada correctamente.');
    }

    /* ─── Ver ──────────────────────────────────────────────────────── */

    public function show(Assignment $assignment)
    {
        abort_unless($assignment->asset_category === 'OTRO', 404);

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

    /* ─── Devolución ───────────────────────────────────────────────── */

    public function returnAssets(Assignment $assignment)
    {
        abort_unless($assignment->asset_category === 'OTRO', 404);
        $assignment->load('assignmentAssets.asset.type');
        return view('assets.assignments.return', compact('assignment'));
    }

    public function processReturn(Request $request, Assignment $assignment)
    {
        abort_unless($assignment->asset_category === 'OTRO', 404);

        $request->validate([
            'assets'   => 'required|array|min:1',
            'assets.*' => 'exists:assignment_assets,id',
            'notes'    => 'nullable|string|max:500',
        ]);

        foreach ($request->assets as $aaId) {
            $aa = AssignmentAsset::where('id', $aaId)
                ->where('assignment_id', $assignment->id)
                ->whereNull('returned_at')
                ->first();

            if ($aa) {
                $aa->update([
                    'returned_at'   => now(),
                    'return_notes'  => $request->notes,
                    'returned_by'   => auth()->id(),
                ]);
            }
        }

        $assignment->refreshStatus();

        return redirect()
            ->route('assets.assignments.show', $assignment)
            ->with('success', 'Devolución registrada correctamente.');
    }
}
