<?php

namespace App\Http\Controllers;

use App\Models\AssetType;
use App\Models\Subcategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoriesController extends Controller
{
    public function index()
    {
        // Load all registered subcategories with a count from asset_types (0 if none)
        $subcategoryRows = Subcategory::orderBy('category')->orderBy('name')->get()
            ->map(function ($sub) {
                $sub->total = AssetType::where('subcategory', $sub->name)
                    ->where('category', $sub->category)
                    ->count();
                return $sub;
            });

        // Map to same shape as before for the view
        $categories = $subcategoryRows->map(function ($sub) {
            return (object) [
                'subcategory' => $sub->name,
                'category'    => $sub->category,
                'total'       => $sub->total,
            ];
        });

        return view('admin.categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'subcategory' => 'required|string|max:60',
            'category'    => 'required|in:TI,OTRO',
        ]);

        $exists = Subcategory::where('name', $request->subcategory)
            ->where('category', $request->category)
            ->exists();

        if ($exists) {
            return back()->withErrors(['subcategory' => 'Ya existe esa subcategoría para la categoría seleccionada.']);
        }

        Subcategory::create([
            'name'     => $request->subcategory,
            'category' => $request->category,
        ]);

        return back()->with('success', 'Subcategoría "' . $request->subcategory . '" registrada para ' . $request->category . '.');
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'subcategory'     => 'required|string|max:60',
            'old_subcategory' => 'required|string',
            'category'        => 'required|in:TI,OTRO',
        ]);

        // Rename in subcategories table
        Subcategory::where('name', $request->old_subcategory)
            ->where('category', $request->category)
            ->update(['name' => $request->subcategory]);

        // Rename across all asset_types
        AssetType::where('subcategory', $request->old_subcategory)
            ->where('category', $request->category)
            ->update(['subcategory' => $request->subcategory]);

        return back()->with('success', 'Subcategoría renombrada correctamente.');
    }

    public function destroy($id)
    {
        // id here is subcategory+category encoded. We use query params instead.
        return back()->withErrors(['Use el formulario de edición para gestionar subcategorías.']);
    }
}
