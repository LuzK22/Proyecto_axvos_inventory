@extends('adminlte::page')
@section('title', 'Editar Área')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <h1 class="m-0 text-dark" style="font-size:1.3rem;">
        <i class="fas fa-map-marker-alt mr-2" style="color:#7c3aed;"></i>Editar Área — {{ $area->name }}
    </h1>
    <a href="{{ route('areas.index') }}" class="btn btn-secondary btn-sm">
        <i class="fas fa-arrow-left mr-1"></i> Volver
    </a>
</div>
@stop

@section('content')
@include('partials._alerts')

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card card-outline card-primary">
            <div class="card-header py-2">
                <h6 class="mb-0 font-weight-bold">
                    <i class="fas fa-edit mr-1 text-primary"></i> Editar datos del área
                </h6>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('areas.update', $area) }}">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label class="text-muted small mb-1">
                            Nombre <span class="text-danger">*</span>
                        </label>
                        <input type="text"
                               name="name"
                               class="form-control form-control-sm @error('name') is-invalid @enderror"
                               value="{{ old('name', $area->name) }}"
                               required>
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="text-muted small mb-1">Sucursal</label>
                        <select name="branch_id"
                                class="form-control form-control-sm @error('branch_id') is-invalid @enderror">
                            <option value="">— Sin sucursal específica —</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}"
                                        {{ old('branch_id', $area->branch_id) == $branch->id ? 'selected' : '' }}>
                                    {{ $branch->name }}{{ $branch->active ? '' : ' (Inactiva)' }}
                                </option>
                            @endforeach
                        </select>
                        @error('branch_id')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group">
                        <label class="text-muted small mb-1">Descripción (opcional)</label>
                        <textarea name="description"
                                  rows="2"
                                  class="form-control form-control-sm @error('description') is-invalid @enderror"
                                  placeholder="Ubicación, capacidad u otras observaciones...">{{ old('description', $area->description) }}</textarea>
                        @error('description')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="form-group mb-0">
                        <div class="custom-control custom-switch">
                            <input type="hidden" name="active" value="0">
                            <input type="checkbox"
                                   class="custom-control-input"
                                   id="activeSwitch"
                                   name="active"
                                   value="1"
                                   {{ old('active', $area->active) ? 'checked' : '' }}>
                            <label class="custom-control-label text-muted small" for="activeSwitch">
                                Área activa (disponible para asignaciones)
                            </label>
                        </div>
                    </div>

                    <hr class="my-3">

                    <div class="d-flex justify-content-end">
                        <a href="{{ route('areas.index') }}" class="btn btn-secondary btn-sm mr-2">Cancelar</a>
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="fas fa-save mr-1"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@stop
