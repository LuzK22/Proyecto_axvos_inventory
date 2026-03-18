<?php

namespace App\Http\Controllers;

use App\Models\Area;
use App\Models\Branch;
use Illuminate\Http\Request;

class AreaController extends Controller
{
    public function index()
    {
        $areas = Area::with('branch')->orderBy('name')->paginate(25);
        return view('areas.index', compact('areas'));
    }

    public function create()
    {
        $branches = Branch::where('active', true)->orderBy('name')->get();
        return view('areas.create', compact('branches'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'branch_id'   => 'nullable|exists:branches,id',
        ]);

        Area::create($request->only('name', 'description', 'branch_id') + ['active' => true]);

        // Si se abrió desde una ventana nueva (popup), cerrarla
        if ($request->has('_popup')) {
            return view('areas.close_popup');
        }

        return redirect()->route('areas.index')->with('success', 'Área creada correctamente.');
    }

    public function edit(Area $area)
    {
        $branches = Branch::where('active', true)->orderBy('name')->get();
        return view('areas.edit', compact('area', 'branches'));
    }

    public function update(Request $request, Area $area)
    {
        $request->validate([
            'name'        => 'required|string|max:100',
            'description' => 'nullable|string|max:255',
            'branch_id'   => 'nullable|exists:branches,id',
            'active'      => 'boolean',
        ]);

        $area->update($request->only('name', 'description', 'branch_id', 'active'));
        return redirect()->route('areas.index')->with('success', 'Área actualizada.');
    }
}
