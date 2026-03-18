@extends('adminlte::page')

@section('title', 'Editar Plantilla')

@section('content_header')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.assignment-templates.index') }}">Plantillas</a></li>
            <li class="breadcrumb-item active">{{ $assignmentTemplate->name }}</li>
        </ol>
    </nav>
@stop

@section('content')

<form method="POST" action="{{ route('admin.assignment-templates.update', $assignmentTemplate) }}" id="form-template">
@csrf
@method('PUT')

<div class="row">
    <div class="col-lg-8">

        <div class="card shadow-sm">
            <div class="card-header py-2">
                <h6 class="mb-0 font-weight-bold">Datos de la Plantilla</h6>
            </div>
            <div class="card-body">
                <div class="form-group">
                    <label class="font-weight-bold">Tipo de asignación <span class="text-danger">*</span></label>
                    <select name="assignment_type_id" class="form-control" required>
                        @foreach($types as $type)
                            <option value="{{ $type->id }}" {{ $assignmentTemplate->assignment_type_id == $type->id ? 'selected' : '' }}>
                                {{ $type->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <div class="form-row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label class="font-weight-bold">Nombre <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control"
                                   value="{{ old('name', $assignmentTemplate->name) }}" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="font-weight-bold">Valor disparador</label>
                            <input type="text" name="trigger_value" class="form-control"
                                   value="{{ old('trigger_value', $assignmentTemplate->trigger_value) }}">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold">Descripción</label>
                    <textarea name="description" class="form-control" rows="2">{{ old('description', $assignmentTemplate->description) }}</textarea>
                </div>

                <div class="custom-control custom-switch">
                    <input type="checkbox" class="custom-control-input" id="active" name="active"
                           value="1" {{ $assignmentTemplate->active ? 'checked' : '' }}>
                    <label class="custom-control-label" for="active">Plantilla activa</label>
                </div>
            </div>
        </div>

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
                        @foreach($assignmentTemplate->items as $i => $item)
                        <tr class="item-row">
                            <td>
                                <select name="items[{{ $i }}][asset_type_id]" class="form-control form-control-sm" required>
                                    @foreach($assetTypes as $at)
                                        <option value="{{ $at->id }}" {{ $item->asset_type_id == $at->id ? 'selected' : '' }}>
                                            [{{ $at->category }}] {{ $at->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </td>
                            <td>
                                <input type="number" name="items[{{ $i }}][quantity]" class="form-control form-control-sm"
                                       value="{{ $item->quantity }}" min="1" max="10" required>
                            </td>
                            <td>
                                <select name="items[{{ $i }}][goes_to]" class="form-control form-control-sm" required>
                                    <option value="assignee" {{ $item->goes_to === 'assignee' ? 'selected' : '' }}>Colaborador</option>
                                    <option value="jefe"     {{ $item->goes_to === 'jefe'     ? 'selected' : '' }}>Jefe / Responsable</option>
                                    <option value="area"     {{ $item->goes_to === 'area'     ? 'selected' : '' }}>Área</option>
                                    <option value="pool"     {{ $item->goes_to === 'pool'     ? 'selected' : '' }}>Pool compartido</option>
                                </select>
                            </td>
                            <td>
                                <input type="text" name="items[{{ $i }}][notes]" class="form-control form-control-sm"
                                       value="{{ $item->notes }}" placeholder="Nota opcional...">
                            </td>
                            <td class="text-center">
                                <button type="button" class="btn btn-xs btn-outline-danger btn-remove-item">
                                    <i class="fas fa-times"></i>
                                </button>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
                <div id="items-empty" class="text-center text-muted py-4 small" style="{{ $assignmentTemplate->items->isEmpty() ? '' : 'display:none' }}">
                    <i class="fas fa-box-open d-block mb-1"></i> Agregue al menos un tipo de activo
                </div>
            </div>
        </div>

    </div>

    <div class="col-lg-4">
        <div class="card shadow-sm">
            <div class="card-body">
                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-save mr-1"></i> Guardar Cambios
                </button>
                <a href="{{ route('admin.assignment-templates.index') }}" class="btn btn-outline-secondary btn-block mt-2">
                    Cancelar
                </a>
            </div>
        </div>
    </div>
</div>

</form>

<template id="item-row-template">
    <tr class="item-row">
        <td>
            <select name="items[__IDX__][asset_type_id]" class="form-control form-control-sm" required>
                <option value="">Tipo...</option>
                @foreach($assetTypes as $at)
                    <option value="{{ $at->id }}">[{{ $at->category }}] {{ $at->name }}</option>
                @endforeach
            </select>
        </td>
        <td><input type="number" name="items[__IDX__][quantity]" class="form-control form-control-sm" value="1" min="1" max="10" required></td>
        <td>
            <select name="items[__IDX__][goes_to]" class="form-control form-control-sm" required>
                <option value="assignee">Colaborador</option>
                <option value="jefe">Jefe / Responsable</option>
                <option value="area">Área</option>
                <option value="pool">Pool compartido</option>
            </select>
        </td>
        <td><input type="text" name="items[__IDX__][notes]" class="form-control form-control-sm" placeholder="Nota opcional..."></td>
        <td class="text-center">
            <button type="button" class="btn btn-xs btn-outline-danger btn-remove-item"><i class="fas fa-times"></i></button>
        </td>
    </tr>
</template>

@stop

@section('css')
@include('partials.hub-css')
@stop

@section('js')
<script>
let itemIndex = {{ $assignmentTemplate->items->count() }};
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
</script>
@stop
