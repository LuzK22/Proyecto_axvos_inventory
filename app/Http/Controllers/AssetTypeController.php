<?php

namespace App\Http\Controllers;

use App\Models\AssetType;
use Illuminate\Http\Request;

class AssetTypeController extends Controller
{
    /**
     * Listado de tipos por categoría
     */
    public function index(string $category)
    {
        $this->validateCategory($category);

        $types = AssetType::where('category', $category)
            ->orderBy('name')
            ->get();

        return view('asset_types.index', compact('types', 'category'));
    }

    /**
     * Formulario crear tipo
     */
    public function create(string $category)
    {
        $this->validateCategory($category);

        return view('asset_types.create', compact('category'));
    }

    /**
     * Guardar tipo
     */
    public function store(Request $request)
    {
        $request->validate([
            'name'     => 'required|string|max:255',
            'category' => 'required|in:TI,OTRO',
        ]);

        // Generar código automático (3 letras)
        $code = strtoupper(substr($request->name, 0, 3));

        AssetType::create([
            'name'       => $request->name,
            'code'       => $code,
            'category'   => $request->category,
            'active'     => true,
            'created_by' => auth()->id(), // 👈 auditoría
        ]);

        return redirect()
            ->route('asset-types.index', $request->category)
            ->with('success', 'Tipo de activo creado correctamente');
    }

    /**
     * Formulario editar tipo
     */
    public function edit(AssetType $assetType)
    {
        return view('asset_types.edit', compact('assetType'));
    }

    /**
     * Actualizar tipo
     */
    public function update(Request $request, AssetType $assetType)
    {
        $request->validate([
            'name'   => 'required|string|max:255',
            'active' => 'nullable|boolean',
        ]);

        $assetType->update([
            'name'   => $request->name,
            'active' => $request->boolean('active', true),
        ]);

        return redirect()
            ->route('asset-types.index', $assetType->category)
            ->with('success', 'Tipo de activo actualizado correctamente');
    }

    /**
     * Validar categoría
     */
    private function validateCategory(string $category): void
    {
        if (!in_array($category, ['TI', 'OTRO'])) {
            abort(404);
        }
    }
}