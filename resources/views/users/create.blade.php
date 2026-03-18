@extends('adminlte::page')

@section('title', 'Crear Usuario')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0">
            <i class="fas fa-user-plus text-primary mr-2"></i> Crear Usuario
        </h1>
        <a href="{{ route('users.index') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Volver
        </a>
    </div>
@stop

@section('content')
    <div class="alert alert-light border shadow-sm mb-0">
        La interfaz completa de administración de usuarios aún está pendiente. Por ahora, utiliza el flujo de registro/autenticación existente o amplía este módulo según la necesidad del proyecto.
    </div>
@stop
