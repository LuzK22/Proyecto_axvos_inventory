@extends('adminlte::page')

@section('title', 'Administración')

@section('content_header')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Administración</li>
        </ol>
    </nav>
@stop

@section('content')

<div class="card card-outline" style="border-top:none;border-right:none;border-bottom:none;border-left:4px solid #374151;">
    <div class="card-body pb-2">
        <div class="d-flex align-items-center">
            <div class="rounded d-flex align-items-center justify-content-center mr-3"
                 style="width:52px;height:52px;background:#374151;flex-shrink:0;">
                <i class="fas fa-cog fa-lg text-white"></i>
            </div>
            <div>
                <h4 class="mb-0 font-weight-bold">Administración</h4>
                <p class="text-muted mb-0 small">Gestione usuarios, sucursales, categorías y configuración del sistema</p>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <p class="hub-section-title"><i class="fas fa-users mr-1"></i> Usuarios y Organización</p>
        <div class="row">

            @can('users.manage')
            <div class="col-md-4 mb-3">
                <a href="{{ route('users.index') }}" class="hub-btn"
                   style="background:linear-gradient(135deg,#1e3a8a,#1d4ed8);">
                    <div class="hub-btn-icon"><i class="fas fa-users-cog"></i></div>
                    <div class="hub-btn-text">
                        <strong>Usuarios del Sistema</strong>
                        <small>Gestionar accesos y roles</small>
                    </div>
                </a>
            </div>
            @endcan

            @can('branches.manage')
            <div class="col-md-4 mb-3">
                <a href="{{ route('branches.index') }}" class="hub-btn"
                   style="background:linear-gradient(135deg,#0f766e,#0d9488);">
                    <div class="hub-btn-icon"><i class="fas fa-building"></i></div>
                    <div class="hub-btn-text">
                        <strong>Sucursales</strong>
                        <small>Sedes y ubicaciones</small>
                    </div>
                </a>
            </div>
            @endcan

            @can('categories.manage')
            <div class="col-md-4 mb-3">
                <a href="{{ route('categories.index') }}" class="hub-btn"
                   style="background:linear-gradient(135deg,#3730a3,#4338ca);">
                    <div class="hub-btn-icon"><i class="fas fa-folder"></i></div>
                    <div class="hub-btn-text">
                        <strong>Categorías</strong>
                        <small>Clasificación de activos</small>
                    </div>
                </a>
            </div>
            @endcan

        </div>
        <div class="row">

            @can('statuses.manage')
            <div class="col-md-4 mb-3">
                <a href="{{ route('statuses.index') }}" class="hub-btn"
                   style="background:linear-gradient(135deg,#334155,#475569);">
                    <div class="hub-btn-icon"><i class="fas fa-toggle-on"></i></div>
                    <div class="hub-btn-text">
                        <strong>Estados</strong>
                        <small>Estados de activos</small>
                    </div>
                </a>
            </div>
            @endcan

            @can('assets.assign')
            <div class="col-md-4 mb-3">
                <a href="{{ route('areas.index') }}" class="hub-btn"
                   style="background:linear-gradient(135deg,#1d4ed8,#2563eb);">
                    <div class="hub-btn-icon"><i class="fas fa-map-marked-alt"></i></div>
                    <div class="hub-btn-text">
                        <strong>Areas / Pools</strong>
                        <small>Gestion de destinos compartidos</small>
                    </div>
                </a>
            </div>
            @endcan

        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <p class="hub-section-title"><i class="fas fa-sliders-h mr-1"></i> Configuración del Sistema</p>
        <div class="row">

            @can('admin.settings')
            <div class="col-md-4 mb-3">
                <a href="{{ route('admin.settings') }}" class="hub-btn"
                   style="background:linear-gradient(135deg,#1e293b,#334155);">
                    <div class="hub-btn-icon"><i class="fas fa-cog"></i></div>
                    <div class="hub-btn-text">
                        <strong>Configuración</strong>
                        <small>Datos empresa, logo, actas</small>
                    </div>
                </a>
            </div>

            <div class="col-md-4 mb-3">
                <a href="{{ route('admin.assignment-templates.index') }}" class="hub-btn"
                   style="background:linear-gradient(135deg,#3730a3,#4338ca);">
                    <div class="hub-btn-icon"><i class="fas fa-layer-group"></i></div>
                    <div class="hub-btn-text">
                        <strong>Plantillas de Asignación</strong>
                        <small>Configura activos por modalidad, cargo o área</small>
                    </div>
                </a>
            </div>
            @endcan

            @can('users.manage')
            <div class="col-md-4 mb-3">
                <a href="{{ route('admin.permissions.index') }}" class="hub-btn"
                   style="background:linear-gradient(135deg,#7c3aed,#6d28d9);">
                    <div class="hub-btn-icon"><i class="fas fa-key"></i></div>
                    <div class="hub-btn-text">
                        <strong>Permisos</strong>
                        <small>Matriz de permisos por rol</small>
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
