@extends('adminlte::page')

@section('title', 'Bajas TI')

@section('content_header')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><span>Tecnología</span></li>
            <li class="breadcrumb-item active">Bajas TI</li>
        </ol>
    </nav>
@stop

@section('content')

<div class="card card-outline" style="border-top:none;border-right:none;border-bottom:none;border-left:4px solid #991b1b;">
    <div class="card-body pb-2">
        <div class="d-flex align-items-center">
            <div class="rounded d-flex align-items-center justify-content-center mr-3"
                 style="width:52px;height:52px;background:#991b1b;flex-shrink:0;">
                <i class="fas fa-ban fa-lg text-white"></i>
            </div>
            <div>
                <h4 class="mb-0 font-weight-bold">Bajas TI</h4>
                <p class="text-muted mb-0 small">Gestione las bajas y desincorporaciones de activos tecnológicos</p>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <p class="hub-section-title"><i class="fas fa-ban mr-1"></i> Gestión de Bajas</p>
        <div class="row">

            @can('tech.assets.disposal.view')
            <div class="col-md-4 mb-3">
                <a href="{{ route('tech.disposals.index') }}" class="hub-btn"
                   style="background:linear-gradient(135deg,#991b1b,#b91c1c);">
                    <div class="hub-btn-icon"><i class="fas fa-clipboard-list"></i></div>
                    <div class="hub-btn-text">
                        <strong>Solicitudes de Baja</strong>
                        <small>Ver solicitudes registradas</small>
                    </div>
                </a>
            </div>
            @endcan

            @can('tech.assets.disposal.request')
            <div class="col-md-4 mb-3">
                <div class="hub-btn hub-btn-soon">
                    <div class="hub-btn-icon"><i class="fas fa-file-medical-alt"></i></div>
                    <div class="hub-btn-text"><strong>Nueva Solicitud</strong><small>Solicitar baja de activo</small></div>
                    <span class="hub-soon-badge">Próximamente</span>
                </div>
            </div>
            @endcan

            @can('tech.assets.disposal.approve')
            <div class="col-md-4 mb-3">
                <div class="hub-btn hub-btn-soon">
                    <div class="hub-btn-icon"><i class="fas fa-check-circle"></i></div>
                    <div class="hub-btn-text"><strong>Aprobaciones Pendientes</strong><small>Revisar y aprobar bajas</small></div>
                    <span class="hub-soon-badge">Próximamente</span>
                </div>
            </div>
            @endcan

        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <p class="hub-section-title"><i class="fas fa-history mr-1"></i> Historial</p>
        <div class="row">

            <div class="col-md-4 mb-3">
                <div class="hub-btn hub-btn-soon">
                    <div class="hub-btn-icon"><i class="fas fa-history"></i></div>
                    <div class="hub-btn-text"><strong>Historial de Bajas</strong><small>Activos dados de baja</small></div>
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
