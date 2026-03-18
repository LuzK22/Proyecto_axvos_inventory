<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Assignment;
use App\Models\AssignmentAsset;
use App\Models\Collaborator;
use App\Models\Loan;
use App\Models\Status;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AdminController extends Controller
{
    public function dashboard()
    {
        // ── Estadísticas TI ────────────────────────────────────────────
        $tiAssets    = Asset::whereHas('type', fn($q) => $q->where('category', 'TI'))->count();
        $tiAssigned  = Asset::whereHas('type', fn($q) => $q->where('category', 'TI'))
                            ->whereHas('status', fn($q) => $q->where('name', 'Asignado'))->count();
        $tiAvailable = Asset::whereHas('type', fn($q) => $q->where('category', 'TI'))
                            ->whereHas('status', fn($q) => $q->where('name', 'Disponible'))->count();

        // ── Estadísticas Otros Activos ─────────────────────────────────
        $otroAssets    = Asset::whereHas('type', fn($q) => $q->where('category', 'OTRO'))->count();
        $otroAssigned  = Asset::whereHas('type', fn($q) => $q->where('category', 'OTRO'))
                              ->whereHas('status', fn($q) => $q->where('name', 'Asignado'))->count();
        $otroAvailable = Asset::whereHas('type', fn($q) => $q->where('category', 'OTRO'))
                              ->whereHas('status', fn($q) => $q->where('name', 'Disponible'))->count();

        // ── Chart: Top 5 tipos de activo OTRO ─────────────────────────
        $topOtroTypes = DB::table('assets')
            ->join('asset_types', 'assets.asset_type_id', '=', 'asset_types.id')
            ->where('asset_types.category', 'OTRO')
            ->select('asset_types.name', DB::raw('count(*) as total'))
            ->groupBy('asset_types.name')
            ->orderByDesc('total')
            ->limit(5)
            ->pluck('total', 'name');

        // ── Colaboradores ──────────────────────────────────────────────
        $totalCollaborators  = Collaborator::where('active', true)->count();
        $remoteCollaborators = Collaborator::where('active', true)
                                           ->where('modalidad_trabajo', 'remoto')->count();

        // ── Total general de activos (TI + Otros) ─────────────────────
        $totalAssets = $tiAssets + $otroAssets;

        // ── Asignaciones activas (TI) ──────────────────────────────────
        $activeAssignments = Assignment::where('status', 'activa')->count();

        // ── Asignaciones activas de Otros Activos ─────────────────────
        $otroActiveAssignments = AssignmentAsset::whereNull('returned_at')
            ->whereHas('asset.type', fn($q) => $q->where('category', 'OTRO'))
            ->count();

        // ── Chart: TI por estado ───────────────────────────────────────
        $tiByStatus = DB::table('assets')
            ->join('asset_types', 'assets.asset_type_id', '=', 'asset_types.id')
            ->join('statuses',    'assets.status_id',     '=', 'statuses.id')
            ->where('asset_types.category', 'TI')
            ->select('statuses.name as status', DB::raw('count(*) as total'))
            ->groupBy('statuses.name')
            ->pluck('total', 'status');

        // ── Chart: Otros Activos por estado ───────────────────────────
        $otroByStatus = DB::table('assets')
            ->join('asset_types', 'assets.asset_type_id', '=', 'asset_types.id')
            ->join('statuses',    'assets.status_id',     '=', 'statuses.id')
            ->where('asset_types.category', 'OTRO')
            ->select('statuses.name as status', DB::raw('count(*) as total'))
            ->groupBy('statuses.name')
            ->pluck('total', 'status');

        // ── Chart: Asignaciones activas por modalidad ──────────────────
        $assignmentsByModality = DB::table('assignments')
            ->where('status', 'activa')
            ->select('work_modality', DB::raw('count(*) as total'))
            ->groupBy('work_modality')
            ->pluck('total', 'work_modality');

        // ── Chart: Top 5 tipos de activo TI ───────────────────────────
        $topAssetTypes = DB::table('assets')
            ->join('asset_types', 'assets.asset_type_id', '=', 'asset_types.id')
            ->where('asset_types.category', 'TI')
            ->select('asset_types.name', DB::raw('count(*) as total'))
            ->groupBy('asset_types.name')
            ->orderByDesc('total')
            ->limit(5)
            ->pluck('total', 'name');

        // ── Estadísticas de Préstamos ──────────────────────────────────
        $activeLoans  = Loan::active()->count();
        $overdueLoans = Loan::overdue()->count();
        $dueSoonLoans = Loan::dueSoon(7)->count();

        // Préstamos TI activos: vencidos + los que vencen en 7 días
        // Ordenados por end_date para mostrar los más urgentes primero
        $upcomingLoans = Loan::with(['asset.type', 'collaborator'])
            ->active()
            ->whereHas('asset.type', fn($q) => $q->where('category', 'TI'))
            ->where('end_date', '<=', now()->addDays(7)->endOfDay())
            ->orderBy('end_date')
            ->limit(6)
            ->get();

        // ── Actividades recientes (últimas 8) ─────────────────────────
        $recentActivities = AssignmentAsset::with([
                'assignment.collaborator',
                'asset.type',
                'returnedBy',
            ])
            ->orderBy('updated_at', 'desc')
            ->limit(8)
            ->get();

        return view('admin.dashboard', compact(
            'totalAssets',
            'tiAssets', 'tiAssigned', 'tiAvailable',
            'otroAssets', 'otroAssigned', 'otroAvailable',
            'otroActiveAssignments',
            'totalCollaborators', 'remoteCollaborators',
            'activeAssignments',
            'activeLoans', 'overdueLoans', 'dueSoonLoans', 'upcomingLoans',
            'tiByStatus', 'otroByStatus',
            'assignmentsByModality', 'topAssetTypes', 'topOtroTypes',
            'recentActivities'
        ));
    }
}
