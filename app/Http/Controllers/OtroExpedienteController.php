<?php

namespace App\Http\Controllers;

use App\Models\Acta;
use App\Models\Area;
use App\Models\Asset;
use App\Models\AssetEvent;
use App\Models\AssignmentAsset;
use App\Models\Collaborator;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OtroExpedienteController extends Controller
{
    // =========================================================================
    // HELPERS COMPARTIDOS
    // =========================================================================

    /** Activos OTRO activos asignados a un colaborador/jefe */
    private function activeItemsByCollaborator(Collaborator $collaborator)
    {
        return AssignmentAsset::with(['asset.type', 'asset.status', 'assignment'])
            ->whereNull('returned_at')
            ->whereHas('assignment', fn($q) =>
                $q->where('collaborator_id', $collaborator->id)
                  ->where('asset_category', 'OTRO')
            )
            ->whereHas('asset.type', fn($q) => $q->where('category', 'OTRO'))
            ->orderBy('assigned_at', 'asc')
            ->get();
    }

    /** Activos OTRO activos asignados a un área */
    private function activeItemsByArea(Area $area)
    {
        return AssignmentAsset::with(['asset.type', 'asset.status', 'assignment'])
            ->whereNull('returned_at')
            ->whereHas('assignment', fn($q) =>
                $q->where('area_id', $area->id)
                  ->whereIn('destination_type', ['area', 'pool'])
                  ->where('asset_category', 'OTRO')
            )
            ->whereHas('asset.type', fn($q) => $q->where('category', 'OTRO'))
            ->orderBy('assigned_at', 'asc')
            ->get();
    }

    /** Actas OTRO relacionadas con un colaborador */
    private function actasByCollaborator(Collaborator $collaborator)
    {
        return Acta::where(function ($q) use ($collaborator) {
                $q->whereHas('assignment', fn($a) =>
                        $a->where('collaborator_id', $collaborator->id)
                    )
                    ->orWhere('collaborator_id', $collaborator->id);
            })
            ->where('asset_category', 'OTRO')
            ->with(['generatedBy', 'signatures'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /** Actas OTRO relacionadas con un área */
    private function actasByArea(Area $area)
    {
        return Acta::where(function ($q) use ($area) {
                $q->whereHas('assignment', fn($a) =>
                        $a->where('area_id', $area->id)
                    )
                    ->orWhere('area_id', $area->id);
            })
            ->where('asset_category', 'OTRO')
            ->with(['generatedBy', 'signatures'])
            ->orderBy('created_at', 'desc')
            ->get();
    }

    // =========================================================================
    // EXPEDIENTE POR COLABORADOR / JEFE
    // =========================================================================

    public function showCollaborator(Collaborator $collaborator)
    {
        $activeItems     = $this->activeItemsByCollaborator($collaborator);
        $actaIds         = $this->actasByCollaborator($collaborator);
        $newAssignmentId = request()->integer('nuevo_assignment', 0) ?: null;
        $mostrarModal    = (bool) request()->integer('mostrar_modal', 0);

        return view('assets.expediente.show', [
            'destinatarioType'  => 'collaborator',
            'destinatario'      => $collaborator,
            'activeItems'       => $activeItems,
            'actaIds'           => $actaIds,
            'routeActa'         => route('assets.expediente.collaborator.acta',   $collaborator),
            'routeReturn'       => route('assets.expediente.collaborator.return',  $collaborator),
            'routeNew'          => route('assets.assignments.create', ['collaborator_id' => $collaborator->id]),
            'newAssignmentId'   => $newAssignmentId,
            'mostrarModal'      => $mostrarModal,
        ]);
    }

    // =========================================================================
    // EXPEDIENTE POR ÁREA
    // =========================================================================

    public function showArea(Area $area)
    {
        $activeItems     = $this->activeItemsByArea($area);
        $actaIds         = $this->actasByArea($area);
        $newAssignmentId = request()->integer('nuevo_assignment', 0) ?: null;
        $mostrarModal    = (bool) request()->integer('mostrar_modal', 0);

        return view('assets.expediente.show', [
            'destinatarioType'  => 'area',
            'destinatario'      => $area,
            'activeItems'       => $activeItems,
            'actaIds'           => $actaIds,
            'routeActa'         => route('assets.expediente.area.acta',   $area),
            'routeReturn'       => route('assets.expediente.area.return',  $area),
            'routeNew'          => route('assets.assignments.create', ['area_id' => $area->id, 'destination' => 'area']),
            'newAssignmentId'   => $newAssignmentId,
            'mostrarModal'      => $mostrarModal,
        ]);
    }

    // =========================================================================
    // GENERAR ACTA — COLABORADOR
    // =========================================================================

    public function generateActaCollaborator(Request $request, Collaborator $collaborator)
    {
        $request->validate([
            'tipo'     => 'required|in:seleccionados,consolidada',
            'aa_ids'   => 'required_if:tipo,seleccionados|array|min:1',
            'aa_ids.*' => 'integer|exists:assignment_assets,id',
        ]);

        $aaIds = $request->tipo === 'seleccionados'
            ? array_map('intval', $request->aa_ids ?? [])
            : [];

        if (!empty($aaIds)) {
            $valid = AssignmentAsset::whereIn('id', $aaIds)
                ->whereNull('returned_at')
                ->whereHas('assignment', fn($q) =>
                    $q->where('collaborator_id', $collaborator->id)
                )
                ->count();

            if ($valid !== count($aaIds)) {
                return back()->with('error', 'Algunos activos no son válidos para este destinatario.');
            }
        }

        try {
            $acta = Acta::generateConsolidatedForCollaborator(
                $collaborator, 'OTRO', auth()->user(), $aaIds
            );
        } catch (\Throwable $e) {
            return back()->with('error', 'Error al generar el acta: ' . $e->getMessage());
        }

        $label = $request->tipo === 'consolidada' ? 'consolidada' : 'con activos seleccionados';

        return redirect()
            ->route('actas.show', $acta)
            ->with('success', "Acta OTRO {$label} generada correctamente.");
    }

    // =========================================================================
    // GENERAR ACTA — ÁREA
    // =========================================================================

    public function generateActaArea(Request $request, Area $area)
    {
        $request->validate([
            'tipo'     => 'required|in:seleccionados,consolidada',
            'aa_ids'   => 'required_if:tipo,seleccionados|array|min:1',
            'aa_ids.*' => 'integer|exists:assignment_assets,id',
        ]);

        $aaIds = $request->tipo === 'seleccionados'
            ? array_map('intval', $request->aa_ids ?? [])
            : [];

        if (!empty($aaIds)) {
            $valid = AssignmentAsset::whereIn('id', $aaIds)
                ->whereNull('returned_at')
                ->whereHas('assignment', fn($q) =>
                    $q->where('area_id', $area->id)
                )
                ->count();

            if ($valid !== count($aaIds)) {
                return back()->with('error', 'Algunos activos no son válidos para esta área.');
            }
        }

        try {
            $acta = Acta::generateConsolidatedForArea(
                $area, 'OTRO', auth()->user(), $aaIds
            );
        } catch (\Throwable $e) {
            return back()->with('error', 'Error al generar el acta: ' . $e->getMessage());
        }

        $label = $request->tipo === 'consolidada' ? 'consolidada' : 'con activos seleccionados';

        return redirect()
            ->route('actas.show', $acta)
            ->with('success', "Acta OTRO {$label} generada correctamente.");
    }

    // =========================================================================
    // DEVOLUCIÓN — COLABORADOR
    // =========================================================================

    public function returnFormCollaborator(Request $request, Collaborator $collaborator)
    {
        $tipo        = $request->get('tipo', 'parcial');
        $activeItems = $this->activeItemsByCollaborator($collaborator);

        if ($activeItems->isEmpty()) {
            return redirect()
                ->route('assets.expediente.collaborator', $collaborator)
                ->with('info', 'Este destinatario no tiene activos OTRO activos para devolver.');
        }

        $preselected = $tipo === 'total'
            ? $activeItems->pluck('id')->all()
            : array_map('intval', array_filter(
                explode(',', (string) $request->get('aa_ids', ''))
              ));

        return view('assets.expediente.return', compact(
            'collaborator', 'activeItems', 'preselected', 'tipo'
        ) + ['destinatarioType' => 'collaborator',
             'routeReturnStore' => route('assets.expediente.collaborator.return.store', $collaborator),
             'routeBack'        => route('assets.expediente.collaborator', $collaborator),
        ]);
    }

    public function processReturnCollaborator(Request $request, Collaborator $collaborator)
    {
        return $this->doProcessReturn($request, function () use ($collaborator) {
            return $this->activeItemsByCollaborator($collaborator)
                ->pluck('id')
                ->all();
        }, function (array $returnedAaIds) use ($collaborator) {
            return Acta::generateReturnForCollaborator($collaborator, 'OTRO', auth()->user(), $returnedAaIds);
        }, route('assets.expediente.collaborator', $collaborator));
    }

    // =========================================================================
    // DEVOLUCIÓN — ÁREA
    // =========================================================================

    public function returnFormArea(Request $request, Area $area)
    {
        $tipo        = $request->get('tipo', 'parcial');
        $activeItems = $this->activeItemsByArea($area);

        if ($activeItems->isEmpty()) {
            return redirect()
                ->route('assets.expediente.area', $area)
                ->with('info', 'Esta área no tiene activos OTRO activos para devolver.');
        }

        $preselected = $tipo === 'total'
            ? $activeItems->pluck('id')->all()
            : array_map('intval', array_filter(
                explode(',', (string) $request->get('aa_ids', ''))
              ));

        return view('assets.expediente.return', compact(
            'area', 'activeItems', 'preselected', 'tipo'
        ) + ['destinatarioType' => 'area',
             'routeReturnStore' => route('assets.expediente.area.return.store', $area),
             'routeBack'        => route('assets.expediente.area', $area),
        ]);
    }

    public function processReturnArea(Request $request, Area $area)
    {
        return $this->doProcessReturn($request, function () use ($area) {
            return $this->activeItemsByArea($area)
                ->pluck('id')
                ->all();
        }, function (array $returnedAaIds) use ($area) {
            return Acta::generateReturnForArea($area, 'OTRO', auth()->user(), $returnedAaIds);
        }, route('assets.expediente.area', $area));
    }

    // =========================================================================
    // LÓGICA COMPARTIDA DE DEVOLUCIÓN
    // =========================================================================

    private function doProcessReturn(
        Request $request,
        callable $getValidIds,
        callable $generateActa,
        string $fallbackRoute
    ) {
        $request->validate([
            'aa_ids'       => 'required|array|min:1',
            'aa_ids.*'     => 'integer|exists:assignment_assets,id',
            'return_notes' => 'nullable|string|max:1000',
        ]);

        $aaIds = array_map('intval', $request->aa_ids);

        // Validar que pertenecen al destinatario correcto
        $validIds  = $getValidIds();
        $requested = collect($aaIds);
        if ($requested->diff($validIds)->isNotEmpty()) {
            return back()->with('error', 'Algunos activos seleccionados no son válidos.');
        }

        $items = AssignmentAsset::whereIn('id', $aaIds)
            ->whereNull('returned_at')
            ->with('assignment')
            ->get();

        if ($items->isEmpty()) {
            return back()->with('error', 'No se encontraron activos válidos para devolver.');
        }

        $availableStatus = Status::where('name', 'Disponible')->firstOrFail();
        $returnedAaIds   = [];
        $acta            = null;

        DB::transaction(function () use (
            $items, $availableStatus, $request, &$returnedAaIds, &$acta, $generateActa
        ) {
            foreach ($items as $item) {
                $item->update([
                    'returned_at'  => now(),
                    'return_notes' => $request->return_notes,
                    'returned_by'  => auth()->id(),
                ]);

                $asset = Asset::find($item->asset_id);
                if ($asset) {
                    $asset->update(['status_id' => $availableStatus->id]);
                    AssetEvent::log($asset, 'devolucion', 'Disponible', [
                        'assignment_id' => $item->assignment_id,
                        'notes'         => $request->return_notes,
                    ]);
                }

                $item->assignment?->refreshStatus();
                $returnedAaIds[] = $item->id;
            }

            if (!empty($returnedAaIds)) {
                $acta = $generateActa($returnedAaIds);
            }
        });

        if ($acta) {
            return redirect()
                ->route('actas.show', $acta)
                ->with('success', 'Devolución registrada. Se generó el Acta de Devolución.');
        }

        return redirect()->to($fallbackRoute)->with('success', 'Devolución registrada correctamente.');
    }
}
