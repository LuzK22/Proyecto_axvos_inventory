<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Assignment;
use App\Models\AssignmentAsset;
use App\Models\Branch;
use App\Models\Collaborator;
use App\Models\Loan;
use App\Models\Status;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    private const PER_PAGE = 25;

    public function index(Request $request)
    {
        return $this->hub($request);
    }

    /* =========================================================
     | HUB PRINCIPAL
     ========================================================= */

    public function hub(Request $request)
    {
        $tab       = $request->get('tab', 'ti');
        $branches  = Branch::where('active', true)->orderBy('name')->get();
        $tiTypes   = AssetType::where('category', 'TI')->orderBy('name')->get();
        $otroTypes = AssetType::where('category', 'OTRO')->orderBy('name')->get();
        $statuses  = Status::orderBy('name')->get();
        $stats     = $this->globalStats();

        $data = match ($tab) {
            'otros'        => $this->queryOtros($request),
            'prestamos'    => $this->queryPrestamos($request),
            'asignaciones' => $this->queryAsignaciones($request),
            'log'          => $this->queryLog($request),
            default        => $this->queryTi($request),
        };

        return view('audit.hub', compact(
            'tab', 'stats', 'data',
            'branches', 'tiTypes', 'otroTypes', 'statuses'
        ));
    }

    /* =========================================================
     | EXPORTAR CSV (abre directo en Excel con BOM UTF-8)
     ========================================================= */

    public function export(Request $request)
    {
        $tab = $request->get('tab', 'ti');

        [$rows, $headers, $filename] = match ($tab) {
            'otros'        => $this->exportOtros($request),
            'prestamos'    => $this->exportPrestamos($request),
            'asignaciones' => $this->exportAsignaciones($request),
            'log'          => $this->exportLog($request),
            default        => $this->exportTi($request),
        };

        return response()->streamDownload(function () use ($rows, $headers) {
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF"); // BOM para que Excel reconozca UTF-8
            fputcsv($out, $headers, ';');
            foreach ($rows as $row) {
                fputcsv($out, array_map(fn($v) => $v ?? '', (array) $row), ';');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /* =========================================================
     | STATS GLOBALES HEADER
     ========================================================= */

    private function globalStats(): array
    {
        return [
            'total_assets'       => Asset::count(),
            'ti_assets'          => Asset::whereHas('type', fn($q) => $q->where('category', 'TI'))->count(),
            'otro_assets'        => Asset::whereHas('type', fn($q) => $q->where('category', 'OTRO'))->count(),
            'active_assignments' => Assignment::where('status', 'activa')->count(),
            'active_loans'       => Loan::active()->count(),
            'overdue_loans'      => Loan::overdue()->count(),
            'collaborators'      => Collaborator::where('active', true)->count(),
        ];
    }

    /* =========================================================
     | FILTROS COMUNES ACTIVOS
     ========================================================= */

    private function applyAssetFilters($q, Request $r): void
    {
        if ($r->filled('search')) {
            $s = $r->search;
            $q->where(fn($sq) =>
                $sq->where('internal_code', 'like', "%{$s}%")
                   ->orWhere('brand',        'like', "%{$s}%")
                   ->orWhere('model',        'like', "%{$s}%")
                   ->orWhere('serial',       'like', "%{$s}%")
                   ->orWhere('asset_tag',    'like', "%{$s}%")
            );
        }
        if ($r->filled('type_id'))       $q->where('asset_type_id', $r->type_id);
        if ($r->filled('status_id'))     $q->where('status_id', $r->status_id);
        if ($r->filled('branch_id'))     $q->where('branch_id', $r->branch_id);
        if ($r->filled('property_type')) $q->where('property_type', $r->property_type);
        if ($r->filled('from'))          $q->where('created_at', '>=', $r->from . ' 00:00:00');
        if ($r->filled('to'))            $q->where('created_at', '<=', $r->to . ' 23:59:59');
    }

    /* =========================================================
     | QUERIES PARA VISTA (paginadas)
     ========================================================= */

    private function queryTi(Request $r)
    {
        $q = Asset::with(['type', 'status', 'branch'])
            ->whereHas('type', fn($q) => $q->where('category', 'TI'));
        $this->applyAssetFilters($q, $r);
        return $q->orderBy('internal_code')->paginate(self::PER_PAGE)->withQueryString();
    }

    private function queryOtros(Request $r)
    {
        $q = Asset::with(['type', 'status', 'branch'])
            ->whereHas('type', fn($q) => $q->where('category', 'OTRO'));
        $this->applyAssetFilters($q, $r);
        return $q->orderBy('internal_code')->paginate(self::PER_PAGE)->withQueryString();
    }

    private function queryPrestamos(Request $r)
    {
        $q = Loan::with(['asset.type', 'collaborator.branch', 'creator']);
        if ($r->filled('loan_status'))  $q->where('status', $r->loan_status);
        if ($r->filled('from'))         $q->where('start_date', '>=', $r->from);
        if ($r->filled('to'))           $q->where('end_date', '<=', $r->to);
        if ($r->filled('branch_id'))    $q->whereHas('collaborator',
            fn($sq) => $sq->where('branch_id', $r->branch_id));
        if ($r->filled('collaborator')) $q->whereHas('collaborator',
            fn($sq) => $sq->where('full_name', 'like', "%{$r->collaborator}%")
                          ->orWhere('document', 'like', "%{$r->collaborator}%"));
        return $q->orderByDesc('start_date')->paginate(self::PER_PAGE)->withQueryString();
    }

    private function queryAsignaciones(Request $r)
    {
        $q = Assignment::with(['collaborator.branch', 'assignedBy', 'assignmentAssets.asset.type']);
        if ($r->filled('assign_status')) $q->where('status', $r->assign_status);
        if ($r->filled('from'))          $q->where('assignment_date', '>=', $r->from);
        if ($r->filled('to'))            $q->where('assignment_date', '<=', $r->to);
        if ($r->filled('modality'))      $q->where('work_modality', $r->modality);
        if ($r->filled('branch_id'))     $q->whereHas('collaborator',
            fn($sq) => $sq->where('branch_id', $r->branch_id));
        if ($r->filled('collaborator'))  $q->whereHas('collaborator',
            fn($sq) => $sq->where('full_name', 'like', "%{$r->collaborator}%")
                          ->orWhere('document', 'like', "%{$r->collaborator}%"));
        return $q->orderByDesc('assignment_date')->paginate(self::PER_PAGE)->withQueryString();
    }

    private function queryLog(Request $r)
    {
        $q = AssignmentAsset::with([
            'assignment.collaborator.branch', 'asset.type', 'returnedBy',
        ]);
        if ($r->filled('action')) {
            if ($r->action === 'asignado') $q->whereNull('returned_at');
            if ($r->action === 'devuelto') $q->whereNotNull('returned_at');
        }
        if ($r->filled('from'))         $q->where('created_at', '>=', $r->from . ' 00:00:00');
        if ($r->filled('to'))           $q->where('created_at', '<=', $r->to . ' 23:59:59');
        if ($r->filled('branch_id'))    $q->whereHas('assignment.collaborator',
            fn($sq) => $sq->where('branch_id', $r->branch_id));
        if ($r->filled('collaborator')) $q->whereHas('assignment.collaborator',
            fn($sq) => $sq->where('full_name', 'like', "%{$r->collaborator}%")
                          ->orWhere('document', 'like', "%{$r->collaborator}%"));
        return $q->orderByDesc('updated_at')->paginate(self::PER_PAGE)->withQueryString();
    }

    /* =========================================================
     | EXPORTS CSV
     ========================================================= */

    private function exportTi(Request $r): array
    {
        $q = Asset::with(['type', 'status', 'branch'])
            ->whereHas('type', fn($q) => $q->where('category', 'TI'));
        $this->applyAssetFilters($q, $r);
        $rows = $q->orderBy('internal_code')->get()->map(fn($a) => [
            $a->internal_code, $a->type?->name, $a->brand, $a->model, $a->serial,
            $a->asset_tag, $a->status?->name, $a->branch?->name,
            $a->property_type, $a->created_at?->format('d/m/Y'),
        ]);
        return [$rows,
            ['Codigo','Tipo','Marca','Modelo','Serial','Asset Tag','Estado','Sucursal','Propiedad','Fecha Ingreso'],
            'auditoria_activos_ti_' . now()->format('Ymd_His') . '.csv'];
    }

    private function exportOtros(Request $r): array
    {
        $q = Asset::with(['type', 'status', 'branch'])
            ->whereHas('type', fn($q) => $q->where('category', 'OTRO'));
        $this->applyAssetFilters($q, $r);
        $rows = $q->orderBy('internal_code')->get()->map(fn($a) => [
            $a->internal_code, $a->type?->name, $a->brand, $a->model, $a->serial,
            $a->asset_tag, $a->status?->name, $a->branch?->name,
            $a->property_type, $a->created_at?->format('d/m/Y'),
        ]);
        return [$rows,
            ['Codigo','Tipo','Marca','Modelo','Serial','Asset Tag','Estado','Sucursal','Propiedad','Fecha Ingreso'],
            'auditoria_otros_activos_' . now()->format('Ymd_His') . '.csv'];
    }

    private function exportPrestamos(Request $r): array
    {
        $q = Loan::with(['asset.type', 'collaborator.branch', 'creator']);
        if ($r->filled('loan_status'))  $q->where('status', $r->loan_status);
        if ($r->filled('from'))         $q->where('start_date', '>=', $r->from);
        if ($r->filled('to'))           $q->where('end_date', '<=', $r->to);
        if ($r->filled('branch_id'))    $q->whereHas('collaborator',
            fn($sq) => $sq->where('branch_id', $r->branch_id));
        if ($r->filled('collaborator')) $q->whereHas('collaborator',
            fn($sq) => $sq->where('full_name', 'like', "%{$r->collaborator}%")
                          ->orWhere('document', 'like', "%{$r->collaborator}%"));
        $rows = $q->orderByDesc('start_date')->get()->map(fn($l) => [
            $l->id, $l->asset?->internal_code, $l->asset?->type?->name,
            $l->collaborator?->full_name, $l->collaborator?->document,
            $l->collaborator?->branch?->name,
            $l->start_date?->format('d/m/Y'), $l->end_date?->format('d/m/Y'),
            $l->returned_at?->format('d/m/Y H:i') ?? '',
            $l->status, $l->notes, $l->creator?->name,
        ]);
        return [$rows,
            ['ID','Activo','Tipo','Colaborador','Cedula','Sucursal','Inicio','Vence','Devuelto','Estado','Notas','Creado por'],
            'auditoria_prestamos_' . now()->format('Ymd_His') . '.csv'];
    }

    private function exportAsignaciones(Request $r): array
    {
        $q = Assignment::with(['collaborator.branch', 'assignedBy', 'assignmentAssets.asset']);
        if ($r->filled('assign_status')) $q->where('status', $r->assign_status);
        if ($r->filled('from'))          $q->where('assignment_date', '>=', $r->from);
        if ($r->filled('to'))            $q->where('assignment_date', '<=', $r->to);
        if ($r->filled('modality'))      $q->where('work_modality', $r->modality);
        if ($r->filled('branch_id'))     $q->whereHas('collaborator',
            fn($sq) => $sq->where('branch_id', $r->branch_id));
        if ($r->filled('collaborator'))  $q->whereHas('collaborator',
            fn($sq) => $sq->where('full_name', 'like', "%{$r->collaborator}%")
                          ->orWhere('document', 'like', "%{$r->collaborator}%"));
        $rows = $q->orderByDesc('assignment_date')->get()->map(fn($a) => [
            $a->id, $a->collaborator?->full_name, $a->collaborator?->document,
            $a->collaborator?->branch?->name, $a->work_modality,
            $a->assignment_date?->format('d/m/Y'),
            $a->assignmentAssets->pluck('asset.internal_code')->filter()->implode(', '),
            $a->status, $a->assignedBy?->name,
        ]);
        return [$rows,
            ['ID','Colaborador','Cedula','Sucursal','Modalidad','Fecha','Activos','Estado','Registrado por'],
            'auditoria_asignaciones_' . now()->format('Ymd_His') . '.csv'];
    }

    private function exportLog(Request $r): array
    {
        $q = AssignmentAsset::with([
            'assignment.collaborator.branch', 'asset.type', 'returnedBy',
        ]);
        if ($r->filled('action')) {
            if ($r->action === 'asignado') $q->whereNull('returned_at');
            if ($r->action === 'devuelto') $q->whereNotNull('returned_at');
        }
        if ($r->filled('from'))         $q->where('created_at', '>=', $r->from . ' 00:00:00');
        if ($r->filled('to'))           $q->where('created_at', '<=', $r->to . ' 23:59:59');
        if ($r->filled('branch_id'))    $q->whereHas('assignment.collaborator',
            fn($sq) => $sq->where('branch_id', $r->branch_id));
        if ($r->filled('collaborator')) $q->whereHas('assignment.collaborator',
            fn($sq) => $sq->where('full_name', 'like', "%{$r->collaborator}%")
                          ->orWhere('document', 'like', "%{$r->collaborator}%"));
        $rows = $q->orderByDesc('updated_at')->get()->map(fn($aa) => [
            $aa->returned_at ? 'Devolucion' : 'Asignacion',
            $aa->asset?->internal_code, $aa->asset?->type?->name,
            $aa->assignment?->collaborator?->full_name,
            $aa->assignment?->collaborator?->document,
            $aa->assignment?->collaborator?->branch?->name,
            $aa->assignment_id,
            $aa->created_at?->format('d/m/Y H:i'),
            $aa->returned_at?->format('d/m/Y H:i') ?? '',
            $aa->returnedBy?->name ?? '',
        ]);
        return [$rows,
            ['Evento','Activo','Tipo','Colaborador','Cedula','Sucursal','Asignacion #','Fecha Asignacion','Fecha Devolucion','Devuelto por'],
            'auditoria_log_' . now()->format('Ymd_His') . '.csv'];
    }
}
