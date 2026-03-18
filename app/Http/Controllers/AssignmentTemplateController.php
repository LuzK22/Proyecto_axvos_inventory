<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\AssignmentType;
use App\Models\AssignmentTemplate;
use App\Models\AssignmentTemplateItem;
use App\Models\AssetType;

class AssignmentTemplateController extends Controller
{
    /** Listado de tipos y plantillas */
    public function index()
    {
        $types = AssignmentType::with('templates.items.assetType')
            ->orderBy('sort_order')
            ->get();

        return view('admin.assignment-templates.index', compact('types'));
    }

    /** Formulario nueva plantilla */
    public function create()
    {
        $types      = AssignmentType::where('active', true)->orderBy('sort_order')->get();
        $assetTypes = AssetType::where('active', true)->orderBy('category')->orderBy('name')->get();

        return view('admin.assignment-templates.create', compact('types', 'assetTypes'));
    }

    /** Guardar nueva plantilla */
    public function store(Request $request)
    {
        $request->validate([
            'assignment_type_id' => 'required|exists:assignment_types,id',
            'name'               => 'required|string|max:100',
            'description'        => 'nullable|string',
            'trigger_value'      => 'nullable|string|max:100',
            'items'              => 'nullable|array',
            'items.*.asset_type_id' => 'required|exists:asset_types,id',
            'items.*.quantity'      => 'required|integer|min:1|max:10',
            'items.*.goes_to'       => 'required|in:assignee,area,jefe,pool',
            'items.*.notes'         => 'nullable|string|max:255',
        ]);

        $template = AssignmentTemplate::create([
            'assignment_type_id' => $request->assignment_type_id,
            'name'               => $request->name,
            'description'        => $request->description,
            'trigger_value'      => $request->trigger_value,
            'active'             => true,
            'sort_order'         => AssignmentTemplate::where('assignment_type_id', $request->assignment_type_id)->count(),
        ]);

        foreach ($request->input('items', []) as $i => $item) {
            AssignmentTemplateItem::create([
                'assignment_template_id' => $template->id,
                'asset_type_id'          => $item['asset_type_id'],
                'quantity'               => $item['quantity'],
                'goes_to'                => $item['goes_to'],
                'notes'                  => $item['notes'] ?? null,
                'sort_order'             => $i,
            ]);
        }

        return redirect()->route('admin.assignment-templates.index')
            ->with('success', "Plantilla \"{$template->name}\" creada correctamente.");
    }

    /** Formulario editar plantilla */
    public function edit(AssignmentTemplate $assignmentTemplate)
    {
        $types      = AssignmentType::where('active', true)->orderBy('sort_order')->get();
        $assetTypes = AssetType::where('active', true)->orderBy('category')->orderBy('name')->get();

        $assignmentTemplate->load('items.assetType');

        return view('admin.assignment-templates.edit', compact('assignmentTemplate', 'types', 'assetTypes'));
    }

    /** Actualizar plantilla */
    public function update(Request $request, AssignmentTemplate $assignmentTemplate)
    {
        $request->validate([
            'assignment_type_id' => 'required|exists:assignment_types,id',
            'name'               => 'required|string|max:100',
            'description'        => 'nullable|string',
            'trigger_value'      => 'nullable|string|max:100',
            'active'             => 'nullable|boolean',
            'items'              => 'nullable|array',
            'items.*.asset_type_id' => 'required|exists:asset_types,id',
            'items.*.quantity'      => 'required|integer|min:1|max:10',
            'items.*.goes_to'       => 'required|in:assignee,area,jefe,pool',
            'items.*.notes'         => 'nullable|string|max:255',
        ]);

        $assignmentTemplate->update([
            'assignment_type_id' => $request->assignment_type_id,
            'name'               => $request->name,
            'description'        => $request->description,
            'trigger_value'      => $request->trigger_value,
            'active'             => $request->boolean('active', true),
        ]);

        // Reemplazar ítems
        $assignmentTemplate->items()->delete();

        foreach ($request->input('items', []) as $i => $item) {
            AssignmentTemplateItem::create([
                'assignment_template_id' => $assignmentTemplate->id,
                'asset_type_id'          => $item['asset_type_id'],
                'quantity'               => $item['quantity'],
                'goes_to'                => $item['goes_to'],
                'notes'                  => $item['notes'] ?? null,
                'sort_order'             => $i,
            ]);
        }

        return redirect()->route('admin.assignment-templates.index')
            ->with('success', "Plantilla \"{$assignmentTemplate->name}\" actualizada.");
    }

    /** Activar / desactivar plantilla (toggle AJAX) */
    public function toggleActive(AssignmentTemplate $assignmentTemplate)
    {
        $assignmentTemplate->update(['active' => !$assignmentTemplate->active]);

        return response()->json(['active' => $assignmentTemplate->active]);
    }

    /** Devuelve plantilla como JSON para el modal de asignación */
    public function forValue(Request $request)
    {
        $typeId = $request->query('type_id');
        $value  = $request->query('value');

        $template = AssignmentTemplate::with('items.assetType')
            ->where('assignment_type_id', $typeId)
            ->where('trigger_value', $value)
            ->where('active', true)
            ->first();

        if (!$template) {
            return response()->json(null);
        }

        return response()->json([
            'id'   => $template->id,
            'name' => $template->name,
            'items' => $template->items->map(fn($i) => [
                'asset_type_id'   => $i->asset_type_id,
                'asset_type_name' => $i->assetType->name,
                'quantity'        => $i->quantity,
                'goes_to'         => $i->goes_to,
                'goes_to_label'   => $i->goes_to_label,
                'notes'           => $i->notes,
            ]),
        ]);
    }
}
