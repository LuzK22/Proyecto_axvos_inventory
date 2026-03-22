@extends('adminlte::page')

@section('title', 'Asignaciones — Otros Activos')

@section('content_header')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><span>Otros Activos</span></li>
            <li class="breadcrumb-item active">Asignaciones</li>
        </ol>
    </nav>
@stop

@section('content')

<div class="card card-outline" style="border-top:none;border-right:none;border-bottom:none;border-left:4px solid #1e3a8a;">
    <div class="card-body pb-2">
        <div class="d-flex align-items-center">
            <div class="rounded d-flex align-items-center justify-content-center mr-3"
                 style="width:52px;height:52px;background:#1e3a8a;flex-shrink:0;">
                <i class="fas fa-user-tag fa-lg text-white"></i>
            </div>
            <div>
                <h4 class="mb-0 font-weight-bold">Asignaciones — Otros Activos</h4>
                <p class="text-muted mb-0 small">Asigne activos generales a colaboradores o áreas</p>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <p class="hub-section-title"><i class="fas fa-user-tag mr-1"></i> Asignaciones</p>
        <div class="row">

            @can('assets.assign')
            <div class="col-md-4 mb-3">
                <a href="{{ route('assets.assignments.index') }}" class="hub-btn"
                   style="background:linear-gradient(135deg,#1e40af,#2563eb);">
                    <div class="hub-btn-icon"><i class="fas fa-th-list"></i></div>
                    <div class="hub-btn-text">
                        <strong>Ver Asignaciones</strong>
                        <small>Asignaciones activas</small>
                    </div>
                </a>
            </div>
            @endcan

            @can('assets.assign')
            <div class="col-md-4 mb-3">
                <a href="{{ route('assets.assignments.create') }}" class="hub-btn"
                   style="background:linear-gradient(135deg,#7c3aed,#6d28d9);">
                    <div class="hub-btn-icon"><i class="fas fa-plus-circle"></i></div>
                    <div class="hub-btn-text">
                        <strong>Nueva Asignación</strong>
                        <small>A colaborador o área</small>
                    </div>
                </a>
            </div>
            @endcan

        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <p class="hub-section-title"><i class="fas fa-file-signature mr-1"></i> Actas y Historial</p>
        <div class="row">

            <div class="col-md-4 mb-3">
                <a href="{{ route('actas.index', ['category' => 'OTRO']) }}" class="hub-btn"
                   style="background:linear-gradient(135deg,#065f46,#047857);">
                    <div class="hub-btn-icon"><i class="fas fa-file-alt"></i></div>
                    <div class="hub-btn-text">
                        <strong>Actas</strong>
                        <small>Ver y descargar actas</small>
                    </div>
                </a>
            </div>

            <div class="col-md-4 mb-3">
                <a href="{{ route('assets.assignments.create') }}?destination=area" class="hub-btn"
                   style="background:linear-gradient(135deg,#1e3a8a,#1d4ed8);">
                    <div class="hub-btn-icon"><i class="fas fa-building"></i></div>
                    <div class="hub-btn-text">
                        <strong>Asignar a Área</strong>
                        <small>Activo compartido por departamento</small>
                    </div>
                </a>
            </div>

            @can('assets.history.view')
            <div class="col-md-4 mb-3">
                <a href="{{ route('assets.history.index') }}" class="hub-btn"
                   style="background:linear-gradient(135deg,#374151,#4b5563);">
                    <div class="hub-btn-icon"><i class="fas fa-history"></i></div>
                    <div class="hub-btn-text">
                        <strong>Historial</strong>
                        <small>Movimientos y devoluciones</small>
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
