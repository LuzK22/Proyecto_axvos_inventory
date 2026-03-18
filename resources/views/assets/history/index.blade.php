@extends('adminlte::page')

@section('title', 'Historial de Otros Activos')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0">
            <i class="fas fa-history text-secondary mr-2"></i> Historial de Otros Activos
        </h1>
        <a href="{{ route('assets.hub') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Volver al hub
        </a>
    </div>
@stop

@section('content')
    <div class="alert alert-light border shadow-sm mb-0">
        Este módulo aún está en construcción. Mientras tanto puedes revisar asignaciones, bajas y reportes desde el hub de Otros Activos.
    </div>
@stop
