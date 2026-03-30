@extends('adminlte::page')

@section('title', 'Activos TI')

@section('content_header')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><span>Tecnología</span></li>
            <li class="breadcrumb-item active">Activos TI</li>
        </ol>
    </nav>
@stop

@section('content')

<div class="card card-outline" style="border-top:none;border-right:none;border-bottom:none;border-left:4px solid #1e3a8a;">
    <div class="card-body pb-2">
        <div class="d-flex align-items-center">
            <div class="rounded d-flex align-items-center justify-content-center mr-3"
                 style="width:52px;height:52px;background:#1e3a8a;flex-shrink:0;">
                <i class="fas fa-desktop fa-lg text-white"></i>
            </div>
            <div>
                <h4 class="mb-0 font-weight-bold">Activos TI</h4>
                <p class="text-muted mb-0 small">Administre los activos tecnológicos de la organización</p>
            </div>
        </div>
    </div>
</div>

{{-- ── Sección 1: Activos TI ─────────────────────────────────────────────── --}}
<div class="card shadow-sm">
    <div class="card-body">
        <p class="hub-section-title"><i class="fas fa-laptop mr-1"></i> Activos TI</p>
        <div class="row">

            @can('tech.assets.create')
            <div class="col-md-4 mb-3">
                <a href="{{ route('tech.assets.create') }}" class="hub-btn"
                   style="background:linear-gradient(135deg,#1e3a8a,#1d4ed8);">
                    <div class="hub-btn-icon"><i class="fas fa-plus-circle"></i></div>
                    <div class="hub-btn-text">
                        <strong>Crear Activo TI</strong>
                        <small>Registrar nuevo equipo</small>
                    </div>
                </a>
            </div>
            @endcan

            @can('tech.assets.view')
            <div class="col-md-4 mb-3">
                <a href="{{ route('tech.assets.index') }}" class="hub-btn"
                   style="background:linear-gradient(135deg,#0f766e,#0d9488);">
                    <div class="hub-btn-icon"><i class="fas fa-th-list"></i></div>
                    <div class="hub-btn-text">
                        <strong>Listado de Activos</strong>
                        <small>Ver inventario completo</small>
                    </div>
                </a>
            </div>

            <div class="col-md-4 mb-3">
                <a href="{{ route('tech.assets.index') }}" class="hub-btn"
                   style="background:linear-gradient(135deg,#3730a3,#4338ca);">
                    <div class="hub-btn-icon"><i class="fas fa-search"></i></div>
                    <div class="hub-btn-text">
                        <strong>Buscar Activo</strong>
                        <small>Por serial, marca o código</small>
                    </div>
                </a>
            </div>
            @endcan

        </div>
    </div>
</div>

{{-- ── Sección 2: Tipos de Activo TI ───────────────────────────────────────── --}}
<div class="card shadow-sm">
    <div class="card-body">
        <p class="hub-section-title"><i class="fas fa-tags mr-1"></i> Tipos de Activo TI</p>
        <div class="row">

            @can('tech.types.create')
            <div class="col-md-4 mb-3">
                <a href="{{ route('tech.types.create') }}" class="hub-btn"
                   style="background:linear-gradient(135deg,#1e3a8a,#1d4ed8);">
                    <div class="hub-btn-icon"><i class="fas fa-plus-square"></i></div>
                    <div class="hub-btn-text">
                        <strong>Crear Tipo</strong>
                        <small>Nuevo tipo de activo TI</small>
                    </div>
                </a>
            </div>
            @endcan

            @can('tech.types.view')
            <div class="col-md-4 mb-3">
                <a href="{{ route('tech.types.index') }}" class="hub-btn"
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

{{-- ── Sección 3: Historial ────────────────────────────────────────────────── --}}
<div class="card shadow-sm">
    <div class="card-body">
        <p class="hub-section-title"><i class="fas fa-history mr-1"></i> Historial</p>
        <div class="row">

            @can('tech.history.view')
            <div class="col-md-4 mb-3">
                <a href="{{ route('tech.assets.history.index') }}" class="hub-btn"
                   style="background:linear-gradient(135deg,#1e293b,#334155);">
                    <div class="hub-btn-icon"><i class="fas fa-history"></i></div>
                    <div class="hub-btn-text">
                        <strong>Historial de Activos</strong>
                        <small>Movimientos y asignaciones</small>
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
