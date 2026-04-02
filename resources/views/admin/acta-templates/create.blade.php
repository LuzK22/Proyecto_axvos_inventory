@extends('adminlte::page')

@section('title', 'Subir Plantilla de Acta')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0">
            <i class="fas fa-upload text-success mr-2"></i> Subir Plantilla
        </h1>
        <a href="{{ route('admin.acta-templates.category', ['category' => strtolower($selectedCategory ?? 'ti')]) }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Volver
        </a>
    </div>
@stop

@section('content')

@include('partials._alerts')

<div class="card shadow-sm mb-3" style="border-left:4px solid #0369a1;">
    <div class="card-header py-2 d-flex align-items-center justify-content-between">
        <h6 class="mb-0 font-weight-bold" style="font-size:.85rem;">
            <i class="fas fa-tags mr-1" style="color:#0369a1;"></i>
            Guia de marcadores
            <small class="text-muted font-weight-normal ml-2">escribe los marcadores en tu plantilla donde quieras el dato</small>
        </h6>
        <button class="btn btn-xs btn-outline-secondary" type="button" data-toggle="collapse" data-target="#cheatBody">
            <i class="fas fa-chevron-down"></i>
        </button>
    </div>
    <div id="cheatBody" class="collapse show">
        <div class="card-body py-2">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-1" style="font-size:.75rem;text-transform:uppercase;color:#0369a1;font-weight:600;">
                        <i class="fas fa-user mr-1"></i> Cabecera
                    </p>
                    <table class="table table-sm table-borderless mb-2" style="font-size:.8rem;">
                        <tr><td><code>@{{delivery_date}}</code></td><td class="text-muted">Fecha del acta</td></tr>
                        <tr><td><code>@{{collaborator_name}}</code></td><td class="text-muted">Nombre colaborador</td></tr>
                        <tr><td><code>@{{collaborator_document}}</code></td><td class="text-muted">Documento</td></tr>
                        <tr><td><code>@{{collaborator_position}}</code></td><td class="text-muted">Cargo</td></tr>
                        <tr><td><code>@{{collaborator_email}}</code></td><td class="text-muted">Correo</td></tr>
                        <tr><td><code>@{{user_domain}}</code></td><td class="text-muted">Usuario - Dominio</td></tr>
                        <tr><td><code>@{{area_name}}</code></td><td class="text-muted">Area</td></tr>
                        <tr><td><code>@{{branch_name}}</code></td><td class="text-muted">Sucursal</td></tr>
                        <tr><td><code>@{{city_name}}</code></td><td class="text-muted">Ciudad</td></tr>
                        <tr><td><code>@{{responsible_name}}</code></td><td class="text-muted">Responsable TI</td></tr>
                    </table>
                </div>
                <div class="col-md-6">
                    <p class="mb-1" style="font-size:.75rem;text-transform:uppercase;color:#059669;font-weight:600;">
                        <i class="fas fa-laptop mr-1"></i> Tabla de activos
                    </p>
                    <div class="alert alert-light border mb-2 py-1 px-2" style="font-size:.78rem;">
                        Usa una fila base con marcadores. AXVOS la repite por cada activo.
                    </div>
                    <table class="table table-sm table-borderless mb-0" style="font-size:.8rem;">
                        <tr><td><code>@{{asset_type}}</code></td><td class="text-muted">Descripcion / Tipo</td></tr>
                        <tr><td><code>@{{asset_brand_model}}</code></td><td class="text-muted">Marca y Modelo</td></tr>
                        <tr><td><code>@{{asset_serial}}</code></td><td class="text-muted">Serial</td></tr>
                        <tr><td><code>@{{asset_hostname}}</code></td><td class="text-muted">Nombre del equipo</td></tr>
                        <tr><td><code>@{{fixed_asset_code}}</code></td><td class="text-muted">Activo Fijo</td></tr>
                        <tr><td><code>@{{asset_status}}</code></td><td class="text-muted">Estado</td></tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-body">
        <form method="POST" action="{{ route('admin.acta-templates.store') }}" enctype="multipart/form-data">
            @csrf

            <div class="form-group">
                <label class="font-weight-bold">Nombre <span class="text-danger">*</span></label>
                <input type="text" name="name" class="form-control" value="{{ old('name') }}" required
                       placeholder="Ej: Acta Entrega TI v1">
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="form-group">
                        <label class="font-weight-bold">Tipo de acta <span class="text-danger">*</span></label>
                        <select name="acta_type" class="form-control" required>
                            @php($type = old('acta_type','entrega'))
                            <option value="entrega" {{ $type==='entrega' ? 'selected' : '' }}>Entrega</option>
                            <option value="devolucion" {{ $type==='devolucion' ? 'selected' : '' }}>Devolucion</option>
                            <option value="prestamo" {{ $type==='prestamo' ? 'selected' : '' }}>Prestamo</option>
                            <option value="baja" {{ $type==='baja' ? 'selected' : '' }}>Baja</option>
                            <option value="donacion" {{ $type==='donacion' ? 'selected' : '' }}>Donacion</option>
                            <option value="venta" {{ $type==='venta' ? 'selected' : '' }}>Venta</option>
                            <option value="actualizacion" {{ $type==='actualizacion' ? 'selected' : '' }}>Actualizacion</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="font-weight-bold">Categoria <span class="text-danger">*</span></label>
                        @php($cat = old('asset_category', $selectedCategory ?? 'TI'))
                        <select name="asset_category" class="form-control" required>
                            <option value="TI" {{ $cat==='TI' ? 'selected' : '' }}>TI</option>
                            <option value="OTRO" {{ $cat==='OTRO' ? 'selected' : '' }}>OTRO</option>
                            <option value="ALL" {{ $cat==='ALL' ? 'selected' : '' }}>ALL (Mixta)</option>
                        </select>
                    </div>
                </div>

                <div class="col-md-4">
                    <div class="form-group">
                        <label class="font-weight-bold">Archivo de plantilla <span class="text-danger">*</span></label>
                        <input type="file" name="template_file" class="form-control" accept=".xlsx,.docx" required>
                        <small class="text-muted">Soportado: .xlsx y .docx (max 20 MB)</small>
                    </div>
                </div>
            </div>

            <button class="btn btn-success">
                <i class="fas fa-upload mr-1"></i> Subir plantilla
            </button>
        </form>
    </div>
</div>

@stop

