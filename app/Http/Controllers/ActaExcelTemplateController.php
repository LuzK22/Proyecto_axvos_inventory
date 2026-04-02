<?php

namespace App\Http\Controllers;

use App\Models\ActaExcelTemplate;
use App\Models\ActaExcelTemplateField;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ActaExcelTemplateController extends Controller
{
    public function index(Request $request, ?string $category = null)
    {
        $selectedCategory = strtoupper($category ?: $request->query('category', ''));
        if (!in_array($selectedCategory, ['TI', 'OTRO', 'ALL'], true)) {
            $selectedCategory = null;
        }

        $query = ActaExcelTemplate::withCount('fields')
            ->orderByDesc('active')
            ->orderBy('acta_type')
            ->orderBy('asset_category')
            ->orderBy('name');

        if ($selectedCategory) {
            $query->where('asset_category', $selectedCategory);
        }

        $templates = $query->get();

        return view('admin.acta-templates.index', compact('templates', 'selectedCategory'));
    }

    public function create(Request $request, ?string $category = null)
    {
        $selectedCategory = strtoupper($category ?: $request->query('category', 'TI'));
        if (!in_array($selectedCategory, ['TI', 'OTRO', 'ALL'], true)) {
            $selectedCategory = 'TI';
        }

        return view('admin.acta-templates.create', compact('selectedCategory'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'             => 'required|string|max:255',
            'acta_type'        => 'required|string|max:50',
            'asset_category'   => 'required|in:TI,OTRO,ALL',
            'assets_start_row' => 'nullable|integer|min:1|max:9999',
            'template_file'    => 'required|file|mimes:xlsx,docx|max:20480',
        ]);

        $file = $request->file('template_file');
        $ext  = strtolower($file->getClientOriginalExtension());
        $path = $file->store('acta-templates');

        $template = ActaExcelTemplate::create([
            'name'             => $request->name,
            'file_path'        => $path,
            'template_type'    => $ext,
            'acta_type'        => $request->acta_type,
            'asset_category'   => $request->asset_category,
            'active'           => false,
            'assets_start_row' => $request->assets_start_row,
            'uploaded_by'      => auth()->id(),
        ]);

        // ── Auto-escanear marcadores {{}} ─────────────────────────────────────
        $detected = $this->autoScanAndCreateFields($template, $ext);

        $msg = $detected > 0
            ? "✅ {$detected} campos detectados automáticamente en la plantilla. Revisa los campos y actívala cuando esté lista."
            : 'Plantilla subida. No se detectaron marcadores {{campo}}. Puedes agregarlos manualmente en "Campos".';

        return redirect()
            ->route('admin.acta-templates.fields.index', $template)
            ->with('success', $msg);
    }

    public function edit(ActaExcelTemplate $actaExcelTemplate)
    {
        $selectedCategory = strtoupper($actaExcelTemplate->asset_category);
        return view('admin.acta-templates.edit', [
            'template'         => $actaExcelTemplate,
            'selectedCategory' => $selectedCategory,
        ]);
    }

    public function update(Request $request, ActaExcelTemplate $actaExcelTemplate)
    {
        $request->validate([
            'name'             => 'required|string|max:255',
            'acta_type'        => 'required|string|max:50',
            'asset_category'   => 'required|in:TI,OTRO,ALL',
            'assets_start_row' => 'nullable|integer|min:1|max:9999',
            'template_file'    => 'nullable|file|mimes:xlsx,docx|max:20480',
        ]);

        $data = $request->only(['name', 'acta_type', 'asset_category', 'assets_start_row']);

        if ($request->hasFile('template_file')) {
            // Borrar archivo y campos anteriores
            if ($actaExcelTemplate->file_path) {
                Storage::delete($actaExcelTemplate->file_path);
            }
            $actaExcelTemplate->fields()->delete();

            $file              = $request->file('template_file');
            $ext               = strtolower($file->getClientOriginalExtension());
            $data['file_path'] = $file->store('acta-templates');
            $data['template_type'] = $ext;

            $actaExcelTemplate->update($data);

            // Re-escanear
            $detected = $this->autoScanAndCreateFields($actaExcelTemplate, $ext);

            $msg = $detected > 0
                ? "✅ Plantilla reemplazada. {$detected} campos detectados automáticamente."
                : 'Plantilla reemplazada. No se detectaron marcadores {{campo}}.';

            return redirect()
                ->route('admin.acta-templates.fields.index', $actaExcelTemplate)
                ->with('success', $msg);
        }

        $actaExcelTemplate->update($data);

        return redirect()
            ->route('admin.acta-templates.category', ['category' => strtolower($actaExcelTemplate->asset_category)])
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

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers privados
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Escanea el archivo subido, crea los campos automáticamente
     * y actualiza assets_start_row si se detectan campos iterables.
     *
     * @return int Número de campos creados
     */
    private function autoScanAndCreateFields(ActaExcelTemplate $template, string $ext): int
    {
        $scanned = match ($ext) {
            'xlsx'  => $template->scanXlsxPlaceholders(),
            'docx'  => $template->scanDocxPlaceholders(),
            default => [],
        };

        if (empty($scanned)) {
            return 0;
        }

        $iterRows = [];

        foreach ($scanned as $f) {
            ActaExcelTemplateField::create([
                'acta_excel_template_id' => $template->id,
                'field_key'              => $f['key'],
                'field_label'            => $f['label'],
                'cell_ref'               => $f['cell_ref'],
                'is_iterable'            => $f['iterable'],
                'sort_order'             => $f['sort_order'],
            ]);

            if ($f['iterable'] && $f['row'] > 0) {
                $iterRows[] = $f['row'];
            }
        }

        // Actualizar fila de inicio de activos si se detectaron campos iterables
        if (!empty($iterRows)) {
            $template->update(['assets_start_row' => min($iterRows)]);
        }

        return count($scanned);
    }
}
