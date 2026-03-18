<?php

namespace App\Http\Controllers;

use App\Models\ActaExcelTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ActaExcelTemplateController extends Controller
{
    public function index()
    {
        $templates = ActaExcelTemplate::withCount('fields')
            ->orderByDesc('active')
            ->orderBy('acta_type')
            ->orderBy('asset_category')
            ->orderBy('name')
            ->get();

        return view('admin.acta-templates.index', compact('templates'));
    }

    public function create()
    {
        return view('admin.acta-templates.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'            => 'required|string|max:255',
            'acta_type'       => 'required|string|max:50',
            'asset_category'  => 'required|in:TI,OTRO,ALL',
            'assets_start_row'=> 'nullable|integer|min:1|max:9999',
            'template_file'   => 'required|file|mimes:xlsx|max:10240',
        ]);

        $path = $request->file('template_file')->store('acta-templates');

        ActaExcelTemplate::create([
            'name'            => $request->name,
            'file_path'       => $path,
            'acta_type'       => $request->acta_type,
            'asset_category'  => $request->asset_category,
            'active'          => false,
            'assets_start_row'=> $request->assets_start_row,
            'uploaded_by'     => auth()->id(),
        ]);

        return redirect()
            ->route('admin.acta-templates.index')
            ->with('success', 'Plantilla Excel subida correctamente. Ahora puedes mapear campos y activarla.');
    }

    public function edit(ActaExcelTemplate $actaExcelTemplate)
    {
        return view('admin.acta-templates.edit', ['template' => $actaExcelTemplate]);
    }

    public function update(Request $request, ActaExcelTemplate $actaExcelTemplate)
    {
        $request->validate([
            'name'            => 'required|string|max:255',
            'acta_type'       => 'required|string|max:50',
            'asset_category'  => 'required|in:TI,OTRO,ALL',
            'assets_start_row'=> 'nullable|integer|min:1|max:9999',
            'template_file'   => 'nullable|file|mimes:xlsx|max:10240',
        ]);

        $data = $request->only(['name', 'acta_type', 'asset_category', 'assets_start_row']);

        if ($request->hasFile('template_file')) {
            if ($actaExcelTemplate->file_path) {
                Storage::delete($actaExcelTemplate->file_path);
            }
            $data['file_path'] = $request->file('template_file')->store('acta-templates');
        }

        $actaExcelTemplate->update($data);

        return redirect()
            ->route('admin.acta-templates.index')
            ->with('success', 'Plantilla actualizada correctamente.');
    }

    public function toggleActive(ActaExcelTemplate $actaExcelTemplate)
    {
        // Solo una plantilla activa por (acta_type + asset_category)
        ActaExcelTemplate::where('acta_type', $actaExcelTemplate->acta_type)
            ->where('asset_category', $actaExcelTemplate->asset_category)
            ->update(['active' => false]);

        $actaExcelTemplate->update(['active' => true]);

        return back()->with('success', 'Plantilla activada correctamente.');
    }
}

