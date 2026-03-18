@extends('adminlte::page')

@section('title', 'Subir Plantilla Excel')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0">
            <i class="fas fa-upload text-success mr-2"></i> Subir Plantilla Excel
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
        <form method="POST" action="{{ route('admin.acta-templates.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="form-group">
                <label class="font-weight-bold">Nombre <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                <small class="text-muted">Ej: “Entrega TI v1 (Mi Empresa)”</small>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="font-weight-bold">Tipo de acta <span class="text-danger">*</span></label>
                        <select name="acta_type" class="form-control" required>
                            @php($type = old('acta_type','entrega'))
                            <option value="entrega"   {{ $type==='entrega' ? 'selected' : '' }}>Entrega</option>
                            <option value="devolucion"{{ $type==='devolucion' ? 'selected' : '' }}>Devolución</option>
                            <option value="baja"      {{ $type==='baja' ? 'selected' : '' }}>Baja</option>
                            <option value="donacion"  {{ $type==='donacion' ? 'selected' : '' }}>Donación</option>
                            <option value="venta"     {{ $type==='venta' ? 'selected' : '' }}>Venta</option>
                            <option value="actualizacion" {{ $type==='actualizacion' ? 'selected' : '' }}>Actualización</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="font-weight-bold">Categoría <span class="text-danger">*</span></label>
                        @php($cat = old('asset_category', $selectedCategory ?? 'TI'))
                        <select name="asset_category" class="form-control" required>
                            <option value="TI"   {{ $cat==='TI' ? 'selected' : '' }}>TI</option>
                            <option value="OTRO" {{ $cat==='OTRO' ? 'selected' : '' }}>OTRO</option>
                            <option value="ALL"  {{ $cat==='ALL' ? 'selected' : '' }}>ALL (Mixta)</option>
                        </select>
                        <small class="text-muted">Usa ALL para acta mixta; TI y OTRO quedan totalmente separadas.</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="font-weight-bold">Fila inicial de activos</label>
                        <input type="number" name="assets_start_row" class="form-control" value="{{ old('assets_start_row') }}" min="1" max="9999">
                        <small class="text-muted">Ej: 12 (para celdas tipo A{row}).</small>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="font-weight-bold">Archivo Excel (.xlsx) <span class="text-danger">*</span></label>
                <input type="file" name="template_file" class="form-control" accept=".xlsx" required>
                <small class="text-muted">Máx. 10 MB.</small>
            </div>

            <button class="btn btn-success">
                <i class="fas fa-save mr-1"></i> Guardar
            </button>
        </form>
    </div>
</div>

@stop
