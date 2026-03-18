@extends('adminlte::page')

@section('title', 'Reporte Global')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0">
            <i class="fas fa-chart-line text-primary mr-2"></i> Reporte Global
        </h1>
        <a href="{{ route('audit.hub') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Volver a auditoría
        </a>
    </div>
@stop

@section('content')
    <div class="alert alert-light border shadow-sm mb-0">
        El reporte global aún se encuentra en consolidación. Usa el módulo de auditoría para consultar y exportar la información disponible.
    </div>
@stop
