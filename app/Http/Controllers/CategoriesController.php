<?php

namespace App\Http\Controllers;

use App\Models\AssetType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CategoriesController extends Controller
{
    public function index()
    {
        // Get unique subcategories grouped with type counts
        $categories = AssetType::select('subcategory', 'category', DB::raw('count(*) as total'))
            ->whereNotNull('subcategory')
            ->groupBy('subcategory', 'category')
            ->orderBy('category')
            ->orderBy('subcategory')
            ->get();

        return view('admin.categories.index', compact('categories'));
    }

    public function store(Request $request)
    {
        // Subcategories don't have their own table — they're text on asset_types.
        // This endpoint just validates and returns info; actual assignment is on asset_type create/edit.
        $request->validate([
            'subcategory' => 'required|string|max:60',
            'category'    => 'required|in:TI,OTRO',
        ]);

        return back()->with('success', 'Subcategoría registrada. Asígnala al crear tipos de activo.');
    }

    public function update(Request $request, $id)
    {
        // Rename a subcategory across all asset_types
        $request->validate([
            'subcategory'     => 'required|string|max:60',
            'old_subcategory' => 'required|string',
            'category'        => 'required|in:TI,OTRO',
        ]);

        AssetType::where('subcategory', $request->old_subcategory)
            ->where('category', $request->category)
            ->update(['subcategory' => $request->subcategory]);

        return back()->with('success', 'Subcategoría renombrada en todos los tipos de activo.');
    }

    public function destroy($id)
    {
        // id here is subcategory+category encoded. We use query params instead.
        return back()->withErrors(['Use el formulario de edición para gestionar subcategorías.']);
    }
}
