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
            'name'        => 'required|string|max:255',
            'category'    => 'required|in:TI,OTRO',
            // Subcategoría: solo aplica a OTRO (ej: Mobiliario, Enseres)
            'subcategory' => 'nullable|string|max:100',
        ]);

        // Generar código automático de 3 letras desde el nombre
        $code = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $request->name), 0, 3));

        $type = AssetType::create([
            'name'        => $request->name,
            'code'        => $code,
            'category'    => $request->category,
            'subcategory' => $request->category === 'OTRO' ? $request->subcategory : null,
            'active'      => true,
            'created_by'  => auth()->id(),
        ]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($type)
            ->withProperties(['category' => $type->category, 'code' => $type->code])
            ->log("Tipo de activo '{$type->name}' ({$type->category}) creado");

        return redirect()
            ->route('asset-types.index', $request->category)
            ->with('success', 'Tipo de activo creado correctamente.');
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
            'name'        => 'required|string|max:255',
            'subcategory' => 'nullable|string|max:100',
            'active'      => 'nullable|boolean',
        ]);

        $assetType->update([
            'name'        => $request->name,
            'subcategory' => $assetType->category === 'OTRO' ? $request->subcategory : null,
            'active'      => $request->boolean('active', true),
        ]);

        activity()
            ->causedBy(auth()->user())
            ->performedOn($assetType)
            ->log("Tipo de activo '{$assetType->name}' actualizado");

        return redirect()
            ->route('asset-types.index', $assetType->category)
            ->with('success', 'Tipo de activo actualizado correctamente');
    }

    /**
     * Eliminar tipo
     */
    public function destroy(AssetType $assetType)
    {
        if ($assetType->assets()->exists()) {
            $assetType->update(['active' => false]);

            activity()
                ->causedBy(auth()->user())
                ->performedOn($assetType)
                ->log("Tipo de activo '{$assetType->name}' desactivado (tiene activos relacionados)");

            return redirect()
                ->route('asset-types.index', $assetType->category)
                ->with('warning', 'El tipo tiene activos relacionados; se desactivó en lugar de eliminarse.');
        }

        $category = $assetType->category;
        $name     = $assetType->name;

        activity()
            ->causedBy(auth()->user())
            ->withProperties(['name' => $name, 'category' => $category])
            ->log("Tipo de activo '{$name}' ({$category}) eliminado");

        $assetType->delete();

        return redirect()
            ->route('asset-types.index', $category)
            ->with('success', 'Tipo de activo eliminado correctamente.');
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
