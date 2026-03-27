@extends('adminlte::page')

@section('title', 'Subcategorías de Activos')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0 font-weight-bold" style="color:#0d1b2a;">
            <i class="fas fa-sitemap mr-2" style="color:#00b4d8;"></i> Subcategorías de Activos
        </h1>
        <a href="{{ route('admin.hub') }}" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Administración
        </a>
    </div>
@stop

@section('content')

@include('partials._alerts')

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show shadow-sm">
        <i class="fas fa-exclamation-circle mr-2"></i>
        <ul class="mb-0">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
@endif

<div class="alert alert-light border shadow-sm mb-4">
    <i class="fas fa-info-circle mr-2 text-info"></i>
    <strong>¿Qué son las subcategorías?</strong>
    Las subcategorías son etiquetas de clasificación que se asignan a los <strong>tipos de activo</strong>.
    Por ejemplo, el tipo de activo "Silla Ejecutiva" puede pertenecer a la subcategoría "Mobiliario" dentro de la categoría OTRO.
    Para asignar una subcategoría, ve a crear o editar un tipo de activo.
</div>

@php
    $tiCategories   = $categories->where('category', 'TI');
    $otroCategories = $categories->where('category', 'OTRO');
@endphp

<div class="row">

    {{-- ── Sección TI ─────────────────────────────────────────────────── --}}
    <div class="col-md-6">
        <div class="card card-outline shadow-sm mb-4" style="border-top-color:#007bff;">
            <div class="card-header" style="background:#f8f9fa;">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-laptop mr-2 text-primary"></i> Subcategorías TI
                </h3>
                <div class="card-tools">
                    <span class="badge badge-primary">{{ $tiCategories->count() }} subcategorías</span>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover table-sm mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th class="pl-3">Subcategoría</th>
                            <th class="text-center">Tipos de activo</th>
                            <th class="text-center" style="width:80px;">Renombrar</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($tiCategories as $cat)
                        <tr id="ti-row-{{ $loop->index }}">
                            <td class="pl-3 align-middle ti-view-{{ $loop->index }}">
                                <strong>{{ $cat->subcategory }}</strong>
                            </td>
                            <td class="text-center align-middle ti-view-{{ $loop->index }}">
                                <span class="badge badge-light border">{{ $cat->total }}</span>
                            </td>
                            <td class="text-center align-middle ti-view-{{ $loop->index }}">
                                <button type="button" class="btn btn-xs btn-outline-primary"
                                        onclick="showTiEdit({{ $loop->index }})" title="Renombrar">
                                    <i class="fas fa-pencil-alt"></i>
                                </button>
                            </td>
                            <td colspan="3" class="ti-edit-{{ $loop->index }} p-2" style="display:none;">
                                <form method="POST"
                                      action="{{ route('categories.update', $loop->index) }}"
                                      class="d-flex align-items-center" style="gap:4px;">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="old_subcategory" value="{{ $cat->subcategory }}">
                                    <input type="hidden" name="category" value="TI">
                                    <input type="text" name="subcategory" class="form-control form-control-sm"
                                           value="{{ $cat->subcategory }}" style="max-width:140px;" required>
                                    <button type="submit" class="btn btn-xs btn-success">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button type="button" class="btn btn-xs btn-secondary"
                                            onclick="hideTiEdit({{ $loop->index }})">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">
                                No hay subcategorías TI registradas aún.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── Sección OTRO ────────────────────────────────────────────────── --}}
    <div class="col-md-6">
        <div class="card card-outline shadow-sm mb-4" style="border-top-color:#7c3aed;">
            <div class="card-header" style="background:#f8f9fa;">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-box mr-2" style="color:#7c3aed;"></i> Subcategorías OTRO
                </h3>
                <div class="card-tools">
                    <span class="badge" style="background:#7c3aed;color:#fff;">{{ $otroCategories->count() }} subcategorías</span>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover table-sm mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th class="pl-3">Subcategoría</th>
                            <th class="text-center">Tipos de activo</th>
                            <th class="text-center" style="width:80px;">Renombrar</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($otroCategories as $cat)
                        <tr id="otro-row-{{ $loop->index }}">
                            <td class="pl-3 align-middle otro-view-{{ $loop->index }}">
                                <strong>{{ $cat->subcategory }}</strong>
                            </td>
                            <td class="text-center align-middle otro-view-{{ $loop->index }}">
                                <span class="badge badge-light border">{{ $cat->total }}</span>
                            </td>
                            <td class="text-center align-middle otro-view-{{ $loop->index }}">
                                <button type="button" class="btn btn-xs btn-outline-primary"
                                        onclick="showOtroEdit({{ $loop->index }})" title="Renombrar">
                                    <i class="fas fa-pencil-alt"></i>
                                </button>
                            </td>
                            <td colspan="3" class="otro-edit-{{ $loop->index }} p-2" style="display:none;">
                                <form method="POST"
                                      action="{{ route('categories.update', $loop->index) }}"
                                      class="d-flex align-items-center" style="gap:4px;">
                                    @csrf
                                    @method('PUT')
                                    <input type="hidden" name="old_subcategory" value="{{ $cat->subcategory }}">
                                    <input type="hidden" name="category" value="OTRO">
                                    <input type="text" name="subcategory" class="form-control form-control-sm"
                                           value="{{ $cat->subcategory }}" style="max-width:140px;" required>
                                    <button type="submit" class="btn btn-xs btn-success">
                                        <i class="fas fa-check"></i>
                                    </button>
                                    <button type="button" class="btn btn-xs btn-secondary"
                                            onclick="hideOtroEdit({{ $loop->index }})">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="text-center text-muted py-4">
                                No hay subcategorías OTRO registradas aún.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>

