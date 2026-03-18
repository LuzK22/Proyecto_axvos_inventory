@extends('adminlte::page')

@section('title', 'Nuevo Colaborador')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0"><i class="fas fa-user-plus text-primary mr-2"></i> Nuevo Colaborador</h1>
        <a href="{{ route('collaborators.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Volver
        </a>
    </div>
@stop

@section('content')

@if($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach($errors->all() as $e) <li>{{ $e }}</li> @endforeach
        </ul>
    </div>
@endif

<div class="card card-outline card-primary">
    <div class="card-body">
        <form method="POST" action="{{ route('collaborators.store') }}">
        @csrf

        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Nombre completo <span class="text-danger">*</span></label>
                    <input name="full_name" class="form-control" value="{{ old('full_name') }}" required>
                    @error('full_name')<small class="text-danger">{{ $message }}</small>@enderror
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Cédula / Documento <span class="text-danger">*</span></label>
                    <input name="document" class="form-control" value="{{ old('document') }}" required>
                    @error('document')<small class="text-danger">{{ $message }}</small>@enderror
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Correo electrónico</label>
                    <input name="email" type="email" class="form-control" value="{{ old('email') }}">
                    @error('email')<small class="text-danger">{{ $message }}</small>@enderror
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Teléfono</label>
                    <input name="phone" type="tel" class="form-control" value="{{ old('phone') }}" placeholder="Ej: 3001234567">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Cargo</label>
                    <input name="position" class="form-control" value="{{ old('position') }}">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Área</label>
                    <input name="area" class="form-control" value="{{ old('area') }}">
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Modalidad de Trabajo <span class="text-danger">*</span></label>
                    <select name="modalidad_trabajo" class="form-control" required>
                        <option value="presencial" {{ old('modalidad_trabajo','presencial') === 'presencial' ? 'selected' : '' }}>
                            🏢 Presencial
                        </option>
                        <option value="remoto" {{ old('modalidad_trabajo') === 'remoto' ? 'selected' : '' }}>
                            🏠 Remoto
                        </option>
                        <option value="hibrido" {{ old('modalidad_trabajo') === 'hibrido' ? 'selected' : '' }}>
                            🔄 Híbrido
                        </option>
                    </select>
                    @error('modalidad_trabajo')<small class="text-danger">{{ $message }}</small>@enderror
                </div>
            </div>
            <div class="col-md-6">
                <div class="form-group">
                    <label>Sucursal <span class="text-danger">*</span></label>
                    <select name="branch_id" class="form-control" required>
                        <option value="">Seleccione...</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}"
                                {{ old('branch_id') == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('branch_id')<small class="text-danger">{{ $message }}</small>@enderror
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end mt-2">
            <a href="{{ route('collaborators.index') }}" class="btn btn-secondary mr-2">
                <i class="fas fa-times mr-1"></i> Cancelar
            </a>
            <button type="submit" class="btn btn-success">
                <i class="fas fa-save mr-1"></i> Guardar Colaborador
            </button>
        </div>

        </form>
    </div>
</div>

@stop
