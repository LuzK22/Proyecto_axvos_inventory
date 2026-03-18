<?php

namespace App\Http\Controllers;

use App\Models\Collaborator;
use App\Models\Branch;
use App\Models\AssignmentAsset;
use App\Models\Loan;
use Illuminate\Http\Request;

class CollaboratorController extends Controller
{
    /* =========================================================
     | LISTADO
     ========================================================= */

    public function index(Request $request)
    {
        $query = Collaborator::with('branch')
            // Contamos activos asignados actualmente para mostrar badge en la tabla
            ->withCount(['assignments as active_assignments_count' => fn($q) => $q->where('status', 'activa')]);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('full_name', 'like', "%{$s}%")
                  ->orWhere('document',  'like', "%{$s}%")
                  ->orWhere('email',     'like', "%{$s}%")
                  ->orWhere('area',      'like', "%{$s}%");
            });
        }

        if ($request->filled('modalidad')) {
            $query->where('modalidad_trabajo', $request->modalidad);
        }

        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->branch_id);
        }

        // Filtro activo/inactivo; por defecto solo activos
        $activeFilter = $request->get('active', '1');
        if ($activeFilter !== 'all') {
            $query->where('active', (bool) $activeFilter);
        }

        $collaborators = $query->orderBy('full_name')->paginate(20)->withQueryString();
        $branches      = Branch::where('active', true)->orderBy('name')->get();

        return view('collaborators.index', compact('collaborators', 'branches', 'activeFilter'));
    }

    /* =========================================================
     | FORMULARIO CREAR
     ========================================================= */

    public function create()
    {
        $branches = Branch::where('active', true)->orderBy('name')->get();
        return view('collaborators.create', compact('branches'));
    }

    /* =========================================================
     | GUARDAR
     ========================================================= */

    public function store(Request $request)
    {
        $request->validate([
            'full_name'         => 'required|string|max:255',
            'document'          => 'required|string|unique:collaborators,document',
            'email'             => 'nullable|email|max:255',
            'phone'             => 'nullable|string|max:20',
            'position'          => 'nullable|string|max:150',
            'area'              => 'nullable|string|max:150',
            'modalidad_trabajo' => 'required|in:presencial,remoto,hibrido',
            'branch_id'         => 'required|exists:branches,id',
        ]);

        Collaborator::create($request->only([
            'full_name', 'document', 'email', 'phone',
            'position', 'area', 'modalidad_trabajo', 'branch_id',
        ]) + ['active' => true]);

        return redirect()
            ->route('collaborators.index')
            ->with('success', 'Colaborador <strong>' . $request->full_name . '</strong> creado correctamente.');
    }

    /* =========================================================
     | FORMULARIO EDITAR
     ========================================================= */

    public function edit(Collaborator $collaborator)
    {
        $branches = Branch::where('active', true)->orderBy('name')->get();
        return view('collaborators.edit', compact('collaborator', 'branches'));
    }

    /* =========================================================
     | ACTUALIZAR
     ========================================================= */

    public function update(Request $request, Collaborator $collaborator)
    {
        $request->validate([
            'full_name'         => 'required|string|max:255',
            'document'          => 'required|string|unique:collaborators,document,' . $collaborator->id,
            'email'             => 'nullable|email|max:255',
            'phone'             => 'nullable|string|max:20',
            'position'          => 'nullable|string|max:150',
            'area'              => 'nullable|string|max:150',
            'modalidad_trabajo' => 'required|in:presencial,remoto,hibrido',
            'branch_id'         => 'required|exists:branches,id',
        ]);

        $collaborator->update($request->only([
            'full_name', 'document', 'email', 'phone',
            'position', 'area', 'modalidad_trabajo', 'branch_id',
        ]) + ['active' => $request->boolean('active', true)]);

        return redirect()
            ->route('collaborators.show', $collaborator)
            ->with('success', 'Colaborador actualizado correctamente.');
    }

    /* =========================================================
     | PERFIL / EXPEDIENTE DIGITAL
     ========================================================= */

    public function show(Collaborator $collaborator)
    {
        $collaborator->load('branch');

        // Base: activos actualmente asignados a este colaborador
        $baseQuery = AssignmentAsset::whereNull('returned_at')
            ->whereHas('assignment', fn($q) => $q->where('collaborator_id', $collaborator->id))
            ->with(['asset.type', 'asset.status', 'assignment']);

        // Activos TI asignados
        $tiItems = (clone $baseQuery)
            ->whereHas('asset.type', fn($q) => $q->where('category', 'TI'))
            ->get();

        // Otros activos asignados
        $otroItems = (clone $baseQuery)
            ->whereHas('asset.type', fn($q) => $q->where('category', 'OTRO'))
            ->get();

        // Préstamos activos para este colaborador
        $activeLoans = Loan::with('asset.type')
            ->where('collaborator_id', $collaborator->id)
            ->where('status', 'activo')
            ->orderBy('end_date')
            ->get();

        // Historial completo de asignaciones
        $assignmentHistory = $collaborator
            ->assignments()
            ->with(['assignedBy', 'assignmentAssets.asset.type'])
            ->orderBy('assignment_date', 'desc')
            ->get();

        // Stats para el header del expediente
        $stats = [
            'ti'       => $tiItems->count(),
            'otro'     => $otroItems->count(),
            'loans'    => $activeLoans->count(),
            'history'  => $assignmentHistory->count(),
        ];

        return view('collaborators.show', compact(
            'collaborator',
            'tiItems',
            'otroItems',
            'activeLoans',
            'assignmentHistory',
            'stats'
        ));
    }
}
