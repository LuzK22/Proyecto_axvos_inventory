@extends('adminlte::page')

@section('title', 'Editar Plantilla Excel')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0">
            <i class="fas fa-edit text-secondary mr-2"></i> Editar Plantilla
        </h1>
        <a href="{{ route('admin.acta-templates.category', ['category' => strtolower($selectedCategory ?? 'ti')]) }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Volver
        </a>
    </div>
@stop

@section('content')

@include('partials._alerts')

<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.acta-templates.update', $template) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label class="font-weight-bold">Nombre <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="{{ old('name', $template->name) }}" required>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="font-weight-bold">Tipo de acta <span class="text-danger">*</span></label>
                        <select name="acta_type" class="form-control" required>
                            @php($type = old('acta_type', $template->acta_type))
                            <option value="entrega"   {{ $type==='entrega' ? 'selected' : '' }}>Entrega</option>
                            <option value="devolucion"{{ $type==='devolucion' ? 'selected' : '' }}>Devolución</option>
                            <option value="baja"      {{ $type==='baja' ? 'selected' : '' }}>Baja</option>
                            <option value="donacion"  {{ $type==='donacion' ? 'selected' : '' }}>Donación</option>
                            <option value="venta"     {{ $type==='venta' ? 'selected' : '' }}>Venta</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="font-weight-bold">Categoría <span class="text-danger">*</span></label>
                        @php($cat = old('asset_category', $template->asset_category))
                        <select name="asset_category" class="form-control" required>
                            <option value="TI"   {{ $cat==='TI' ? 'selected' : '' }}>TI</option>
                            <option value="OTRO" {{ $cat==='OTRO' ? 'selected' : '' }}>OTRO</option>
                            <option value="ALL"  {{ $cat==='ALL' ? 'selected' : '' }}>ALL (Mixta)</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="font-weight-bold">Fila inicial de activos</label>
                        <input type="number" name="assets_start_row" class="form-control"
                               value="{{ old('assets_start_row', $template->assets_start_row) }}" min="1" max="9999">
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="font-weight-bold">Reemplazar archivo (opcional)</label>
                <input type="file" name="template_file" class="form-control" accept=".xlsx">
                <small class="text-muted">Si subes uno nuevo, se reemplaza el anterior.</small>
            </div>

            <div class="d-flex justify-content-between">
                <button class="btn btn-primary">
                    <i class="fas fa-save mr-1"></i> Guardar cambios
                </button>
                <a href="{{ route('admin.acta-templates.fields.index', $template) }}" class="btn btn-outline-primary">
                    <i class="fas fa-th mr-1"></i> Configurar campos
                </a>
            </div>
        </form>
    </div>
</div>

@stop
