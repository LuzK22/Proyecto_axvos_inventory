<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\AssetType;
use App\Models\Assignment;
use App\Models\AssignmentAsset;
use App\Models\Branch;
use App\Models\Collaborator;
use App\Models\Status;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    // ── REPORTES TI ────────────────────────────────────────────────────

    public function tech(Request $request)
    {
        $category = 'TI';
        $q = $this->baseQuery($category, $request);
        $assets   = $q->orderBy('internal_code')->paginate(25)->withQueryString();
        $stats    = $this->stats($category);
        $types    = AssetType::where('category', $category)->orderBy('name')->get();
        $branches = Branch::where('active', true)->orderBy('name')->get();
        $statuses = Status::orderBy('name')->get();

        return view('tech.reports.hub', compact('assets', 'stats', 'types', 'branches', 'statuses'));
    }

    public function techExport(Request $request)
    {
        $q    = $this->baseQuery('TI', $request);
        $rows = $q->orderBy('internal_code')->get();

        $headers = ['Código','Tipo','Marca','Modelo','Serial','Asset Tag','Estado','Sucursal','Propiedad','Fecha Ingreso'];

        return $this->csvResponse($rows->map(fn($a) => [
            $a->internal_code, $a->type?->name, $a->brand, $a->model,
            $a->serial, $a->asset_tag, $a->status?->name, $a->branch?->name,
            $a->property_type, $a->created_at?->format('d/m/Y'),
        ]), $headers, 'reporte_activos_ti_' . now()->format('Ymd_His') . '.csv');
    }

    /** Exporta activos TI con campos contables NIIF NIC 16 */
    public function techNiifExport(Request $request)
    {
        $q    = $this->baseQuery('TI', $request);
        $rows = $q->orderBy('internal_code')->get();

        \App\Models\ExportLog::record('activos_ti_niif', 'csv', $request->all(), $rows->count());

        $headers = [
            'Cód. Inventario','Cód. Activo Fijo','Tipo','Marca','Modelo','Serial',
            'Propiedad','Valor Compra','Fecha Compra','Proveedor',
            'Vida Útil (años)','Método Depreciación','Valor Residual',
            'Inicio Depreciación','Depreciación Anual','Valor en Libros',
            'Cuenta PUC','Sucursal','Estado',
        ];

        return $this->csvResponse($rows->map(fn($a) => [
            $a->internal_code,
            $a->fixed_asset_code ?? 'PENDIENTE',
            $a->type?->name,
            $a->brand,
            $a->model,
            $a->serial,
            $a->property_type,
            $a->purchase_value     ? number_format($a->purchase_value, 2, '.', '') : '',
            $a->purchase_date      ? $a->purchase_date->format('d/m/Y') : '',
            $a->provider_name      ?? '',
            $a->useful_life_years  ?? '',
            $a->depreciation_method ?? '',
            $a->residual_value     ? number_format($a->residual_value, 2, '.', '') : '',
            $a->depreciation_start_date ? $a->depreciation_start_date->format('d/m/Y') : '',
            $a->annual_depreciation ? number_format($a->annual_depreciation, 2, '.', '') : '',
            $a->current_book_value  ? number_format($a->current_book_value, 2, '.', '') : '',
            $a->account_code        ?? '',
            $a->branch?->name,
            $a->status?->name,
        ]), $headers, 'niif_activos_ti_' . now()->format('Ymd_His') . '.csv');
    }

    // ── REPORTES OTRO ───────────────────────────────────────────────────

    public function assets(Request $request)
    {
        $category = 'OTRO';
        $q = $this->baseQuery($category, $request);

        if ($request->filled('subcategory')) {
            $q->whereHas('type', fn($sq) => $sq->where('subcategory', $request->subcategory));
        }

        $assets        = $q->orderBy('internal_code')->paginate(25)->withQueryString();
        $stats         = $this->stats($category);
        $types         = AssetType::where('category', $category)->orderBy('name')->get();
        $branches      = Branch::where('active', true)->orderBy('name')->get();
        $statuses      = Status::orderBy('name')->get();
        $subcategories = AssetType::where('category', $category)->whereNotNull('subcategory')->distinct()->pluck('subcategory');

        return view('assets.reports.hub', compact('assets', 'stats', 'types', 'branches', 'statuses', 'subcategories'));
    }

    public function assetsExport(Request $request)
    {
        $q = $this->baseQuery('OTRO', $request);
        if ($request->filled('subcategory')) {
            $q->whereHas('type', fn($sq) => $sq->where('subcategory', $request->subcategory));
        }
        $rows = $q->orderBy('internal_code')->get();

        $headers = ['Código','Tipo','Subcategoría','Nombre','Marca','Estado','Sucursal','Propiedad','Fecha Ingreso'];

        return $this->csvResponse($rows->map(fn($a) => [
            $a->internal_code, $a->type?->name, $a->type?->subcategory,
            $a->brand, $a->model, $a->status?->name, $a->branch?->name,
            $a->property_type, $a->created_at?->format('d/m/Y'),
        ]), $headers, 'reporte_otros_activos_' . now()->format('Ymd_His') . '.csv');
    }

    /** Exporta otros activos con campos contables NIIF NIC 16 */
    public function assetsNiifExport(Request $request)
    {
        $q = $this->baseQuery('OTRO', $request);
        if ($request->filled('subcategory')) {
            $q->whereHas('type', fn($sq) => $sq->where('subcategory', $request->subcategory));
        }
        $rows = $q->orderBy('internal_code')->get();

        \App\Models\ExportLog::record('otros_activos_niif', 'csv', $request->all(), $rows->count());

        $headers = [
            'Cód. Inventario','Cód. Activo Fijo','Tipo','Subcategoría','Nombre','Marca',
            'Propiedad','Valor Compra','Fecha Compra','Proveedor',
            'Vida Útil (años)','Método Depreciación','Valor Residual',
            'Inicio Depreciación','Depreciación Anual','Valor en Libros',
            'Cuenta PUC','Sucursal','Estado',
        ];

        return $this->csvResponse($rows->map(fn($a) => [
            $a->internal_code,
            $a->fixed_asset_code    ?? 'PENDIENTE',
            $a->type?->name,
            $a->type?->subcategory  ?? '',
            $a->brand,
            $a->model,
            $a->property_type,
            $a->purchase_value     ? number_format($a->purchase_value, 2, '.', '') : '',
            $a->purchase_date      ? $a->purchase_date->format('d/m/Y') : '',
            $a->provider_name      ?? '',
            $a->useful_life_years  ?? '',
            $a->depreciation_method ?? '',
            $a->residual_value     ? number_format($a->residual_value, 2, '.', '') : '',
            $a->depreciation_start_date ? $a->depreciation_start_date->format('d/m/Y') : '',
            $a->annual_depreciation ? number_format($a->annual_depreciation, 2, '.', '') : '',
            $a->current_book_value  ? number_format($a->current_book_value, 2, '.', '') : '',
            $a->account_code        ?? '',
            $a->branch?->name,
            $a->status?->name,
        ]), $headers, 'niif_otros_activos_' . now()->format('Ymd_His') . '.csv');
    }

    // ── REPORTE COLABORADORES CON ACTIVOS ──────────────────────────────

    public function collaboratorsExport(Request $request)
    {
        $collaborators = Collaborator::with(['assignments.assignmentAssets.asset.type', 'branch'])
            ->where('active', true)
            ->orderBy('full_name')
            ->get();

        $headers = ['Colaborador','Cédula','Cargo','Sucursal','Total Activos','Activos TI','Otros Activos','Asignación más reciente'];
        $rows = $collaborators->map(function ($c) {
            $activeAssets = $c->assignments->where('status','activa')
                ->flatMap(fn($a) => $a->assignmentAssets->whereNull('returned_at'));
            $ti   = $activeAssets->filter(fn($aa) => $aa->asset?->type?->category === 'TI')->count();
            $otro = $activeAssets->filter(fn($aa) => $aa->asset?->type?->category === 'OTRO')->count();
            $lastAsn = $c->assignments->sortByDesc('assignment_date')->first();
            return [
                $c->full_name, $c->document, $c->position, $c->branch?->name,
                $ti + $otro, $ti, $otro,
                $lastAsn?->assignment_date?->format('d/m/Y') ?? 'Sin asignaciones',
            ];
        });

        return $this->csvResponse($rows, $headers, 'reporte_colaboradores_activos_' . now()->format('Ymd_His') . '.csv');
    }

    // ── REPORTES GENERALES ──────────────────────────────────────────────

    public function global()
    {
        return view('reports.global');
    }

    public function index()
    {
        return view('reports.index');
    }

    // ── Helpers privados ────────────────────────────────────────────────

    private function baseQuery(string $category, Request $request)
    {
        $q = Asset::with(['type', 'status', 'branch'])
            ->whereHas('type', fn($q) => $q->where('category', $category));

        if ($request->filled('q')) {
            $s = $request->q;
            $q->where(fn($sq) =>
                $sq->where('internal_code', 'like', "%{$s}%")
                   ->orWhere('brand', 'like', "%{$s}%")
                   ->orWhere('model', 'like', "%{$s}%")
                   ->orWhere('serial', 'like', "%{$s}%")
            );
        }
        if ($request->filled('status_id'))   $q->where('status_id',    $request->status_id);
        if ($request->filled('branch_id'))   $q->where('branch_id',    $request->branch_id);
        if ($request->filled('type_id'))     $q->where('asset_type_id',$request->type_id);
        if ($request->filled('property_type')) $q->where('property_type', $request->property_type);

        return $q;
    }

    private function stats(string $category): array
    {
        $base = fn() => Asset::whereHas('type', fn($q) => $q->where('category', $category));
        return [
            'total'       => $base()->count(),
            'asignados'   => $base()->whereHas('status', fn($q) => $q->where('name','like','%Asignado%'))->count(),
            'disponibles' => $base()->whereHas('status', fn($q) => $q->where('name','like','%Disponible%'))->count(),
            'baja'        => $base()->whereHas('status', fn($q) => $q->where('name','like','%Baja%'))->count(),
        ];
    }

    private function csvResponse($rows, array $headers, string $filename)
    {
        return response()->streamDownload(function () use ($rows, $headers) {
            $out = fopen('php://output', 'w');
            fputs($out, "\xEF\xBB\xBF");
            fputcsv($out, $headers, ';');
            foreach ($rows as $row) {
                fputcsv($out, array_map(fn($v) => $v ?? '', (array) $row), ';');
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
