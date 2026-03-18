@extends('adminlte::page')

@section('title', 'Activos TI')

@section('content_header')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('tech.assets.hub') }}">Activos TI</a></li>
            <li class="breadcrumb-item active">Listado</li>
        </ol>
    </nav>
@stop

@section('content')

@include('partials._alerts')

{{-- Búsqueda --}}
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('tech.assets.index') }}" class="row align-items-end">
            <div class="col-md-4 mb-2 mb-md-0">
                <label class="small font-weight-bold text-muted mb-1">Buscar</label>
                <input type="text" name="q" class="form-control form-control-sm"
                       value="{{ request('q') }}"
                       placeholder="Serial, marca, modelo o código...">
            </div>
            <div class="col-md-3 mb-2 mb-md-0">
                <label class="small font-weight-bold text-muted mb-1">Estado</label>
                <select name="status" class="form-control form-control-sm">
                    <option value="">Todos los estados</option>
                    @foreach($statuses as $st)
                        <option value="{{ $st->name }}" {{ request('status') === $st->name ? 'selected' : '' }}>
                            {{ $st->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 mb-2 mb-md-0">
                <label class="small font-weight-bold text-muted mb-1">Sucursal</label>
                <select name="branch" class="form-control form-control-sm">
                    <option value="">Todas las sucursales</option>
                    @foreach($branches ?? [] as $branch)
                        <option value="{{ $branch->id }}" {{ request('branch') == $branch->id ? 'selected' : '' }}>
                            {{ $branch->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-sm btn-primary w-100">
                    <i class="fas fa-search mr-1"></i> Buscar
                </button>
                @if(request()->hasAny(['q','status','branch']))
                    <a href="{{ route('tech.assets.index') }}" class="btn btn-sm btn-link w-100 p-0 mt-1 text-muted small">
                        <i class="fas fa-times mr-1"></i> Limpiar filtros
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

{{-- Tabla --}}
<div class="card shadow-sm">
    <div class="card-header d-flex align-items-center justify-content-between py-2">
        <h6 class="mb-0 font-weight-bold">
            <i class="fas fa-laptop mr-1"></i> Activos TI
            <span class="badge badge-secondary ml-1">{{ $assets->count() }}</span>
        </h6>
        <a href="{{ route('tech.assets.hub') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Volver
        </a>
    </div>

    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover table-sm mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>Código</th>
                        <th>Tipo</th>
                        <th>Marca / Modelo</th>
                        <th>Serial</th>
                        <th>Propiedad</th>
                        <th>Sucursal</th>
                        <th>Estado</th>
                        <th class="text-center" style="width:80px">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($assets as $asset)
                        <tr>
                            <td>
                                <a href="{{ route('tech.assets.show', $asset) }}" class="font-weight-bold text-primary">
                                    {{ $asset->internal_code }}
                                </a>
                            </td>
                            <td>{{ $asset->type->name ?? '-' }}</td>
                            <td>
                                {{ $asset->brand }}<br>
                                <small class="text-muted">{{ $asset->model }}</small>
                            </td>
                            <td><small>{{ $asset->serial }}</small></td>
                            <td>
                                <span class="badge badge-light border">{{ $asset->property_type }}</span>
                            </td>
                            <td>{{ $asset->branch->name ?? '-' }}</td>
                            <td>
                                @php
                                    $statusClass = match($asset->status?->name ?? '') {
                                        'Disponible'    => 'success',
                                        'Asignado'      => 'primary',
                                        'En Bodega'     => 'secondary',
                                        'Préstamo'      => 'warning',
                                        'Baja'          => 'danger',
                                        'En Garantía'   => 'info',
                                        'Mantenimiento' => 'warning',
                                        'En Traslado'   => 'info',
                                        'Donado'        => 'dark',
                                        'Vendido'       => 'dark',
                                        default         => 'light',
                                    };
                                @endphp
                                <span class="badge badge-{{ $statusClass }}">
                                    {{ $asset->status?->name ?? 'N/A' }}
                                </span>
                            </td>
                            <td class="text-center">
                                <a href="{{ route('tech.assets.show', $asset) }}"
                                   class="btn btn-xs btn-outline-secondary" title="Ver detalle">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @can('tech.assets.edit')
                                    <a href="{{ route('tech.assets.edit', $asset) }}"
                                       class="btn btn-xs btn-outline-primary" title="Editar">
                                        <i class="fas fa-pen"></i>
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-muted py-4">
                                <i class="fas fa-inbox fa-2x d-block mb-2 opacity-50"></i>
                                No hay activos TI registrados
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@stop

@section('js')
<script>
$(function(){ $('[data-toggle="tooltip"]').tooltip(); });
</script>
@stop
