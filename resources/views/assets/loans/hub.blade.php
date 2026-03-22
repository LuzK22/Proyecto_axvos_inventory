@extends('adminlte::page')
@section('title', 'Préstamos — Otros Activos')

@section('content_header')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><span>Otros Activos</span></li>
            <li class="breadcrumb-item active">Préstamos</li>
        </ol>
    </nav>
@stop

@section('content')

<div class="card card-outline" style="border-top:none;border-right:none;border-bottom:none;border-left:4px solid #7c3aed;">
    <div class="card-body pb-2">
        <div class="d-flex align-items-center">
            <div class="rounded d-flex align-items-center justify-content-center mr-3"
                 style="width:52px;height:52px;background:#7c3aed;flex-shrink:0;">
                <i class="fas fa-handshake fa-lg text-white"></i>
            </div>
            <div>
                <h4 class="mb-0 font-weight-bold">Préstamos — Otros Activos</h4>
                <p class="text-muted mb-0 small">Préstamos temporales de mobiliario, enseres y otros activos entre colaboradores o sucursales</p>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <p class="hub-section-title"><i class="fas fa-handshake mr-1"></i> Préstamos</p>
        <div class="row">

            @can('assets.assign')
            <div class="col-md-4 mb-3">
                <a href="{{ route('assets.loans.create') }}" class="hub-btn"
                   style="background:linear-gradient(135deg,#7c3aed,#6d28d9);">
                    <div class="hub-btn-icon"><i class="fas fa-plus-circle"></i></div>
                    <div class="hub-btn-text">
                        <strong>Nuevo Préstamo</strong>
                        <small>Registrar préstamo temporal</small>
                    </div>
                </a>
            </div>
            @endcan

            @can('assets.view')
            <div class="col-md-4 mb-3">
                <a href="{{ route('assets.loans.index') }}" class="hub-btn"
                   style="background:linear-gradient(135deg,#0f766e,#0d9488);">
                    <div class="hub-btn-icon"><i class="fas fa-list"></i></div>
                    <div class="hub-btn-text">
                        <strong>Préstamos</strong>
                        <small>Ver y filtrar por estado</small>
                    </div>
                </a>
            </div>

            <div class="col-md-4 mb-3">
                <a href="#" class="hub-btn" style="background:linear-gradient(135deg,#065f46,#047857);"
                   data-toggle="modal" data-target="#modalExportLoans">
                    <div class="hub-btn-icon"><i class="fas fa-file-csv"></i></div>
                    <div class="hub-btn-text">
                        <strong>Exportar Excel</strong>
                        <small>Elige qué préstamos descargar</small>
                    </div>
                </a>
            </div>
            @endcan

        </div>
    </div>
</div>

{{-- Modal exportar --}}
<div class="modal fade" id="modalExportLoans" tabindex="-1">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title font-weight-bold">
                    <i class="fas fa-file-csv mr-1 text-success"></i> Exportar Préstamos
                </h6>
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
            </div>
            <div class="modal-body p-3">
                <p class="text-muted small mb-3">Selecciona qué préstamos quieres descargar:</p>
                <div class="list-group list-group-flush">
                    <a href="{{ route('assets.loans.export', ['filter'=>'activo']) }}"
                       class="list-group-item list-group-item-action py-2 px-3">
                        <i class="fas fa-handshake mr-2 text-primary"></i>
                        Solo <strong>Activos</strong>
                    </a>
                    <a href="{{ route('assets.loans.export', ['filter'=>'vencido']) }}"
                       class="list-group-item list-group-item-action py-2 px-3">
                        <i class="fas fa-exclamation-triangle mr-2 text-danger"></i>
                        Solo <strong>Vencidos</strong>
                    </a>
                    <a href="{{ route('assets.loans.export', ['filter'=>'devuelto']) }}"
                       class="list-group-item list-group-item-action py-2 px-3">
                        <i class="fas fa-undo mr-2 text-secondary"></i>
                        Solo <strong>Devueltos</strong>
                    </a>
                    <a href="{{ route('assets.loans.export') }}"
                       class="list-group-item list-group-item-action py-2 px-3">
                        <i class="fas fa-list mr-2 text-success"></i>
                        <strong>Todos</strong> los préstamos
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

@stop

@section('css')
@include('partials.hub-css')
@stop
