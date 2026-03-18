@extends('adminlte::page')

@section('title', 'Asistente IA')

@section('content_header')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Asistente IA</li>
        </ol>
    </nav>
@stop

@section('content')

<div class="card card-outline" style="border-top:none;border-right:none;border-bottom:none;border-left:4px solid #5b21b6;">
    <div class="card-body pb-2">
        <div class="d-flex align-items-center">
            <div class="rounded d-flex align-items-center justify-content-center mr-3"
                 style="width:52px;height:52px;background:linear-gradient(135deg,#5b21b6,#7c3aed);flex-shrink:0;">
                <i class="fas fa-robot fa-lg text-white"></i>
            </div>
            <div>
                <h4 class="mb-0 font-weight-bold">Asistente IA</h4>
                <p class="text-muted mb-0 small">Consulte el inventario en lenguaje natural</p>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body text-center py-5">
        <div class="rounded-circle d-inline-flex align-items-center justify-content-center mb-3"
             style="width:72px;height:72px;background:linear-gradient(135deg,#5b21b6,#7c3aed);">
            <i class="fas fa-robot fa-2x text-white"></i>
        </div>
        <h5 class="font-weight-bold">Asistente de Inventario IA</h5>
        <p class="text-muted mb-1">Realice consultas sobre activos, asignaciones y reportes en lenguaje natural.</p>
        <p class="text-muted small">Ejemplo: "¿Cuántos laptops están disponibles en la sucursal Bogotá?"</p>
        <span class="badge px-3 py-2 mt-2" style="background:#5b21b6;color:#fff;font-size:0.82rem;">
            <i class="fas fa-clock mr-1"></i> Próximamente disponible
        </span>
    </div>
</div>

@stop

@section('css')
@include('partials.hub-css')
@stop
