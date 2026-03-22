@extends('adminlte::page')
@section('title', 'Préstamos TI')

@section('content_header')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item">Tecnología</li>
            <li class="breadcrumb-item active">Préstamos TI</li>
        </ol>
    </nav>
@stop

@section('content')
<div class="card card-outline" style="border-top:none;border-right:none;border-bottom:none;border-left:4px solid #1e3a8a;">
    <div class="card-body pb-2">
        <div class="d-flex align-items-center">
            <div class="rounded d-flex align-items-center justify-content-center mr-3"
                 style="width:52px;height:52px;background:#1e3a8a;flex-shrink:0;">
                <i class="fas fa-handshake fa-lg text-white"></i>
            </div>
            <div>
                <h4 class="mb-0 font-weight-bold">Préstamos TI</h4>
                <p class="text-muted mb-0 small">Gestione préstamos temporales de activos tecnológicos</p>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <p class="hub-section-title"><i class="fas fa-handshake mr-1"></i> Préstamos</p>
        <div class="row">
            <div class="col-md-4 mb-3">
                <a href="{{ route('tech.loans.create') }}" class="text-decoration-none">
                    <div class="hub-btn">
                        <div class="hub-btn-icon"><i class="fas fa-plus-circle"></i></div>
                        <div class="hub-btn-text"><strong>Nuevo Préstamo</strong><small>Registrar préstamo temporal</small></div>
                    </div>
                </a>
            </div>
            <div class="col-md-4 mb-3">
                <a href="{{ route('tech.loans.index', ['filter'=>'activo']) }}" class="text-decoration-none">
                    <div class="hub-btn">
                        <div class="hub-btn-icon"><i class="fas fa-clipboard-list"></i></div>
                        <div class="hub-btn-text"><strong>Préstamos Activos</strong><small>Ver préstamos vigentes</small></div>
                    </div>
                </a>
            </div>
            <div class="col-md-4 mb-3">
                <a href="{{ route('tech.loans.index', ['filter'=>'all']) }}" class="text-decoration-none">
                    <div class="hub-btn">
                        <div class="hub-btn-icon"><i class="fas fa-history"></i></div>
                        <div class="hub-btn-text"><strong>Historial de Préstamos</strong><small>Todos los préstamos</small></div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <p class="hub-section-title"><i class="fas fa-file-csv mr-1"></i> Reportes</p>
        <div class="row">
            <div class="col-md-4 mb-3">
                <a href="{{ route('tech.loans.export') }}" class="text-decoration-none">
                    <div class="hub-btn">
                        <div class="hub-btn-icon"><i class="fas fa-file-csv"></i></div>
                        <div class="hub-btn-text"><strong>Exportar Excel</strong><small>Descargar todos los préstamos</small></div>
                    </div>
                </a>
            </div>
            <div class="col-md-4 mb-3">
                <a href="{{ route('tech.loans.export', ['filter'=>'vencido']) }}" class="text-decoration-none">
                    <div class="hub-btn">
                        <div class="hub-btn-icon" style="color:#dc3545;"><i class="fas fa-exclamation-triangle"></i></div>
                        <div class="hub-btn-text"><strong>Préstamos Vencidos</strong><small>Exportar vencidos</small></div>
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
