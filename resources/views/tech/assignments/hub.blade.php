@extends('adminlte::page')

@section('title', 'Asignaciones TI')

@section('content_header')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><span>Tecnología</span></li>
            <li class="breadcrumb-item active">Asignaciones TI</li>
        </ol>
    </nav>
@stop

@section('content')

<div class="card card-outline" style="border-top:none;border-right:none;border-bottom:none;border-left:4px solid #1e3a8a;">
    <div class="card-body pb-2">
        <div class="d-flex align-items-center">
            <div class="rounded d-flex align-items-center justify-content-center mr-3"
                 style="width:52px;height:52px;background:#1e3a8a;flex-shrink:0;">
                <i class="fas fa-user-check fa-lg text-white"></i>
            </div>
            <div>
                <h4 class="mb-0 font-weight-bold">Asignaciones TI</h4>
                <p class="text-muted mb-0 small">Asigne y gestione activos tecnológicos a colaboradores</p>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <p class="hub-section-title"><i class="fas fa-user-check mr-1"></i> Asignaciones</p>
        <div class="row">

            @can('tech.assets.assign')
            <div class="col-md-4 mb-3">
                <a href="{{ route('tech.assignments.create') }}" class="hub-btn"
                   style="background:linear-gradient(135deg,#1e3a8a,#1d4ed8);">
                    <div class="hub-btn-icon"><i class="fas fa-plus-circle"></i></div>
                    <div class="hub-btn-text">
                        <strong>Nueva Asignación</strong>
                        <small>Asignar activos a colaborador</small>
                    </div>
                </a>
            </div>

            <div class="col-md-4 mb-3">
                <a href="{{ route('tech.assignments.index') }}" class="hub-btn"
                   style="background:linear-gradient(135deg,#0f766e,#0d9488);">
                    <div class="hub-btn-icon"><i class="fas fa-th-list"></i></div>
                    <div class="hub-btn-text">
                        <strong>Ver Asignaciones</strong>
                        <small>Asignaciones activas</small>
                    </div>
                </a>
            </div>

            <div class="col-md-4 mb-3">
                <a href="{{ route('tech.assignments.search') }}" class="hub-btn"
                   style="background:linear-gradient(135deg,#3730a3,#4338ca);">
                    <div class="hub-btn-icon"><i class="fas fa-search"></i></div>
                    <div class="hub-btn-text">
                        <strong>Buscar Colaborador</strong>
                        <small>Ver activos asignados</small>
                    </div>
                </a>
            </div>
            @endcan

        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <p class="hub-section-title"><i class="fas fa-file-signature mr-1"></i> Actas y Devoluciones</p>
        <div class="row">

            @can('tech.history.view')
            <div class="col-md-4 mb-3">
                <a href="{{ route('tech.history.index') }}" class="hub-btn"
                   style="background:linear-gradient(135deg,#374151,#4b5563);">
                    <div class="hub-btn-icon"><i class="fas fa-history"></i></div>
                    <div class="hub-btn-text">
                        <strong>Historial de Asignaciones</strong>
                        <small>Devoluciones y movimientos</small>
                    </div>
                </a>
            </div>
            @endcan

            <div class="col-md-4 mb-3">
                <a href="{{ route('actas.index', ['category' => 'TI']) }}" class="hub-btn"
                   style="background:linear-gradient(135deg,#065f46,#047857);">
                    <div class="hub-btn-icon"><i class="fas fa-file-signature"></i></div>
                    <div class="hub-btn-text">
                        <strong>Actas</strong>
                        <small>Entrega · Devolución · Préstamo</small>
                    </div>
                </a>
            </div>


        </div>
    </div>
</div>

@stop

@section('css')
@include('partials.hub-css')
@stop
