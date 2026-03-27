@extends('adminlte::page')

@section('title', 'Estados de Activos')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0 font-weight-bold" style="color:#0d1b2a;">
            <i class="fas fa-tags mr-2" style="color:#00b4d8;"></i> Estados de Activos
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

<div class="row">

    {{-- ── COLUMNA IZQUIERDA: tabla de estados ──────────────────────────── --}}
    <div class="col-md-7">
        <div class="card shadow-sm">
            <div class="card-header" style="background:#f8f9fa;">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-list mr-2 text-secondary"></i> Estados configurados
                </h3>
                <div class="card-tools">
                    <small class="text-muted">{{ $statuses->count() }} estados</small>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover table-sm mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th class="pl-3">Nombre</th>
                            <th>Vista previa</th>
                            <th class="text-center">Activos</th>
                            <th class="text-center" style="width:110px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    @forelse($statuses as $status)
                        <tr id="row-{{ $status->id }}">
                            {{-- Vista normal --}}
                            <td class="pl-3 align-middle view-mode-{{ $status->id }}">
                                <strong>{{ $status->name }}</strong>
                            </td>
                            <td class="align-middle view-mode-{{ $status->id }}">
                                <span class="badge badge-pill badge-{{ $status->color }}">
                                    {{ $status->name }}
                                </span>
                            </td>
                            <td class="text-center align-middle view-mode-{{ $status->id }}">
                                @if($status->assets_count > 0)
                                    <span class="badge badge-secondary">{{ $status->assets_count }}</span>
                                @else
                                    <span class="text-muted small">0</span>
                                @endif
                            </td>
                            <td class="text-center align-middle view-mode-{{ $status->id }}">
                                <button type="button" class="btn btn-xs btn-outline-primary mr-1"
                                        onclick="showEditRow({{ $status->id }})"
                                        title="Editar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                @if($status->assets_count == 0)
                                <form method="POST" action="{{ route('statuses.destroy', $status) }}" class="d-inline"
                                      onsubmit="return confirm('¿Eliminar el estado «{{ addslashes($status->name) }}»?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-xs btn-outline-danger" title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                                @else
                                <button type="button" class="btn btn-xs btn-outline-secondary" disabled
                                        title="Tiene activos asociados">
                                    <i class="fas fa-trash"></i>
                                </button>
                                @endif
                            </td>

                            {{-- Vista edición inline (oculta por defecto) --}}
                            <td colspan="4" class="edit-mode-{{ $status->id }} p-2" style="display:none;">
                                <form method="POST" action="{{ route('statuses.update', $status) }}"
                                      class="d-flex align-items-center flex-wrap" style="gap:6px;">
                                    @csrf
                                    @method('PUT')
                                    <input type="text" name="name" class="form-control form-control-sm"
                                           value="{{ $status->name }}" style="max-width:180px;" required>
                                    <select name="color" class="form-control form-control-sm" style="max-width:140px;" required>
                                        @foreach(['primary','success','danger','warning','info','secondary','dark','light'] as $color)
                                            <option value="{{ $color }}" {{ $status->color === $color ? 'selected' : '' }}>
                                                {{ ucfirst($color) }}
                                            </option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-success">
                                        <i class="fas fa-check mr-1"></i> Guardar
                                    </button>
                                    <button type="button" class="btn btn-sm btn-secondary"
                                            onclick="hideEditRow({{ $status->id }})">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted py-4">
                                No hay estados configurados aún.
                            </td>
                        </tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ── COLUMNA DERECHA: nuevo estado ────────────────────────────────── --}}
    <div class="col-md-5">
        <div class="card card-outline shadow-sm" style="border-top-color:#00b4d8;">
            <div class="card-header" style="background:#f8f9fa;">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-plus-circle mr-2" style="color:#00b4d8;"></i> Nuevo Estado
                </h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('statuses.store') }}">
                    @csrf

                    <div class="form-group">
                        <label class="font-weight-bold">Nombre del estado <span class="text-danger">*</span></label>
                        <input type="text" name="name"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name') }}"
                               placeholder="Ej: En Reparación, Extraviado...">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Color <span class="text-danger">*</span></label>
                        <select name="color" id="newColor"
                                class="form-control @error('color') is-invalid @enderror"
                                onchange="updateColorPreview()">
                            @foreach(['primary','success','danger','warning','info','secondary','dark','light'] as $color)
                                <option value="{{ $color }}" {{ old('color') === $color ? 'selected' : '' }}>
                                    {{ ucfirst($color) }}
                                </option>
                            @endforeach
                        </select>
                        @error('color')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="mt-2">
                            <span class="text-muted small">Vista previa: </span>
                            <span id="colorPreview" class="badge badge-pill badge-primary">
                                {{ old('name', 'Estado') ?: 'Estado' }}
                            </span>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary btn-block">
                        <i class="fas fa-plus mr-1"></i> Crear Estado
                    </button>
                </form>
            </div>
        </div>

        {{-- Colores disponibles --}}
        <div class="card shadow-sm mt-3">
            <div class="card-header" style="background:#f8f9fa;">
                <h3 class="card-title">
                    <i class="fas fa-palette mr-2 text-secondary"></i> Colores disponibles
                </h3>
            </div>
            <div class="card-body py-2">
                <div class="d-flex flex-wrap" style="gap:6px;">
                    @foreach(['primary'=>'Azul','success'=>'Verde','danger'=>'Rojo','warning'=>'Amarillo','info'=>'Celeste','secondary'=>'Gris','dark'=>'Negro','light'=>'Blanco'] as $color => $label)
                        <span class="badge badge-pill badge-{{ $color }}" style="font-size:0.8rem;padding:5px 10px;">
                            {{ $label }}
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

</div>

@stop

@section('js')
<script>
function showEditRow(id) {
    // Hide view columns, show edit colspan cell
    document.querySelectorAll('.view-mode-' + id).forEach(function(el) {
        el.style.display = 'none';
    });
    document.querySelectorAll('.edit-mode-' + id).forEach(function(el) {
        el.style.display = '';
    });
}

function hideEditRow(id) {
    document.querySelectorAll('.view-mode-' + id).forEach(function(el) {
        el.style.display = '';
    });
    document.querySelectorAll('.edit-mode-' + id).forEach(function(el) {
        el.style.display = 'none';
    });
}

function updateColorPreview() {
    var select  = document.getElementById('newColor');
    var preview = document.getElementById('colorPreview');
    var color   = select.value;
    preview.className = 'badge badge-pill badge-' + color;
}

// Keep preview name in sync
document.addEventListener('DOMContentLoaded', function () {
    var nameInput = document.querySelector('input[name="name"]');
    var preview   = document.getElementById('colorPreview');
    if (nameInput && preview) {
        nameInput.addEventListener('input', function () {
            preview.textContent = this.value || 'Estado';
        });
    }
});
</script>
@stop
