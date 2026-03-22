@extends('adminlte::page')

@section('title', 'Otros Activos')

@section('content_header')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><span>Otros Activos</span></li>
            <li class="breadcrumb-item active">Activos Generales</li>
        </ol>
    </nav>
@stop

@section('content')

<div class="card card-outline" style="border-top:none;border-right:none;border-bottom:none;border-left:4px solid #1e3a8a;">
    <div class="card-body pb-2">
        <div class="d-flex align-items-center">
            <div class="rounded d-flex align-items-center justify-content-center mr-3"
                 style="width:52px;height:52px;background:#1e3a8a;flex-shrink:0;">
                <i class="fas fa-boxes fa-lg text-white"></i>
            </div>
            <div>
                <h4 class="mb-0 font-weight-bold">Otros Activos</h4>
                <p class="text-muted mb-0 small">Administre los activos generales de la organización</p>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <p class="hub-section-title"><i class="fas fa-boxes mr-1"></i> Activos Generales</p>
        <div class="row">

            @can('assets.view')
            <div class="col-md-4 mb-3">
                <a href="{{ route('assets.index') }}" class="hub-btn"
                   style="background:linear-gradient(135deg,#0f766e,#0d9488);">
                    <div class="hub-btn-icon"><i class="fas fa-th-list"></i></div>
                    <div class="hub-btn-text">
                        <strong>Listado de Activos</strong>
                        <small>Ver inventario general</small>
                    </div>
                </a>
            </div>
            @endcan

            @can('assets.create')
            <div class="col-md-4 mb-3">
                <a href="{{ route('assets.create') }}" class="hub-btn"
                   style="background:linear-gradient(135deg,#7c3aed,#6d28d9);">
                    <div class="hub-btn-icon"><i class="fas fa-plus-circle"></i></div>
                    <div class="hub-btn-text">
                        <strong>Crear Activo General</strong>
                        <small>Registrar nuevo activo</small>
                    </div>
                </a>
            </div>
            @endcan

            @can('assets.history.view')
            <div class="col-md-4 mb-3">
                <a href="{{ route('assets.history.index') }}" class="hub-btn"
                   style="background:linear-gradient(135deg,#374151,#4b5563);">
                    <div class="hub-btn-icon"><i class="fas fa-history"></i></div>
                    <div class="hub-btn-text">
                        <strong>Historial</strong>
                        <small>Movimientos y cambios</small>
                    </div>
                </a>
            </div>
            @endcan

        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <p class="hub-section-title"><i class="fas fa-tags mr-1"></i> Tipos de Activos</p>
        <div class="row">

            @can('asset-types.create')
            <div class="col-md-4 mb-3">
                <a href="{{ route('asset-types.create', 'OTRO') }}" class="hub-btn"
                   style="background:linear-gradient(135deg,#1e3a8a,#1d4ed8);">
                    <div class="hub-btn-icon"><i class="fas fa-plus-square"></i></div>
                    <div class="hub-btn-text">
                        <strong>Crear Tipo</strong>
                        <small>Nuevo tipo de activo</small>
                    </div>
                </a>
            </div>
            @endcan

            @can('asset-types.view')
            <div class="col-md-4 mb-3">
                <a href="{{ route('asset-types.index', 'OTRO') }}" class="hub-btn"
                   style="background:linear-gradient(135deg,#334155,#475569);">
                    <div class="hub-btn-icon"><i class="fas fa-list-alt"></i></div>
                    <div class="hub-btn-text">
                        <strong>Listado de Tipos</strong>
                        <small>Ver y gestionar tipos</small>
                    </div>
                </a>
            </div>
            @endcan

        </div>
    </div>
</div>

@stop

@section('css')
@include('partials.hub-css')
@stop
