<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Assignment;
use App\Models\AssignmentAsset;
use App\Models\Branch;
use App\Models\Collaborator;
use App\Models\ExportLog;
use App\Models\Loan;
use App\Models\Status;
use App\Models\UserSession;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

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
        $stats = $this->globalStats();

        // No tab specified → show landing hub with buttons
        if (! $request->has('tab')) {
            return view('audit.landing', compact('stats'));
        }

        $tab       = $request->get('tab', 'ti');
        $branches  = Branch::where('active', true)->orderBy('name')->get();
        $tiTypes   = AssetType::where('category', 'TI')->orderBy('name')->get();
        $otroTypes = AssetType::where('category', 'OTRO')->orderBy('name')->get();
        $statuses  = Status::orderBy('name')->get();

        $data = match ($tab) {
            'otros'          => $this->queryOtros($request),
            'prestamos'      => $this->queryPrestamos($request),
            'asignaciones'   => $this->queryAsignaciones($request),
            'log'            => $this->queryLog($request),
            'actividad'      => $this->queryActividad($request),
            'bajas'          => $this->queryBajas($request),
            'sesiones'       => $this->querySesiones($request),
            'exportaciones'  => $this->queryExportaciones($request),
            default          => $this->queryTi($request),
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
            'otros'         => $this->exportOtros($request),
            'prestamos'     => $this->exportPrestamos($request),
            'asignaciones'  => $this->exportAsignaciones($request),
            'log'           => $this->exportLog($request),
            'bajas'         => $this->exportBajas($request),
            'sesiones'      => $this->exportSesiones($request),
            'actividad'     => $this->exportActividad($request),
            'exportaciones' => $this->exportExportaciones($request),
            default         => $this->exportTi($request),
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
        $q = Loan::with(['asset.type', 'collaborator.branch', 'creator', 'destinationBranch']);
        if ($r->filled('loan_status'))    $q->where('status', $r->loan_status);
        if ($r->filled('loan_category'))  $q->whereHas('asset.type',
            fn($sq) => $sq->where('category', $r->loan_category));
        if ($r->filled('from'))           $q->where('start_date', '>=', $r->from);
        if ($r->filled('to'))             $q->where('end_date', '<=', $r->to);
        if ($r->filled('branch_id'))      $q->whereHas('collaborator',
            fn($sq) => $sq->where('branch_id', $r->branch_id));
        if ($r->filled('collaborator'))   $q->whereHas('collaborator',
            fn($sq) => $sq->where('full_name', 'like', "%{$r->collaborator}%"));
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
            fn($sq) => $sq->where('full_name', 'like', "%{$r->collaborator}%"));
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
        if ($r->filled('log_category')) $q->whereHas('asset.type',
            fn($sq) => $sq->where('category', $r->log_category));
        if ($r->filled('from'))         $q->where('created_at', '>=', $r->from . ' 00:00:00');
        if ($r->filled('to'))           $q->where('created_at', '<=', $r->to . ' 23:59:59');
        if ($r->filled('branch_id'))    $q->whereHas('assignment.collaborator',
            fn($sq) => $sq->where('branch_id', $r->branch_id));
        if ($r->filled('collaborator')) $q->whereHas('assignment.collaborator',
            fn($sq) => $sq->where('full_name', 'like', "%{$r->collaborator}%"));
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
        $allHeaders = ['Codigo','Tipo','Marca','Modelo','Serial','Asset Tag','Estado','Sucursal','Propiedad','Fecha Ingreso'];
        [$headers, $filteredRows] = $this->filterExportColumns($r, $allHeaders, $rows);
        return [$filteredRows, $headers, 'auditoria_activos_ti_' . now()->format('Ymd_His') . '.csv'];
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
        $allHeaders = ['Codigo','Tipo','Marca','Modelo','Serial','Asset Tag','Estado','Sucursal','Propiedad','Fecha Ingreso'];
        [$headers, $filteredRows] = $this->filterExportColumns($r, $allHeaders, $rows);
        return [$filteredRows, $headers, 'auditoria_otros_activos_' . now()->format('Ymd_His') . '.csv'];
    }

    private function exportPrestamos(Request $r): array
    {
        $q = Loan::with(['asset.type', 'collaborator.branch', 'creator']);
        if ($r->filled('loan_status'))   $q->where('status', $r->loan_status);
        if ($r->filled('loan_category')) $q->whereHas('asset.type',
            fn($sq) => $sq->where('category', $r->loan_category));
        if ($r->filled('from'))          $q->where('start_date', '>=', $r->from);
        if ($r->filled('to'))            $q->where('end_date', '<=', $r->to);
        if ($r->filled('branch_id'))     $q->whereHas('collaborator',
            fn($sq) => $sq->where('branch_id', $r->branch_id));
        if ($r->filled('collaborator'))  $q->whereHas('collaborator',
            fn($sq) => $sq->where('full_name', 'like', "%{$r->collaborator}%"));
        $rows = $q->orderByDesc('start_date')->get()->map(fn($l) => [
            $l->id, $l->asset?->internal_code, $l->asset?->type?->name,
            $l->collaborator?->full_name, $l->collaborator?->document,
            $l->collaborator?->branch?->name,
            $l->start_date?->format('d/m/Y'), $l->end_date?->format('d/m/Y'),
            $l->returned_at?->format('d/m/Y H:i') ?? '',
            $l->status, $l->notes, $l->creator?->name,
        ]);
        $allHeaders = ['ID','Categoria','Activo','Tipo','Colaborador/Destino','Sucursal','Inicio','Vence','Devuelto','Estado','Notas','Creado por'];
        [$headers, $filteredRows] = $this->filterExportColumns($r, $allHeaders, $rows);
        return [$filteredRows, $headers, 'auditoria_prestamos_' . now()->format('Ymd_His') . '.csv'];
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
            fn($sq) => $sq->where('full_name', 'like', "%{$r->collaborator}%"));
        $rows = $q->orderByDesc('assignment_date')->get()->map(fn($a) => [
            $a->id, $a->collaborator?->full_name, $a->collaborator?->document,
            $a->collaborator?->branch?->name, $a->work_modality,
            $a->assignment_date?->format('d/m/Y'),
            $a->assignmentAssets->pluck('asset.internal_code')->filter()->implode(', '),
            $a->status, $a->assignedBy?->name,
        ]);
        $allHeaders = ['ID','Colaborador','Cedula','Sucursal','Modalidad','Fecha','Activos','Estado','Registrado por'];
        [$headers, $filteredRows] = $this->filterExportColumns($r, $allHeaders, $rows);
        return [$filteredRows, $headers, 'auditoria_asignaciones_' . now()->format('Ymd_His') . '.csv'];
    }

    /** Exporta activos dados de baja (TI y OTRO) con valor en libros NIIF */
    private function exportBajas(Request $r): array
    {
        $statusNames = ['Baja', 'Donado', 'Vendido'];

        $q = Asset::with(['type', 'status', 'branch'])
            ->whereHas('status', fn($sq) => $sq->whereIn('name', $statusNames));

        // Filtros opcionales
        if ($r->filled('category'))  $q->whereHas('type', fn($sq) => $sq->where('category', $r->category));
        if ($r->filled('branch_id')) $q->where('branch_id', $r->branch_id);
        if ($r->filled('from'))      $q->where('updated_at', '>=', $r->from . ' 00:00:00');
        if ($r->filled('to'))        $q->where('updated_at', '<=', $r->to . ' 23:59:59');

        $rows = $q->orderBy('updated_at', 'desc')->get();

        ExportLog::record('bajas_activos', 'csv', $r->all(), $rows->count());

        $data = $rows->map(fn($a) => [
            $a->internal_code,
            $a->fixed_asset_code    ?? 'PENDIENTE',
            $a->type?->category     ?? '',
            $a->type?->name         ?? '',
            $a->brand               ?? '',
            $a->model               ?? '',
            $a->serial              ?? '',
            $a->status?->name       ?? '',
            $a->purchase_value      ? number_format($a->purchase_value, 2, '.', '') : '',
            $a->current_book_value  ? number_format($a->current_book_value, 2, '.', '') : '',
            $a->account_code        ?? '',
            $a->branch?->name       ?? '',
            $a->updated_at?->format('d/m/Y') ?? '',
        ]);

        $allHeaders = ['Cód. Inventario','Cód. Activo Fijo','Categoría','Tipo','Marca','Modelo',
             'Serial','Motivo Baja','Valor Compra','Valor en Libros','Cuenta PUC','Sucursal','Fecha Baja'];
        [$headers, $filteredData] = $this->filterExportColumns($r, $allHeaders, $data);
        return [$filteredData, $headers, 'bajas_activos_' . now()->format('Ymd_His') . '.csv'];
    }

    /** Exporta sesiones activas e históricas de usuarios (ISO 27001) */
    private function exportSesiones(Request $r): array
    {
        $q = UserSession::with(['user.roles', 'user.branch'])
            ->orderByDesc('last_active_at');

        if ($r->filled('from')) $q->where('created_at', '>=', $r->from . ' 00:00:00');
        if ($r->filled('to'))   $q->where('created_at', '<=', $r->to . ' 23:59:59');

        $rows = $q->get();

        ExportLog::record('sesiones_usuarios', 'csv', $r->all(), $rows->count());

        $data = $rows->map(fn($s) => [
            $s->user?->name              ?? 'Desconocido',
            $s->user?->email             ?? '',
            $s->user?->roles->pluck('name')->implode(', ') ?? '',
            $s->user?->branch?->name     ?? '',
            $s->ip_address               ?? '',
            $s->deviceName(),
            $s->created_at?->format('d/m/Y H:i')     ?? '',
            $s->last_active_at?->format('d/m/Y H:i') ?? '',
        ]);

        return [$data,
            ['Usuario','Email','Roles','Sucursal','IP','Dispositivo','Inicio Sesión','Última Actividad'],
            'sesiones_usuarios_' . now()->format('Ymd_His') . '.csv'];
    }

    /* =========================================================
     | ACTIVIDAD DEL SISTEMA — activity_log de Spatie
     ========================================================= */

    private function queryActividad(Request $r)
    {
        $q = Activity::with('causer')
            ->orderByDesc('created_at');

        if ($r->filled('search')) {
            $s = $r->search;
            $q->where(fn($sq) =>
                $sq->where('description', 'like', "%{$s}%")
                   ->orWhere('log_name', 'like', "%{$s}%")
            );
        }

        if ($r->filled('causer_id')) {
            $q->where('causer_id', $r->causer_id)
              ->where('causer_type', 'App\\Models\\User');
        }

        if ($r->filled('log_name')) {
            $q->where('log_name', $r->log_name);
        }

        if ($r->filled('from')) $q->where('created_at', '>=', $r->from . ' 00:00:00');
        if ($r->filled('to'))   $q->where('created_at', '<=', $r->to   . ' 23:59:59');

        return $q->paginate(self::PER_PAGE)->withQueryString();
    }

    private function exportActividad(Request $r): array
    {
        $q = Activity::with('causer')->orderByDesc('created_at');

        if ($r->filled('causer_id')) {
            $q->where('causer_id', $r->causer_id)
              ->where('causer_type', 'App\\Models\\User');
        }
        if ($r->filled('log_name')) $q->where('log_name', $r->log_name);
        if ($r->filled('from'))     $q->where('created_at', '>=', $r->from . ' 00:00:00');
        if ($r->filled('to'))       $q->where('created_at', '<=', $r->to   . ' 23:59:59');

        $rows = $q->get()->map(fn($a) => [
            $a->causer?->name   ?? 'Sistema',
            $a->causer?->email  ?? '',
            $a->log_name        ?? '',
            $a->description,
            $a->subject_type    ? class_basename($a->subject_type) : '',
            $a->subject_id      ?? '',
            $a->properties->except(['old'])->toJson(JSON_UNESCAPED_UNICODE),
            $a->created_at?->format('d/m/Y H:i:s') ?? '',
        ]);

        ExportLog::record('actividad_sistema', 'csv', $r->all(), $rows->count());

        return [$rows,
            ['Usuario','Email','Módulo','Descripción','Tipo Objeto','ID Objeto','Propiedades','Fecha y Hora'],
            'actividad_sistema_' . now()->format('Ymd_His') . '.csv'];
    }

    /* =========================================================
     | BAJAS — query para vista en hub
     ========================================================= */

    private function queryBajas(Request $r)
    {
        $statusNames = ['Baja', 'Donado', 'Vendido'];

        $q = Asset::with(['type', 'status', 'branch'])
            ->whereHas('status', fn($sq) => $sq->whereIn('name', $statusNames));

        if ($r->filled('category'))  $q->whereHas('type', fn($sq) => $sq->where('category', $r->category));
        if ($r->filled('branch_id')) $q->where('branch_id', $r->branch_id);
        if ($r->filled('from'))      $q->where('updated_at', '>=', $r->from . ' 00:00:00');
        if ($r->filled('to'))        $q->where('updated_at', '<=', $r->to   . ' 23:59:59');

        return $q->orderByDesc('updated_at')->paginate(self::PER_PAGE)->withQueryString();
    }

    /* =========================================================
     | SESIONES — query para vista en hub
     ========================================================= */

    private function querySesiones(Request $r)
    {
        $q = UserSession::with(['user.roles', 'user.branch'])
            ->orderByDesc('last_active_at');

        if ($r->filled('search')) {
            $s = $r->search;
            $q->whereHas('user', fn($sq) => $sq->where('name', 'like', "%{$s}%")
                ->orWhere('email', 'like', "%{$s}%"));
        }
        if ($r->filled('from')) $q->where('created_at', '>=', $r->from . ' 00:00:00');
        if ($r->filled('to'))   $q->where('created_at', '<=', $r->to   . ' 23:59:59');

        return $q->paginate(self::PER_PAGE)->withQueryString();
    }

    /* =========================================================
     | EXPORTACIONES — query para vista en hub
     ========================================================= */

    private function queryExportaciones(Request $r)
    {
        $q = ExportLog::with('user')
            ->orderByDesc('created_at');

        if ($r->filled('search')) {
            $s = $r->search;
            $q->where('entity_type', 'like', "%{$s}%")
              ->orWhereHas('user', fn($sq) => $sq->where('name', 'like', "%{$s}%"));
        }
        if ($r->filled('from')) $q->where('created_at', '>=', $r->from . ' 00:00:00');
        if ($r->filled('to'))   $q->where('created_at', '<=', $r->to   . ' 23:59:59');

        return $q->paginate(self::PER_PAGE)->withQueryString();
    }

    /* =========================================================
     | EXPORTAR CSV — Exportaciones realizadas
     ========================================================= */

    private function exportExportaciones(Request $r): array
    {
        $q = ExportLog::with('user')->orderByDesc('created_at');

        if ($r->filled('from')) $q->where('created_at', '>=', $r->from . ' 00:00:00');
        if ($r->filled('to'))   $q->where('created_at', '<=', $r->to   . ' 23:59:59');

        $rows = $q->get()->map(fn($e) => [
            $e->user?->name     ?? 'Sistema',
            $e->user?->email    ?? '',
            $e->entity_type,
            strtoupper($e->format),
            $e->rows_exported,
            $e->ip_address      ?? '',
            $e->created_at?->format('d/m/Y H:i') ?? '',
        ]);

        return [$rows,
            ['Usuario','Email','Módulo Exportado','Formato','Filas','IP','Fecha y Hora'],
            'exportaciones_' . now()->format('Ymd_His') . '.csv'];
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
        if ($r->filled('log_category')) $q->whereHas('asset.type',
            fn($sq) => $sq->where('category', $r->log_category));
        if ($r->filled('from'))         $q->where('created_at', '>=', $r->from . ' 00:00:00');
        if ($r->filled('to'))           $q->where('created_at', '<=', $r->to . ' 23:59:59');
        if ($r->filled('branch_id'))    $q->whereHas('assignment.collaborator',
            fn($sq) => $sq->where('branch_id', $r->branch_id));
        if ($r->filled('collaborator')) $q->whereHas('assignment.collaborator',
            fn($sq) => $sq->where('full_name', 'like', "%{$r->collaborator}%"));

        $allHeaders = ['Evento','Categoria','Activo','Tipo','Colaborador','Cedula','Sucursal','Asignacion #','Fecha Asignacion','Fecha Devolucion','Devuelto por'];
        $allRows = $q->orderByDesc('updated_at')->get()->map(fn($aa) => [
            $aa->returned_at ? 'Devolucion' : 'Asignacion',
            $aa->asset?->type?->category ?? '',
            $aa->asset?->internal_code,
            $aa->asset?->type?->name,
            $aa->assignment?->collaborator?->full_name,
            $aa->assignment?->collaborator?->document,
            $aa->assignment?->collaborator?->branch?->name,
            $aa->assignment_id,
            $aa->created_at?->format('d/m/Y H:i'),
            $aa->returned_at?->format('d/m/Y H:i') ?? '',
            $aa->returnedBy?->name ?? '',
        ]);

        [$headers, $rows] = $this->filterExportColumns($r, $allHeaders, $allRows);
        return [$rows, $headers, 'auditoria_log_' . now()->format('Ymd_His') . '.csv'];
    }

    /* =========================================================
     | HELPER: filtrar columnas del CSV según cols[] del request
     ========================================================= */

    private function filterExportColumns(Request $r, array $headers, $rows): array
    {
        if (! $r->filled('cols')) {
            return [$headers, $rows];
        }
        $selected = array_map('intval', (array) $r->input('cols'));
        $filteredHeaders = array_values(array_filter($headers,
            fn($i) => in_array($i, $selected), ARRAY_FILTER_USE_KEY));
        $filteredRows = $rows->map(function ($row) use ($selected) {
            $arr = array_values((array) $row);
            return array_values(array_filter($arr,
                fn($i) => in_array($i, $selected), ARRAY_FILTER_USE_KEY));
        });
        return [$filteredHeaders, $filteredRows];
    }
}
