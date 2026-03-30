<?php

namespace App\Http\Controllers;

use App\Models\Acta;
use App\Models\ActaSignature;
use App\Models\Asset;
use App\Models\AssetEvent;
use App\Models\Assignment;
use App\Models\AssignmentAsset;
use App\Models\AssignmentType;
use App\Models\Area;
use App\Models\Collaborator;
use App\Models\Loan;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class AssignmentController extends Controller
{
    /* =========================================================
     | LISTADO DE ASIGNACIONES ACTIVAS
     ========================================================= */

    public function index()
    {
        $assignments = Assignment::with([
                'collaborator.branch',
                'area',
                'activeAssets.asset.type',
                'activeAssets.asset.status',
                'assignedBy',
            ])
            ->where(function ($q) {
                $q->where('asset_category', 'TI')
                    ->orWhere(function ($legacy) {
                        $legacy->whereNull('asset_category')
                            ->whereHas('assignmentAssets.asset.type', fn($t) => $t->where('category', 'TI'));
                    });
            })
            ->activa()
            ->orderBy('assignment_date', 'desc')
            ->get();
        $groupedAssignments = $assignments
            ->groupBy('collaborator_id')
            ->map(function ($items) {
                $first = $items->first();
                $assetsCount = $items->sum(fn($a) => $a->activeAssets->count());
                $destinationLabels = $items
                    ->map(fn($a) => Assignment::destinationLabel($a->destination_type ?? 'collaborator'))
                    ->unique()
                    ->values();

                return [
                    'collaborator' => $first->collaborator,
                    'assignments_count' => $items->count(),
                    'assets_count' => $assetsCount,
                    'latest_assignment' => $items->sortByDesc('assignment_date')->first(),
                    'destination_labels' => $destinationLabels,
                ];
            })
            ->sortByDesc(fn($g) => $g['latest_assignment']->assignment_date)
            ->values();

        return view('tech.assignments.index', compact('assignments', 'groupedAssignments'));
    }

    /* =========================================================
     | FORMULARIO NUEVA ASIGNACIÃ“N
     ========================================================= */

    public function create()
    {
        $collaborators = Collaborator::where('active', true)
            ->with('branch')
            ->orderBy('full_name')
            ->get();

        // Solo activos TI que estÃ©n disponibles en este momento
        $availableAssets = Asset::with(['type', 'branch', 'status'])
            ->whereHas('type', fn($q) => $q->where('category', 'TI'))
            ->whereHas('status', fn($q) => $q->where('name', 'Disponible'))
            ->orderBy('internal_code')
            ->get();

        $modalityAssignmentType = AssignmentType::query()
            ->where('active', true)
            ->where(function ($q) {
                $q->where('trigger_field', 'modalidad')
                    ->orWhere('name', 'like', '%Modalidad%');
            })
            ->orderBy('sort_order')
            ->first();

        $areas = Area::active()->with('branch')->orderBy('name')->get();

        return view('tech.assignments.create', compact('collaborators', 'availableAssets', 'modalityAssignmentType', 'areas'));
    }

    /* =========================================================
     | GUARDAR NUEVA ASIGNACIÃ“N
     ========================================================= */

    public function store(Request $request)
    {
        $request->validate([
            // Colaborador debe existir Y estar activo
            'collaborator_id' => ['required', Rule::exists('collaborators', 'id')->where('active', true)],
            'destination_type' => 'required|in:collaborator,jefe,area,pool',
            'area_id' => 'required_if:destination_type,area,pool|nullable|exists:areas,id',
            'asset_ids'       => 'required|array|min:1',
            'asset_ids.*'     => 'exists:assets,id',
            'assignment_date' => 'required|date',
            'notes'           => 'nullable|string|max:1000',
        ]);

        $collaborator   = Collaborator::findOrFail($request->collaborator_id);
        $assets         = Asset::whereIn('id', $request->asset_ids)->get();
        $assignedStatus = Status::where('name', 'Asignado')->firstOrFail();

        // Revisamos que ningÃºn activo estÃ© ocupado antes de guardar
        $notAvailable = $assets->filter(fn($a) => !$a->isAvailable());
        if ($notAvailable->isNotEmpty()) {
            $codes = $notAvailable->pluck('internal_code')->implode(', ');
            return back()
                ->withErrors(['asset_ids' => "Los siguientes activos no estÃ¡n disponibles: {$codes}"])
                ->withInput();
        }

        $acta = null;

        DB::transaction(function () use ($request, $collaborator, $assets, $assignedStatus, &$acta) {

            $assignment = Assignment::create([
                'collaborator_id' => $collaborator->id,
                'area_id'         => in_array($request->destination_type, ['area', 'pool'], true) ? $request->area_id : null,
                'destination_type'=> $request->destination_type,
                'asset_category'  => 'TI',
                'assigned_by'     => auth()->id(),
                'assignment_date' => $request->assignment_date,
                'work_modality'   => $collaborator->modalidad_trabajo,
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

            // Generar acta de ENTREGA automÃ¡tica para TI (si aplica y sin duplicar)
            $acta = Acta::generateDeliveryForAssignment($assignment, 'TI', auth()->user());
        });

        $msg = 'AsignaciÃ³n creada correctamente.';
        if ($acta) {
            $msg .= ' Se generÃ³ el Acta de Entrega TI.';
            return redirect()->route('actas.show', $acta)->with('success', $msg);
        }

        return redirect()
            ->route('tech.assignments.index')
            ->with('success', $msg);
    }

    /* =========================================================
     | VER DETALLE DE ASIGNACIÃ“N
     ========================================================= */

    public function show(Assignment $assignment)
    {
        $assignment->load([
            'collaborator.branch',
            'area.branch',
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
     | FORMULARIO DEVOLUCIÃ“N
     ========================================================= */

    public function returnForm(Assignment $assignment)
    {
        // Solo cargamos los activos que aÃºn no fueron devueltos
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

        $preselectedAssetIds = collect(explode(',', (string) request()->get('asset_ids', '')))
            ->map(fn($id) => (int) trim($id))
            ->filter()
            ->values()
            ->all();

        return view('tech.assignments.return', compact('assignment', 'preselectedAssetIds'));
    }

    /* =========================================================
     | PROCESAR DEVOLUCIÃ“N (PARCIAL O TOTAL)
     | Permite devolver solo algunos activos de la asignaciÃ³n
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

            $returnedAssetIds = [];
            $returnedAssignmentAssetIds = [];

            foreach ($request->asset_ids as $assetId) {
                // Buscamos el pivot activo para este activo en esta asignaciÃ³n
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

                    $returnedAssetIds[] = $assetId;
                    $returnedAssignmentAssetIds[] = $pivot->id;
                }
            }

            // Verificamos si quedaron activos pendientes o si la asignaciÃ³n queda cerrada
            $assignment->refreshStatus();

            // Generamos el acta solo si efectivamente se devolviÃ³ algo
            if (!empty($returnedAssetIds)) {
                $deliveryActa = $assignment->actas()
                    ->where('acta_type', Acta::TYPE_ENTREGA)
                    ->where('asset_category', 'TI')
                    ->whereNotIn('status', [Acta::STATUS_ANULADA])
                    ->latest()
                    ->first();

                if ($deliveryActa) {
                    $pendingCount = $assignment->assignmentAssets()->whereNull('returned_at')->count();
                    $line = now()->format('Y-m-d H:i') . ' - Devolucion registrada (' . count($returnedAssetIds) . ' activo(s)); pendientes: ' . $pendingCount . '.';
                    $deliveryActa->update([
                        'notes' => trim(($deliveryActa->notes ? $deliveryActa->notes . PHP_EOL : '') . $line),
                    ]);
                }
            }

            if (!empty($returnedAssetIds)) {
                $acta = Acta::create([
                    'assignment_id' => $assignment->id,
                    'acta_number'   => Acta::generateActaNumber('TI', Acta::TYPE_DEVOLUCION),
                    'acta_type'     => Acta::TYPE_DEVOLUCION,
                    'asset_category'=> 'TI',
                    'asset_scope'   => ['assignment_asset_ids' => array_values($returnedAssignmentAssetIds)],
                    'status'        => Acta::STATUS_BORRADOR,
                    'generated_by'  => auth()->id(),
                    'notes'         => $request->return_notes,
                ]);

                // El colaborador tiene 7 dÃ­as para firmar el acta de devoluciÃ³n
                ActaSignature::createCollaboratorSignature(
                    $acta,
                    $assignment->collaborator->full_name,
                    $assignment->collaborator->email,
                    7
                );

                ActaSignature::createResponsibleSignature($acta, auth()->user(), 7);
            }
        });

        if ($acta) {
            return redirect()
                ->route('actas.show', $acta)
                ->with('success', 'Devolucion registrada. Se genero el Acta de Devolucion para los activos seleccionados.');
        }

        return redirect()
            ->route('tech.assignments.show', $assignment)
            ->with('success', 'DevoluciÃ³n registrada correctamente.');
    }

    /* =========================================================
     | BUSCAR COLABORADOR (AJAX â€” por nombre o cÃ©dula)
     ========================================================= */

    public function search(Request $request)
    {
        $query = trim((string) $request->get('q', ''));
        $isApiRequest = $request->expectsJson() || $request->ajax() || $request->wantsJson();

        $collaboratorsQuery = Collaborator::where('active', true);
        if ($query !== '') {
            $collaboratorsQuery->where('full_name', 'like', "%{$query}%");
        }

        $collaborators = $collaboratorsQuery
            ->with('branch')
            ->limit(20)
            ->get();

        $collaboratorIds = $collaborators->pluck('id')->all();

        $directTiByCollaborator = AssignmentAsset::query()
            ->with(['asset.type', 'assignment'])
            ->whereNull('returned_at')
            ->whereHas('assignment', fn($q) => $q->whereIn('collaborator_id', $collaboratorIds))
            ->whereHas('asset.type', fn($q) => $q->where('category', 'TI'))
            ->get()
            ->groupBy(fn($aa) => $aa->assignment?->collaborator_id);

        $activeAreas = Area::active()->get(['id', 'name', 'branch_id']);
        $areaIdsByCollaborator = [];
        foreach ($collaborators as $c) {
            $areaName = strtolower(trim((string) $c->area));
            if ($areaName === '') {
                $areaIdsByCollaborator[$c->id] = [];
                continue;
            }
            $matched = $activeAreas
                ->filter(fn($a) => strtolower(trim((string) $a->name)) === $areaName
                    && ($a->branch_id === null || $a->branch_id === $c->branch_id))
                ->pluck('id')
                ->values()
                ->all();
            $areaIdsByCollaborator[$c->id] = $matched;
        }
        $allAreaIds = collect($areaIdsByCollaborator)->flatten()->unique()->values()->all();

        $areaTiByArea = collect();
        if (!empty($allAreaIds)) {
            $areaTiByArea = AssignmentAsset::query()
                ->with(['asset.type', 'assignment'])
                ->whereNull('returned_at')
                ->whereHas('assignment', fn($q) => $q
                    ->whereIn('area_id', $allAreaIds)
                    ->whereIn('destination_type', ['area', 'pool']))
                ->whereHas('asset.type', fn($q) => $q->where('category', 'TI'))
                ->get()
                ->groupBy(fn($aa) => $aa->assignment?->area_id);
        }

        $tiLoansByCollaborator = Loan::query()
            ->with('asset.type')
            ->whereIn('collaborator_id', $collaboratorIds)
            ->whereIn('status', ['activo', 'vencido'])
            ->where(function ($q) {
                $q->where('category', 'TI')
                    ->orWhere(function ($legacy) {
                        $legacy->whereNull('category')
                            ->whereHas('asset.type', fn($t) => $t->where('category', 'TI'));
                    });
            })
            ->get()
            ->groupBy('collaborator_id');

        $rows = $collaborators->map(function ($c) use (
            $directTiByCollaborator,
            $areaIdsByCollaborator,
            $areaTiByArea,
            $tiLoansByCollaborator
        ) {
            try {
                $document = $c->document;
            } catch (\Throwable $e) {
                $document = (string) $c->getRawOriginal('document');
            }

            $directTi = $directTiByCollaborator->get($c->id, collect());
            $areaIds = $areaIdsByCollaborator[$c->id] ?? [];
            $areaTi = collect($areaIds)->flatMap(fn($id) => $areaTiByArea->get($id, collect()))->values();
            $tiLoans = $tiLoansByCollaborator->get($c->id, collect());
            $latestDirectAssignmentId = optional(
                $directTi->sortByDesc(fn($aa) => $aa->assigned_at ?? $aa->created_at)->first()
            )->assignment_id;

            return [
                'id'         => $c->id,
                'full_name'  => $c->full_name,
                'document'   => $document,
                'position'   => $c->position,
                'area'       => $c->area,
                'branch'     => $c->branch?->name,
                'modality'   => $c->modalidad_trabajo,
                'ti_direct_count' => $directTi->count(),
                'ti_area_count' => $areaTi->count(),
                'ti_loans_count' => $tiLoans->count(),
                'latest_ti_assignment_id' => $latestDirectAssignmentId,
                'ti_codes' => $directTi->pluck('asset.internal_code')
                    ->merge($areaTi->pluck('asset.internal_code'))
                    ->filter()
                    ->unique()
                    ->values(),
            ];
        });

        if ($isApiRequest) {
            return response()->json($rows->map(fn($r) => [
                'id' => $r['id'],
                'text' => "{$r['full_name']} - CC {$r['document']}",
                'full_name' => $r['full_name'],
                'document' => $r['document'],
                'position' => $r['position'],
                'area' => $r['area'],
                'branch' => $r['branch'],
                'modality' => $r['modality'],
            ]));
        }

        return view('tech.assignments.search', [
            'q' => $query,
            'results' => $rows,
        ]);
    }
    /* =========================================================
     | ACTIVOS ASIGNADOS A UN COLABORADOR (AJAX)
     | Lo usa el formulario de nueva asignaciÃ³n para mostrar quÃ© tiene el colaborador
     ========================================================= */

    public function collaboratorAssets(Collaborator $collaborator)
    {
        // Solo los que aÃºn no han sido devueltos
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
            'area.branch',
            'assignmentAssets.asset.type',
            'assignedBy',
        ])->where(function ($q) {
            $q->where('asset_category', 'TI')
                ->orWhere(function ($legacy) {
                    $legacy->whereNull('asset_category')
                        ->whereHas('assignmentAssets.asset.type', fn($t) => $t->where('category', 'TI'));
                });
        });

        if ($request->filled('collaborator')) {
            $q = $request->collaborator;
            $query->where(function ($sq) use ($q) {
                $sq->whereHas('collaborator', fn($c) =>
                    $c->where('full_name', 'like', "%{$q}%")
                )->orWhereHas('area', fn($a) =>
                    $a->where('name', 'like', "%{$q}%")
                );
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $assignments = $query->orderBy('assignment_date', 'desc')->paginate(20);

        return view('tech.history.index', compact('assignments'));
    }
}
