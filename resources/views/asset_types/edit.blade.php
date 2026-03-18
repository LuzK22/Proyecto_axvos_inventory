@extends('adminlte::page')

@section('title', 'Editar Tipo')

@section('content_header')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            @if($assetType->category === 'TI')
                <li class="breadcrumb-item"><a href="{{ route('tech.assets.hub') }}">Activos TI</a></li>
                <li class="breadcrumb-item"><a href="{{ route('tech.types.index') }}">Tipos TI</a></li>
            @else
                <li class="breadcrumb-item"><a href="{{ route('assets.hub') }}">Otros Activos</a></li>
                <li class="breadcrumb-item"><a href="{{ route('asset-types.index', 'OTRO') }}">Tipos</a></li>
            @endif
            <li class="breadcrumb-item active">{{ $assetType->name }}</li>
        </ol>
    </nav>
@stop

@section('content')

<div class="row justify-content-center">
    <div class="col-lg-6 col-md-8">

        <div class="card shadow-sm">
            <div class="card-header py-2" style="border-left:4px solid #334155;">
                <h6 class="mb-0 font-weight-bold">
                    <i class="fas fa-pencil-alt mr-1" style="color:#334155;"></i>
                    Editar Tipo de Activo
                    <span class="badge badge-secondary ml-1" style="font-size:.7rem;">{{ $assetType->category }}</span>
                </h6>
            </div>
            <div class="card-body">

                @if($errors->any())
                    <div class="alert alert-danger py-2">
                        <ul class="mb-0 pl-3">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <form method="POST" action="{{ route('asset-types.update', $assetType) }}">
                    @csrf
                    @method('PUT')

                    <div class="form-group">
                        <label class="font-weight-bold">Nombre <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control"
                               value="{{ old('name', $assetType->name) }}" required autofocus>
                    </div>

                    <div class="form-group mb-0">
                        <label class="font-weight-bold">Código interno</label>
                        <input type="text" class="form-control" value="{{ $assetType->code }}" disabled>
                        <small class="text-muted">El código no puede modificarse.</small>
                    </div>

                    <div class="custom-control custom-switch mt-3">
                        <input type="checkbox" class="custom-control-input" id="active" name="active"
                               value="1" {{ $assetType->active ? 'checked' : '' }}>
                        <label class="custom-control-label" for="active">Tipo activo</label>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-4">
                        @if($assetType->category === 'TI')
                            <a href="{{ route('tech.types.index') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-arrow-left mr-1"></i> Volver
                            </a>
                        @else
                            <a href="{{ route('asset-types.index', 'OTRO') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="fas fa-arrow-left mr-1"></i> Volver
                            </a>
                        @endif
                        <button type="submit" class="btn btn-primary btn-sm px-4">
                            <i class="fas fa-save mr-1"></i> Guardar Cambios
                        </button>
                    </div>
                </form>

            </div>
        </div>

    </div>
</div>

@stop

@section('css')
<style>
.card { border-radius: 10px; }
</style>
@stop
