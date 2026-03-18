@extends('adminlte::page')

@section('title', 'Nueva Plantilla de Asignación')

@section('content_header')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.assignment-templates.index') }}">Plantillas</a></li>
            <li class="breadcrumb-item active">Nueva Plantilla</li>
        </ol>
    </nav>
@stop

@section('content')

<form method="POST" action="{{ route('admin.assignment-templates.store') }}" id="form-template">
@csrf

<div class="row">
    <div class="col-lg-8">

        {{-- Datos de la plantilla --}}
        <div class="card shadow-sm">
            <div class="card-header py-2">
                <h6 class="mb-0 font-weight-bold">Datos de la Plantilla</h6>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label class="font-weight-bold">Tipo de asignación <span class="text-danger">*</span></label>
                    <select name="assignment_type_id" class="form-control" required>
                        <option value="">Seleccionar...</option>
                        @foreach($types as $type)
                            <option value="{{ $type->id }}" {{ old('assignment_type_id') == $type->id ? 'selected' : '' }}>
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label class="font-weight-bold">Nombre de la plantilla <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control"
                                   value="{{ old('name') }}"
                                   placeholder="Ej: Trabajo Remoto, Cargo Gerente..."
                                   required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="font-weight-bold">Valor disparador</label>
                            <input type="text" name="trigger_value" class="form-control"
                                   value="{{ old('trigger_value') }}"
                                   placeholder="remoto, gerente, area_b...">
                            <small class="text-muted">Cuando el campo del tipo coincida con este valor, se sugiere esta plantilla.</small>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold">Descripción</label>
                    <textarea name="description" class="form-control" rows="2"
                              placeholder="Descripción breve de cuándo aplica esta plantilla...">{{ old('description') }}</textarea>
                </div>
            </div>
        </div>

        {{-- Activos de la plantilla --}}
        <div class="card shadow-sm">
            <div class="card-header py-2 d-flex align-items-center justify-content-between">
                <h6 class="mb-0 font-weight-bold">Activos de la Plantilla</h6>
                <button type="button" class="btn btn-sm btn-outline-primary" id="btn-add-item">
                    <i class="fas fa-plus mr-1"></i> Agregar activo
                </button>
            </div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0" id="items-table">
                    <thead class="thead-light">
                        <tr>
                            <th>Tipo de Activo</th>
                            <th style="width:80px">Cantidad</th>
                            <th style="width:160px">Va a...</th>
                            <th>Notas</th>
                            <th style="width:40px"></th>
                        </tr>
                    </thead>
                    <tbody id="items-body">
                        {{-- filas dinámicas --}}
                    </tbody>
                </table>
                <div id="items-empty" class="text-center text-muted py-4 small">
                    <i class="fas fa-box-open d-block mb-1"></i>
                    Agregue al menos un tipo de activo
                </div>
            </div>
        </div>

    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <p class="hub-section-title">Destinos disponibles</p>
                <div class="mb-2">
                    <span class="badge badge-primary">Colaborador</span>
                    <small class="text-muted d-block mt-1">El activo va directamente con el colaborador asignado.</small>
                </div>
                <div class="mb-2">
                    <span class="badge badge-secondary">Jefe / Responsable</span>
                    <small class="text-muted d-block mt-1">Queda asignado al jefe del área (ej: monitor en trabajo remoto).</small>
                </div>
                <div class="mb-2">
                    <span class="badge badge-info">Área</span>
                    <small class="text-muted d-block mt-1">Queda en el área compartida de la oficina.</small>
                </div>
                <div class="mb-2">
                    <span class="badge badge-warning text-dark">Pool compartido</span>
                    <small class="text-muted d-block mt-1">Activo de uso rotativo, sin asignación fija.</small>
                </div>
            </div>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-save mr-1"></i> Guardar Plantilla
                </button>
                <a href="{{ route('admin.assignment-templates.index') }}" class="btn btn-outline-secondary btn-block mt-2">
                    Cancelar
                </a>
            </div>
        </div>
    </div>
</div>

</form>

{{-- Template fila --}}
<template id="item-row-template">
    <tr class="item-row">
        <td>
            <select name="items[__IDX__][asset_type_id]" class="form-control form-control-sm" required>
                <option value="">Tipo...</option>
                @foreach($assetTypes as $at)
                    <option value="{{ $at->id }}" data-category="{{ $at->category }}">
                        [{{ $at->category }}] {{ $at->name }}
                    </option>
                @endforeach
            </select>
        </td>
        <td>
            <input type="number" name="items[__IDX__][quantity]" class="form-control form-control-sm"
                   value="1" min="1" max="10" required>
        </td>
        <td>
            <select name="items[__IDX__][goes_to]" class="form-control form-control-sm" required>
                <option value="assignee">Colaborador</option>
                <option value="jefe">Jefe / Responsable</option>
                <option value="area">Área</option>
                <option value="pool">Pool compartido</option>
            </select>
        </td>
        <td>
            <input type="text" name="items[__IDX__][notes]" class="form-control form-control-sm"
                   placeholder="Nota opcional...">
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-xs btn-outline-danger btn-remove-item">
                <i class="fas fa-times"></i>
            </button>
        </td>
    </tr>
</template>

@stop

@section('css')
@include('partials.hub-css')
@stop

@section('js')
<script>
let itemIndex = 0;
const tmpl = document.getElementById('item-row-template').innerHTML;

function updateEmpty() {
    const rows = $('#items-body tr').length;
    $('#items-empty').toggle(rows === 0);
    $('#items-table thead').toggle(rows > 0);
}

$('#btn-add-item').on('click', function() {
    const row = tmpl.replace(/__IDX__/g, itemIndex++);
    $('#items-body').append(row);
    updateEmpty();
});

$(document).on('click', '.btn-remove-item', function() {
    $(this).closest('tr').remove();
    updateEmpty();
});

updateEmpty();
</script>
@stop
