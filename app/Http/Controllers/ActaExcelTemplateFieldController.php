<?php

namespace App\Http\Controllers;

use App\Models\ActaExcelTemplate;
use App\Models\ActaExcelTemplateField;
use Illuminate\Http\Request;

class ActaExcelTemplateFieldController extends Controller
{
    public function index(ActaExcelTemplate $actaExcelTemplate)
    {
        $template = $actaExcelTemplate->load('fields');
        return view('admin.acta-templates.fields', compact('template'));
    }

    public function store(Request $request, ActaExcelTemplate $actaExcelTemplate)
    {
        $request->validate([
            'field_key'   => 'required|string|max:100',
            'field_label' => 'required|string|max:255',
            'cell_ref'    => 'required|string|max:20',
            'is_iterable' => 'nullable|boolean',
            'sort_order'  => 'nullable|integer|min:0|max:9999',
        ]);

        $actaExcelTemplate->fields()->create([
            'field_key'   => $request->field_key,
            'field_label' => $request->field_label,
            'cell_ref'    => strtoupper(trim($request->cell_ref)),
            'is_iterable' => (bool) $request->boolean('is_iterable'),
            'sort_order'  => (int) ($request->sort_order ?? 0),
        ]);

        return back()->with('success', 'Campo agregado.');
    }

    public function update(Request $request, ActaExcelTemplate $actaExcelTemplate, ActaExcelTemplateField $field)
    {
        abort_unless($field->acta_excel_template_id === $actaExcelTemplate->id, 404);

        $request->validate([
            'field_key'   => 'required|string|max:100',
            'field_label' => 'required|string|max:255',
            'cell_ref'    => 'required|string|max:20',
            'is_iterable' => 'nullable|boolean',
            'sort_order'  => 'nullable|integer|min:0|max:9999',
        ]);

        $field->update([
            'field_key'   => $request->field_key,
            'field_label' => $request->field_label,
            'cell_ref'    => strtoupper(trim($request->cell_ref)),
            'is_iterable' => (bool) $request->boolean('is_iterable'),
            'sort_order'  => (int) ($request->sort_order ?? 0),
        ]);

        return back()->with('success', 'Campo actualizado.');
    }

    public function destroy(ActaExcelTemplate $actaExcelTemplate, ActaExcelTemplateField $field)
    {
        abort_unless($field->acta_excel_template_id === $actaExcelTemplate->id, 404);
        $field->delete();
        return back()->with('success', 'Campo eliminado.');
    }
}

