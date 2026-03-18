@extends('adminlte::page')

@section('title', 'Nueva Sucursal')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0">
            <i class="fas fa-building text-success mr-2"></i> Nueva Sucursal
        </h1>
        <a href="{{ route('branches.index') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Volver
        </a>
    </div>
@stop

@section('content')
@include('partials._alerts')

<div class="row justify-content-center">
    <div class="col-lg-6">
        <div class="card shadow-sm">
            <div class="card-body">
                <form method="POST" action="{{ route('branches.store') }}">
                    @csrf
                    <div class="form-group">
                        <label class="font-weight-bold">Nombre</label>
                        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="form-group">
                        <label class="font-weight-bold">Ciudad</label>
                        <input type="text" name="city" class="form-control @error('city') is-invalid @enderror" value="{{ old('city') }}" required>
                        @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <button class="btn btn-success">
                        <i class="fas fa-save mr-1"></i> Guardar sucursal
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@stop
