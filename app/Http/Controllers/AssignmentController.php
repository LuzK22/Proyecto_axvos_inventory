<?php

namespace App\Http\Controllers;

use App\Models\Acta;
use App\Models\ActaSignature;
use App\Models\Asset;
use App\Models\AssetEvent;
use App\Models\Assignment;
use App\Models\AssignmentAsset;
use App\Models\Collaborator;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssignmentController extends Controller
{
    /* =========================================================
     | LISTADO DE ASIGNACIONES ACTIVAS
     ========================================================= */

    public function index()
    {
        $assignments = Assignment::with([
                'collaborator.branch',
                'activeAssets.asset.type',
                'activeAssets.asset.status',
                'assignedBy',
            ])
            ->activa()
            ->orderBy('assignment_date', 'desc')
            ->get();

        return view('tech.assignments.index', compact('assignments'));
    }

    /* =========================================================
     | FORMULARIO NUEVA ASIGNACIÓN
     ========================================================= */

    public function create()
    {
        $collaborators = Collaborator::where('active', true)
            ->with('branch')
            ->orderBy('full_name')
            ->get();

        // Solo activos TI que estén disponibles en este momento
        $availableAssets = Asset::with(['type', 'branch', 'status'])
            ->whereHas('type', fn($q) => $q->where('category', 'TI'))
            ->whereHas('status', fn($q) => $q->where('name', 'Disponible'))
            ->orderBy('internal_code')
            ->get();

        return view('tech.assignments.create', compact('collaborators', 'availableAssets'));
    }

    /* =========================================================
     | GUARDAR NUEVA ASIGNACIÓN
     ========================================================= */

    public function store(Request $request)
    {
        $request->validate([
            'collaborator_id' => 'required|exists:collaborators,id',
            'asset_ids'       => 'required|array|min:1',
            'asset_ids.*'     => 'exists:assets,id',
            'assignment_date' => 'required|date',
            'notes'           => 'nullable|string|max:1000',
        ]);

        $collaborator   = Collaborator::findOrFail($request->collaborator_id);
        $assets         = Asset::whereIn('id', $request->asset_ids)->get();
        $assignedStatus = Status::where('name', 'Asignado')->firstOrFail();

        // Revisamos que ningún activo esté ocupado antes de guardar
        $notAvailable = $assets->filter(fn($a) => !$a->isAvailable());
        if ($notAvailable->isNotEmpty()) {
            $codes = $notAvailable->pluck('internal_code')->implode(', ');
            return back()
                ->withErrors(['asset_ids' => "Los siguientes activos no están disponibles: {$codes}"])
                ->withInput();
        }

        DB::transaction(function () use ($request, $collaborator, $assets, $assignedStatus) {

            $assignment = Assignment::create([
                'collaborator_id' => $collaborator->id,
                'assigned_by'     => auth()->id(),
                'assignment_date' => $request->assignment_date,
                'work_modality'   => $collaborator->modalidad_trabajo, // heredamos la modalidad actual del colaborador
                'notes'           => $request->notes,
                'status'          => 'activa',
            ]);

            // Por cada activo: creamos el pivot y lo marcamos como Asignado
            foreach ($assets as $asset) {
                AssignmentAsset::create([
                    'assignment_id' => $assignment->id,
                    'asset_id'      => $asset->id,
                    'assigned_at'   => now(),
                ]);

                $asset->update(['status_id' => $assignedStatus->id]);
            }

            // Generar acta de ENTREGA automática para TI (si aplica y sin duplicar)
            Acta::generateDeliveryForAssignment($assignment, 'TI', auth()->user());
        });

        return redirect()
            ->route('tech.assignments.index')
            ->with('success', 'Asignación creada correctamente.');
    }

    /* =========================================================
     | VER DETALLE DE ASIGNACIÓN
     ========================================================= */

    public function show(Assignment $assignment)
    {
        $assignment->load([
            'collaborator.branch',
            'assignmentAssets.asset.type',
            'assignmentAssets.asset.status',
            'assignmentAssets.returnedBy',
            'assignedBy',
        ]);

        $actaTi = $assignment->actas()
            ->where('asset_category', 'TI')
            ->whereNotIn('status', [Acta::STATUS_ANULADA])
            ->latest()
            ->first();

        return view('tech.assignments.show', compact('assignment', 'actaTi'));
    }

    /* =========================================================
     | FORMULARIO DEVOLUCIÓN
     ========================================================= */

    public function returnForm(Assignment $assignment)
    {
        // Solo cargamos los activos que aún no fueron devueltos
        $assignment->load([
            'collaborator',
            'activeAssets.asset.type',
            'activeAssets.asset.status',
        ]);

        // Si ya devolvieron todo, no tiene sentido mostrar el formulario
        if ($assignment->activeAssets->isEmpty()) {
            return redirect()
                ->route('tech.assignments.show', $assignment)
                ->with('info', 'Todos los activos ya fueron devueltos.');
        }

        return view('tech.assignments.return', compact('assignment'));
    }

    /* =========================================================
     | PROCESAR DEVOLUCIÓN (PARCIAL O TOTAL)
     | Permite devolver solo algunos activos de la asignación
     ========================================================= */

    public function processReturn(Request $request, Assignment $assignment)
    {
        $request->validate([
            'asset_ids'    => 'required|array|min:1',
            'asset_ids.*'  => 'exists:assets,id',
            'return_notes' => 'nullable|string|max:1000',
        ]);

        $availableStatus = Status::where('name', 'Disponible')->firstOrFail();

        $acta = null;

        DB::transaction(function () use ($request, $assignment, $availableStatus, &$acta) {

            $returnedAssets = [];

            foreach ($request->asset_ids as $assetId) {
                // Buscamos el pivot activo para este activo en esta asignación
                $pivot = AssignmentAsset::where('assignment_id', $assignment->id)
                    ->where('asset_id', $assetId)
                    ->whereNull('returned_at')
                    ->first();

                if ($pivot) {
                    $pivot->update([
                        'returned_at'  => now(),
                        'return_notes' => $request->return_notes,
                        'returned_by'  => auth()->id(),
                    ]);

                    $asset = Asset::find($assetId);
                    $asset->update(['status_id' => $availableStatus->id]);

                    AssetEvent::log($asset, 'devolucion', 'Disponible', [
                        'assignment_id'   => $assignment->id,
                        'collaborator_id' => $assignment->collaborator_id,
                        'notes'           => $request->return_notes,
                    ]);

                    $returnedAssets[] = $assetId;
                }
            }

            // Verificamos si quedaron activos pendientes o si la asignación queda cerrada
            $assignment->refreshStatus();

            // Generamos el acta solo si efectivamente se devolvió algo
            if (!empty($returnedAssets)) {
                $acta = Acta::create([
                    'assignment_id' => $assignment->id,
                    'acta_number'   => Acta::generateActaNumber('devolucion'),
                    'acta_type'     => Acta::TYPE_DEVOLUCION,
                    'status'        => Acta::STATUS_BORRADOR,
                    'generated_by'  => auth()->id(),
                    'notes'         => $request->return_notes,
                ]);

                // El colaborador tiene 7 días para firmar el acta de devolución
                ActaSignature::create([
                    'acta_id'          => $acta->id,
                    'signer_role'      => 'collaborator',
                    'signer_name'      => $assignment->collaborator->full_name,
                    'signer_email'     => $assignment->collaborator->email,
                    'token'            => ActaSignature::generateToken(),
                    'token_expires_at' => now()->addDays(7),
                ]);

                ActaSignature::create([
                    'acta_id'          => $acta->id,
                    'signer_role'      => 'responsible',
                    'signer_name'      => auth()->user()->name,
                    'signer_email'     => auth()->user()->email,
                    'signer_user_id'   => auth()->id(),
                    'token'            => ActaSignature::generateToken(),
                    'token_expires_at' => now()->addDays(7),
                ]);
            }
        });

        if ($acta) {
            return redirect()
                ->route('actas.show', $acta)
                ->with('success', 'Devolución registrada. Se generó el Acta de Devolución.');
        }

        return redirect()
            ->route('tech.assignments.show', $assignment)
            ->with('success', 'Devolución registrada correctamente.');
    }

    /* =========================================================
     | BUSCAR COLABORADOR (AJAX — por nombre o cédula)
     ========================================================= */

    public function search(Request $request)
    {
        $query = $request->get('q', '');

        // Evitamos queries innecesarias con menos de 2 caracteres
        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $collaborators = Collaborator::where('active', true)
            ->where(function ($q) use ($query) {
                $q->where('full_name', 'like', "%{$query}%")
                  ->orWhere('document', 'like', "%{$query}%");
            })
            ->with('branch')
            ->limit(10)
            ->get()
            ->map(fn($c) => [
                'id'         => $c->id,
                'text'       => "{$c->full_name} - CC {$c->document}",
                'full_name'  => $c->full_name,
                'document'   => $c->document,
                'position'   => $c->position,
                'area'       => $c->area,
                'branch'     => $c->branch?->name,
                'modality'   => $c->modalidad_trabajo,
            ]);

        return response()->json($collaborators);
    }

    /* =========================================================
     | ACTIVOS ASIGNADOS A UN COLABORADOR (AJAX)
     | Lo usa el formulario de nueva asignación para mostrar qué tiene el colaborador
     ========================================================= */

    public function collaboratorAssets(Collaborator $collaborator)
    {
        // Solo los que aún no han sido devueltos
        $activeAssignmentAssets = AssignmentAsset::whereNull('returned_at')
            ->whereHas('assignment', fn($q) => $q->where('collaborator_id', $collaborator->id))
            ->with(['asset.type', 'asset.status', 'assignment'])
            ->get();

        return response()->json([
            'collaborator' => [
                'id'        => $collaborator->id,
                'full_name' => $collaborator->full_name,
                'document'  => $collaborator->document,
                'modality'  => $collaborator->modalidad_trabajo,
                'position'  => $collaborator->position,
                'area'      => $collaborator->area,
            ],
            'assets' => $activeAssignmentAssets->map(fn($aa) => [
                'assignment_asset_id' => $aa->id,
                'asset_id'            => $aa->asset_id,
                'internal_code'       => $aa->asset->internal_code,
                'type'                => $aa->asset->type?->name,
                'brand'               => $aa->asset->brand,
                'model'               => $aa->asset->model,
                'serial'              => $aa->asset->serial,
                'assigned_at'         => $aa->assigned_at?->format('d/m/Y'),
            ]),
        ]);
    }

    /* =========================================================
     | HISTORIAL DE ASIGNACIONES (activas + devueltas)
     ========================================================= */

    public function history(Request $request)
    {
        $query = Assignment::with([
            'collaborator.branch',
            'assignmentAssets.asset.type',
            'assignedBy',
        ]);

        if ($request->filled('collaborator')) {
            $q = $request->collaborator;
            $query->whereHas('collaborator', fn($c) =>
                $c->where('full_name', 'like', "%{$q}%")
                  ->orWhere('document', 'like', "%{$q}%")
            );
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $assignments = $query->orderBy('assignment_date', 'desc')->paginate(20);

        return view('tech.history.index', compact('assignments'));
    }
}
