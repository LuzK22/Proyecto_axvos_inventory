@extends('adminlte::page')

@section('title', 'Tipos de Activo ' . ($category === 'TI' ? 'TI' : 'General'))

@section('content_header')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            @if($category === 'TI')
                <li class="breadcrumb-item"><a href="{{ route('tech.assets.hub') }}">Activos TI</a></li>
            @else
                <li class="breadcrumb-item"><a href="{{ route('assets.hub') }}">Otros Activos</a></li>
            @endif
            <li class="breadcrumb-item active">Tipos de Activo</li>
        </ol>
    </nav>
@stop

@section('content')

@include('partials._alerts')

<div class="card shadow-sm">
    <div class="card-header py-2 d-flex align-items-center justify-content-between"
         style="border-left:4px solid #334155;">
        <h6 class="mb-0 font-weight-bold">
            <i class="fas fa-tags mr-1" style="color:#334155;"></i>
            Tipos de Activo
            <span class="badge badge-secondary ml-1" style="font-size:.7rem;">{{ $category }}</span>
            <span class="badge badge-light ml-1" style="font-size:.7rem;">{{ $types->count() }}</span>
        </h6>
        <div>
            @if($category === 'TI')
                @can('tech.types.create')
                    <a href="{{ route('tech.types.create') }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus mr-1"></i> Nuevo Tipo
                    </a>
                @endcan
                <a href="{{ route('tech.assets.hub') }}" class="btn btn-sm btn-outline-secondary ml-1">
                    <i class="fas fa-arrow-left mr-1"></i> Volver
                </a>
            @else
                @can('asset-types.create')
                    <a href="{{ route('asset-types.create', 'OTRO') }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus mr-1"></i> Nuevo Tipo
                    </a>
                @endcan
                <a href="{{ route('assets.hub') }}" class="btn btn-sm btn-outline-secondary ml-1">
                    <i class="fas fa-arrow-left mr-1"></i> Volver
                </a>
            @endif
        </div>
    </div>

    <div class="card-body p-0">
        @if($types->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="fas fa-tags fa-3x mb-3 d-block" style="opacity:.2;"></i>
                <p class="mb-0">No hay tipos registrados para <strong>{{ $category }}</strong></p>
            </div>
        @else
            <table class="table table-sm table-hover mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Nombre</th>
                        <th style="width:160px;">Subcategoría</th>
                        <th style="width:130px;">Código / Prefijo</th>
                        <th style="width:140px;">Creado por</th>
                        <th style="width:90px;">Estado</th>
                        <th style="width:60px;"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($types as $type)
                    <tr>
                        <td class="font-weight-bold">{{ $type->name }}</td>
                        <td>
                            @if($type->subcategory)
                                <span class="badge badge-light border text-muted" style="font-size:.75rem;">
                                    {{ $type->subcategory }}
                                </span>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                        <td>
                            <code style="font-size:.78rem;">{{ $type->prefix ?? $type->code }}</code>
                        </td>
                        <td class="text-muted small">{{ $type->creator->name ?? '-' }}</td>
                        <td>
                            <span class="badge badge-pill {{ $type->active ? 'badge-success' : 'badge-secondary' }}">
                                {{ $type->active ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td class="text-center">
                            <a href="{{ route('asset-types.edit', $type) }}"
                               class="btn btn-xs btn-outline-primary" title="Editar">
                                <i class="fas fa-pencil-alt"></i>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

@stop

@section('css')
<style>
.card { border-radius: 10px; }
.table th { font-size: .75rem; text-transform: uppercase; letter-spacing: .04em; }
</style>
@stop
