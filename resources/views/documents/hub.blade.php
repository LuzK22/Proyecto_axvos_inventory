@extends('adminlte::page')

@section('title', 'Documentación')

@section('content_header')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item active">Documentación</li>
        </ol>
    </nav>
@stop

@section('content')

<div class="card card-outline" style="border-top:none;border-right:none;border-bottom:none;border-left:4px solid #0f766e;">
    <div class="card-body pb-2">
        <div class="d-flex align-items-center">
            <div class="rounded d-flex align-items-center justify-content-center mr-3"
                 style="width:52px;height:52px;background:#0f766e;flex-shrink:0;">
                <i class="fas fa-file-signature fa-lg text-white"></i>
            </div>
            <div>
                <h4 class="mb-0 font-weight-bold">Documentación</h4>
                <p class="text-muted mb-0 small">Actas digitales, firmas y documentos del inventario</p>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <p class="hub-section-title"><i class="fas fa-file-alt mr-1"></i> Actas y Documentos</p>
        <div class="row">

            <div class="col-md-4 mb-3">
                <a href="{{ route('actas.index') }}" class="hub-btn" style="background:linear-gradient(135deg,#0f766e,#0d9488);">
                    <div class="hub-btn-icon"><i class="fas fa-file-contract"></i></div>
                    <div class="hub-btn-text"><strong>Actas Digitales</strong><small>Gestionar todas las actas</small></div>
                </a>
            </div>

            <div class="col-md-4 mb-3">
                <a href="{{ route('actas.index', ['filter' => 'pending']) }}" class="hub-btn" style="background:linear-gradient(135deg,#3730a3,#4338ca);">
                    <div class="hub-btn-icon"><i class="fas fa-pen-nib"></i></div>
                    <div class="hub-btn-text"><strong>Firmas Pendientes</strong><small>Actas por firmar</small></div>
                </a>
            </div>

            <div class="col-md-4 mb-3">
                <a href="{{ route('actas.index', ['filter' => 'signed']) }}" class="hub-btn" style="background:linear-gradient(135deg,#334155,#475569);">
                    <div class="hub-btn-icon"><i class="fas fa-check-double"></i></div>
                    <div class="hub-btn-text"><strong>Actas Firmadas</strong><small>Archivo de actas completadas</small></div>
                </a>
            </div>

        </div>
    </div>
</div>

@stop

@section('css')
@include('partials.hub-css')
@stop
