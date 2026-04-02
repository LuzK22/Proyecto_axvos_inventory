@extends('adminlte::page')

@section('title', 'Campos de Plantilla')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0">
            <i class="fas fa-th text-primary mr-2"></i>
            Campos: {{ $template->name }}
        </h1>
        <div>
            <a href="{{ route('admin.acta-templates.edit', $template) }}" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-edit mr-1"></i> Editar plantilla
            </a>
            <a href="{{ route('admin.acta-templates.category', ['category' => strtolower($template->asset_category)]) }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left mr-1"></i> Volver
            </a>
        </div>
    </div>
@stop

@section('content')

@include('partials._alerts')

@php
    $autoCount   = $template->fields->filter(fn($f) => $f->is_auto)->count();
    $manualCount = $template->fields->filter(fn($f) => !$f->is_auto && !$f->is_iterable)->count();
    $iterCount   = $template->fields->where('is_iterable', true)->count();
@endphp

<div class="row mb-3">
    <div class="col-md-4">
        <div class="card shadow-sm text-center py-2" style="border-top:3px solid #16a34a;">
            <div style="font-size:1.4rem;font-weight:700;color:#16a34a;">{{ $autoCount }}</div>
            <small class="text-muted">Campos automaticos</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm text-center py-2" style="border-top:3px solid #f59e0b;">
            <div style="font-size:1.4rem;font-weight:700;color:#f59e0b;">{{ $manualCount }}</div>
            <small class="text-muted">Campos manuales</small>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card shadow-sm text-center py-2" style="border-top:3px solid #3b82f6;">
            <div style="font-size:1.4rem;font-weight:700;color:#3b82f6;">{{ $iterCount }}</div>
            <small class="text-muted">Campos de tabla</small>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-header"><strong>Agregar campo</strong></div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.acta-templates.fields.store', $template) }}">
                    @csrf
                    <div class="form-group">
                        <label class="font-weight-bold">Key <span class="text-danger">*</span></label>
                        <input name="field_key" class="form-control" value="{{ old('field_key') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Etiqueta <span class="text-danger">*</span></label>
                        <input name="field_label" class="form-control" value="{{ old('field_label') }}" required>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Celda <span class="text-danger">*</span></label>
                        <input name="cell_ref" class="form-control" value="{{ old('cell_ref') }}" required>
                    </div>
                    <div class="form-group">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="is_iterable" name="is_iterable" value="1">
                            <label class="custom-control-label" for="is_iterable">Es iterable</label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Orden</label>
                        <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', 0) }}" min="0" max="9999">
                    </div>
                    <button class="btn btn-primary btn-block">
                        <i class="fas fa-plus mr-1"></i> Agregar
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card shadow-sm">
            <div class="card-body p-0">
                <table class="table table-sm table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Marcador</th>
                            <th>Etiqueta</th>
                            <th>Celda</th>
                            <th>Tipo</th>
                            <th>Rellena</th>
                            <th class="text-right">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($template->fields as $f)
                        <tr>
                            <td><code style="font-size:.78rem;">@{{{{ $f->field_key }}}}</code></td>
                            <td style="font-size:.82rem;">{{ $f->field_label }}</td>
                            <td><code style="font-size:.75rem;">{{ $f->cell_ref }}</code></td>
                            <td>
                                @if($f->is_iterable)
                                    <span class="badge badge-primary" style="font-size:.65rem;">Tabla activos</span>
                                @else
                                    <span class="badge badge-secondary" style="font-size:.65rem;">Cabecera</span>
                                @endif
                            </td>
                            <td>
                                @if($f->is_auto)
                                    <span class="badge badge-success" style="font-size:.65rem;">Automatico</span>
                                @else
                                    <span class="badge badge-warning" style="font-size:.65rem;">Manual</span>
                                @endif
                            </td>
                            <td class="text-right">
                                <button class="btn btn-xs btn-outline-secondary" type="button"
                                        onclick="fillEdit({{ $f->id }}, '{{ addslashes($f->field_key) }}', '{{ addslashes($f->field_label) }}', '{{ addslashes($f->cell_ref) }}', {{ $f->is_iterable ? 'true' : 'false' }}, {{ $f->sort_order }})">
                                    <i class="fas fa-pen"></i>
                                </button>
                                <form method="POST" action="{{ route('admin.acta-templates.fields.destroy', [$template, $f]) }}" class="d-inline">
                                    @csrf
                                    @method('DELETE')
                                    <button class="btn btn-xs btn-outline-danger" onclick="return confirm('Eliminar campo?')">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center text-muted p-4">Aun no hay campos configurados.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-header"><strong>Editar campo</strong></div>
            <div class="card-body">
                <form id="editForm" method="POST" action="">
                    @csrf
                    @method('PUT')
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold">Key</label>
                                <input id="e_key" name="field_key" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold">Etiqueta</label>
                                <input id="e_label" name="field_label" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="font-weight-bold">Celda</label>
                                <input id="e_cell" name="cell_ref" class="form-control" required>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <div class="custom-control custom-checkbox mt-2">
                                    <input type="checkbox" class="custom-control-input" id="e_iter" name="is_iterable" value="1">
                                    <label class="custom-control-label" for="e_iter">Iterable</label>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label class="font-weight-bold">Orden</label>
                                <input id="e_order" type="number" name="sort_order" class="form-control" min="0" max="9999">
                            </div>
                        </div>
                        <div class="col-md-6 text-right">
                            <button class="btn btn-primary mt-4">
                                <i class="fas fa-save mr-1"></i> Guardar
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@stop

@section('js')
<script>
function fillEdit(id, key, label, cell, iterable, order) {
    const base = @json(route('admin.acta-templates.fields.update', [$template, 'FIELD_ID']));
    document.getElementById('editForm').action = base.replace('FIELD_ID', id);
    document.getElementById('e_key').value = key;
    document.getElementById('e_label').value = label;
    document.getElementById('e_cell').value = cell;
    document.getElementById('e_iter').checked = !!iterable;
    document.getElementById('e_order').value = order;
    window.scrollTo({ top: document.getElementById('editForm').offsetTop - 80, behavior: 'smooth' });
}
</script>
@stop