{{-- ── Nueva Subcategoría ─────────────────────────────────────────────── --}}
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card card-outline shadow-sm" style="border-top-color:#00b4d8;">
            <div class="card-header" style="background:#f8f9fa;">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-plus-circle mr-2" style="color:#00b4d8;"></i> Registrar Nueva Subcategoría
                </h3>
            </div>
            <div class="card-body">
                <p class="text-muted small">
                    Registra el nombre de la subcategoría aquí para tenerla disponible al crear tipos de activo.
                    La asignación real se hace desde el módulo de Tipos de Activo.
                </p>
                <form method="POST" action="{{ route('categories.store') }}">
                    @csrf
                    <div class="row">
                        <div class="col-md-7">
                            <div class="form-group">
                                <label class="font-weight-bold">Nombre <span class="text-danger">*</span></label>
                                <input type="text" name="subcategory"
                                       class="form-control @error('subcategory') is-invalid @enderror"
                                       value="{{ old('subcategory') }}"
                                       placeholder="Ej: Mobiliario, Redes, Periféricos...">
                                @error('subcategory')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="form-group">
                                <label class="font-weight-bold">Categoría <span class="text-danger">*</span></label>
                                <select name="category" class="form-control @error('category') is-invalid @enderror">
                                    <option value="TI"   {{ old('category') === 'TI'   ? 'selected' : '' }}>TI</option>
                                    <option value="OTRO" {{ old('category') === 'OTRO' ? 'selected' : '' }}>OTRO</option>
                                </select>
                                @error('category')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-plus mr-1"></i> Registrar Subcategoría
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

@stop

@section('js')
<script>
function showTiEdit(idx) {
    document.querySelectorAll('.ti-view-' + idx).forEach(function(el) { el.style.display = 'none'; });
    document.querySelectorAll('.ti-edit-' + idx).forEach(function(el) { el.style.display = ''; });
}
function hideTiEdit(idx) {
    document.querySelectorAll('.ti-view-' + idx).forEach(function(el) { el.style.display = ''; });
    document.querySelectorAll('.ti-edit-' + idx).forEach(function(el) { el.style.display = 'none'; });
}
function showOtroEdit(idx) {
    document.querySelectorAll('.otro-view-' + idx).forEach(function(el) { el.style.display = 'none'; });
    document.querySelectorAll('.otro-edit-' + idx).forEach(function(el) { el.style.display = ''; });
}
function hideOtroEdit(idx) {
    document.querySelectorAll('.otro-view-' + idx).forEach(function(el) { el.style.display = ''; });
    document.querySelectorAll('.otro-edit-' + idx).forEach(function(el) { el.style.display = 'none'; });
}
</script>
@stop
