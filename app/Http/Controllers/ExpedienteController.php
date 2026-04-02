<?php

namespace App\Http\Controllers;

use App\Models\Acta;
use App\Models\ActaSignature;
use App\Models\Asset;
use App\Models\AssetEvent;
use App\Models\AssignmentAsset;
use App\Models\Collaborator;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExpedienteController extends Controller
{
    // =========================================================================
    // FASE 1 — Ver expediente TI del colaborador
    // =========================================================================

    public function showTi(Collaborator $collaborator)
    {
        $activeItems = AssignmentAsset::with([
                'asset.type',
                'asset.status',
                'asset.branch',
                'assignment',
            ])
            ->whereNull('returned_at')
            ->whereHas('assignment', fn($q) =>
                $q->where('collaborator_id', $collaborator->id)
                  ->where('asset_category', 'TI')
            )
            ->whereHas('asset.type', fn($q) => $q->where('category', 'TI'))
            ->orderBy('assigned_at', 'asc')
            ->get();

        // Línea de tiempo: todos los AssignmentAssets (activos + devueltos) para TI
        $timeline = AssignmentAsset::with(['asset.type', 'assignment'])
            ->whereHas('assignment', fn($q) =>
                $q->where('collaborator_id', $collaborator->id)
                  ->where('asset_category', 'TI')
            )
            ->whereHas('asset.type', fn($q) => $q->where('category', 'TI'))
            ->orderByDesc('assigned_at')
            ->get();

        $byAssignment = $activeItems->groupBy('assignment_id');

        // FASE 5: ¿viene de una nueva asignación con modal?
        $newAssignmentId = request()->integer('nuevo_assignment', 0) ?: null;
        $mostrarModal    = (bool) request()->integer('mostrar_modal', 0);

        return view('tech.expediente.show', compact(
            'collaborator',
            'activeItems',
            'timeline',
            'byAssignment',
            'newAssignmentId',
            'mostrarModal',
        ));
    }

    // =========================================================================
    // FASE 3 — Generar acta desde el expediente
    // =========================================================================

    /**
     * POST /tech/expediente/{collaborator}/acta
     *
     * tipo = 'seleccionados' | 'consolidada'
     * aa_ids[] = IDs de AssignmentAsset (solo para tipo=seleccionados)
     */
    public function generateActa(Request $request, Collaborator $collaborator)
    {
        $request->validate([
            'tipo'     => 'required|in:seleccionados,consolidada',
            'aa_ids'   => 'required_if:tipo,seleccionados|array|min:1',
            'aa_ids.*' => 'integer|exists:assignment_assets,id',
        ]);

        $aaIds = $request->tipo === 'seleccionados'
            ? array_map('intval', $request->aa_ids ?? [])
            : [];   // vacío = todos los activos activos del colaborador

        // Validar que los aa_ids pertenecen a este colaborador y no devueltos
        if (!empty($aaIds)) {
            $valid = AssignmentAsset::whereIn('id', $aaIds)
                ->whereNull('returned_at')
                ->whereHas('assignment', fn($q) =>
                    $q->where('collaborator_id', $collaborator->id)
                )
                ->count();

            if ($valid !== count($aaIds)) {
                return back()->with('error', 'Algunos activos seleccionados no son válidos para este colaborador.');
            }
        }

        try {
            $acta = Acta::generateConsolidatedForCollaborator(
                $collaborator,
                'TI',
                auth()->user(),
                $aaIds
            );
        } catch (\Throwable $e) {
            return back()->with('error', 'Error al generar el acta: ' . $e->getMessage());
        }

        $label = $request->tipo === 'consolidada' ? 'consolidada' : 'con activos seleccionados';

        return redirect()
            ->route('actas.show', $acta)
            ->with('success', "Acta de entrega {$label} generada correctamente.");
    }

    // =========================================================================
    // FASE 4 — Devolución desde el expediente
    // =========================================================================

    /**
     * GET /tech/expediente/{collaborator}/devolucion
     *
     * aa_ids[] = IDs de AssignmentAsset a devolver
     * tipo     = 'parcial' | 'total'
     */
    public function returnForm(Request $request, Collaborator $collaborator)
    {
        $tipo = $request->get('tipo', 'parcial');

        // Todos los activos activos del colaborador
        $activeItems = AssignmentAsset::with(['asset.type', 'asset.status', 'assignment'])
            ->whereNull('returned_at')
            ->whereHas('assignment', fn($q) =>
                $q->where('collaborator_id', $collaborator->id)
                  ->where('asset_category', 'TI')
            )
            ->whereHas('asset.type', fn($q) => $q->where('category', 'TI'))
            ->orderBy('assigned_at', 'asc')
            ->get();

        if ($activeItems->isEmpty()) {
            return redirect()
                ->route('tech.expediente.show', $collaborator)
                ->with('info', 'Este colaborador no tiene activos TI activos para devolver.');
        }

        // Preselección: aa_ids de query string, o todos si tipo=total
        $preselected = $tipo === 'total'
            ? $activeItems->pluck('id')->all()
            : array_map('intval',
                array_filter(
                    explode(',', (string) $request->get('aa_ids', ''))
                )
              );

        return view('tech.expediente.return', compact(
            'collaborator',
            'activeItems',
            'preselected',
            'tipo',
        ));
    }

    /**
     * POST /tech/expediente/{collaborator}/devolucion
     */
    public function processReturn(Request $request, Collaborator $collaborator)
    {
        $request->validate([
            'aa_ids'       => 'required|array|min:1',
            'aa_ids.*'     => 'integer|exists:assignment_assets,id',
            'return_notes' => 'nullable|string|max:1000',
        ]);

        $aaIds = array_map('intval', $request->aa_ids);

        // Verificar que pertenecen al colaborador y no están devueltos
        $items = AssignmentAsset::whereIn('id', $aaIds)
            ->whereNull('returned_at')
            ->whereHas('assignment', fn($q) =>
                $q->where('collaborator_id', $collaborator->id)
            )
            ->with('assignment')
            ->get();

        if ($items->isEmpty()) {
            return back()->with('error', 'No se encontraron activos válidos para devolver.');
        }

        $availableStatus = Status::where('name', 'Disponible')->firstOrFail();
        $returnedAaIds   = [];
        $acta            = null;

        DB::transaction(function () use (
            $items, $availableStatus, $request, $collaborator, &$returnedAaIds, &$acta
        ) {
            foreach ($items as $item) {
                $item->update([
                    'returned_at'  => now(),
                    'return_notes' => $request->return_notes,
                    'returned_by'  => auth()->id(),
                ]);

                /** @var Asset $asset */
                $asset = Asset::find($item->asset_id);
                if ($asset) {
                    $asset->update(['status_id' => $availableStatus->id]);

                    AssetEvent::log($asset, 'devolucion', 'Disponible', [
                        'assignment_id'   => $item->assignment_id,
                        'collaborator_id' => $collaborator->id,
                        'notes'           => $request->return_notes,
                    ]);
                }

                // Actualizar status de la asignación padre
                $item->assignment?->refreshStatus();

                $returnedAaIds[] = $item->id;
            }

            // Generar acta de devolución consolidada
            if (!empty($returnedAaIds)) {
                $acta = Acta::generateReturnForCollaborator(
                    $collaborator,
                    'TI',
                    auth()->user(),
                    $returnedAaIds
                );
            }
        });

        if ($acta) {
            return redirect()
                ->route('actas.show', $acta)
                ->with('success', 'Devolución registrada. Se generó el Acta de Devolución.');
        }

        return redirect()
            ->route('tech.expediente.show', $collaborator)
            ->with('success', 'Devolución registrada correctamente.');
    }
}
