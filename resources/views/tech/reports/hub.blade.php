@extends('adminlte::page')

@section('title', 'Reportes TI')

@section('content_header')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><span>Tecnología</span></li>
            <li class="breadcrumb-item active">Reportes TI</li>
        </ol>
    </nav>
@stop

@section('content')

<div class="card card-outline" style="border-top:none;border-right:none;border-bottom:none;border-left:4px solid #92400e;">
    <div class="card-body pb-2">
        <div class="d-flex align-items-center">
            <div class="rounded d-flex align-items-center justify-content-center mr-3"
                 style="width:52px;height:52px;background:#92400e;flex-shrink:0;">
                <i class="fas fa-chart-bar fa-lg text-white"></i>
            </div>
            <div>
                <h4 class="mb-0 font-weight-bold">Reportes TI</h4>
                <p class="text-muted mb-0 small">Genere y exporte reportes del inventario tecnológico</p>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <p class="hub-section-title"><i class="fas fa-chart-bar mr-1"></i> Reportes</p>
        <div class="row">

            @can('tech.reports.view')
            <div class="col-md-4 mb-3">
                <a href="{{ route('tech.reports.index') }}" class="hub-btn"
                   style="background:linear-gradient(135deg,#92400e,#b45309);">
                    <div class="hub-btn-icon"><i class="fas fa-chart-pie"></i></div>
                    <div class="hub-btn-text">
                        <strong>Reportes TI</strong>
                        <small>Reportes filtrables</small>
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

<div class="card shadow-sm">
    <div class="card-body">
        <p class="hub-section-title"><i class="fas fa-filter mr-1"></i> Por Dimensión</p>
        <div class="row">

            <div class="col-md-4 mb-3">
                <div class="hub-btn hub-btn-soon">
                    <div class="hub-btn-icon"><i class="fas fa-building"></i></div>
                    <div class="hub-btn-text"><strong>Por Sucursal</strong><small>Activos por sede</small></div>
                    <span class="hub-soon-badge">Próximamente</span>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="hub-btn hub-btn-soon">
                    <div class="hub-btn-icon"><i class="fas fa-sitemap"></i></div>
                    <div class="hub-btn-text"><strong>Por Área</strong><small>Activos por departamento</small></div>
                    <span class="hub-soon-badge">Próximamente</span>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="hub-btn hub-btn-soon">
                    <div class="hub-btn-icon"><i class="fas fa-tasks"></i></div>
                    <div class="hub-btn-text"><strong>Por Estado</strong><small>Activos por condición</small></div>
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
