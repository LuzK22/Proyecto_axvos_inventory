@extends('adminlte::page')

@section('title', 'Reportes — Otros Activos')

@section('content_header')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><span>Otros Activos</span></li>
            <li class="breadcrumb-item active">Reportes</li>
        </ol>
    </nav>
@stop

@section('content')

<div class="card card-outline" style="border-top:none;border-right:none;border-bottom:none;border-left:4px solid #1e3a8a;">
    <div class="card-body pb-2">
        <div class="d-flex align-items-center">
            <div class="rounded d-flex align-items-center justify-content-center mr-3"
                 style="width:52px;height:52px;background:#1e3a8a;flex-shrink:0;">
                <i class="fas fa-file-excel fa-lg text-white"></i>
            </div>
            <div>
                <h4 class="mb-0 font-weight-bold">Reportes — Otros Activos</h4>
                <p class="text-muted mb-0 small">Genere y exporte reportes del inventario general</p>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <p class="hub-section-title"><i class="fas fa-chart-bar mr-1"></i> Reportes</p>
        <div class="row">

            @can('assets.reports.view')
            <div class="col-md-4 mb-3">
                <a href="{{ route('assets.reports.index') }}" class="hub-btn"
                   style="background:linear-gradient(135deg,#1e3a8a,#1d4ed8);">
                    <div class="hub-btn-icon"><i class="fas fa-chart-pie"></i></div>
                    <div class="hub-btn-text">
                        <strong>Reportes Filtrables</strong>
                        <small>Activos generales</small>
                    </div>
                </a>
            </div>
            @endcan

            <div class="col-md-4 mb-3">
                <div class="hub-btn hub-btn-soon">
                    <div class="hub-btn-icon"><i class="fas fa-file-excel"></i></div>
                    <div class="hub-btn-text"><strong>Exportar a Excel</strong><small>Descargar inventario</small></div>
                    <span class="hub-soon-badge">Próximamente</span>
                </div>
            </div>

        </div>
    </div>
</div>

@stop

@section('css')
@include('partials.hub-css')
@stop
