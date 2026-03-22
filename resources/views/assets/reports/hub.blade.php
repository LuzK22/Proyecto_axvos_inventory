@extends('adminlte::page')
@section('title', 'Reportes — Otros Activos')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <div>
        <h1 class="m-0 text-dark">
            <i class="fas fa-chart-bar mr-2" style="color:#7c3aed;"></i>Reportes — Otros Activos
        </h1>
        <small class="text-muted">Inventario de mobiliario, enseres y otros activos</small>
    </div>
    <a href="{{ route('assets.hub') }}" class="btn btn-sm btn-secondary">
        <i class="fas fa-arrow-left mr-1"></i> Volver
    </a>
</div>
@stop

@section('content')
@include('partials._alerts')

{{-- Stats --}}
<div class="row mb-3">
    <div class="col-6 col-md-3">
        <div class="small-box" style="background:#7c3aed;color:#fff;">
            <div class="inner"><h3>{{ $stats['total'] }}</h3><p>Total Activos</p></div>
            <div class="icon"><i class="fas fa-boxes"></i></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="small-box bg-success">
            <div class="inner"><h3>{{ $stats['asignados'] }}</h3><p>Asignados</p></div>
            <div class="icon"><i class="fas fa-user-check"></i></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="small-box bg-info">
            <div class="inner"><h3>{{ $stats['disponibles'] }}</h3><p>Disponibles</p></div>
            <div class="icon"><i class="fas fa-box-open"></i></div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="small-box bg-danger">
            <div class="inner"><h3>{{ $stats['baja'] }}</h3><p>Dados de Baja</p></div>
            <div class="icon"><i class="fas fa-ban"></i></div>
        </div>
    </div>
</div>

{{-- Filtros --}}
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" class="form-inline flex-wrap" style="gap:8px;">
            <input type="text" name="q" value="{{ request('q') }}"
                   class="form-control form-control-sm" placeholder="Buscar código, nombre, marca..."
                   style="min-width:200px;">
            @if($subcategories->count())
            <select name="subcategory" class="form-control form-control-sm">
                <option value="">Todas las subcategorías</option>
                @foreach($subcategories as $sub)
                    <option value="{{ $sub }}" {{ request('subcategory') === $sub ? 'selected' : '' }}>{{ $sub }}</option>
                @endforeach
            </select>
            @endif
            <select name="type_id" class="form-control form-control-sm">
                <option value="">Todos los tipos</option>
                @foreach($types as $t)
                    <option value="{{ $t->id }}" {{ request('type_id') == $t->id ? 'selected' : '' }}>{{ $t->name }}</option>
                @endforeach
            </select>
            <select name="status_id" class="form-control form-control-sm">
                <option value="">Todos los estados</option>
                @foreach($statuses as $s)
                    <option value="{{ $s->id }}" {{ request('status_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                @endforeach
            </select>
            <select name="branch_id" class="form-control form-control-sm">
                <option value="">Todas las sucursales</option>
                @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-sm btn-primary">
                <i class="fas fa-filter mr-1"></i> Filtrar
            </button>
            @if(request()->hasAny(['q','type_id','status_id','branch_id','subcategory']))
                <a href="{{ route('assets.reports.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-times mr-1"></i> Limpiar
                </a>
            @endif
        </form>
    </div>
</div>

{{-- Quick export links --}}
<div class="mb-3 d-flex" style="gap:8px;">
    <a href="{{ route('assets.reports.export', request()->all()) }}" class="btn btn-sm btn-outline-success">
        <i class="fas fa-file-csv mr-1"></i> Exportar completo
    </a>
    <a href="{{ route('reports.collaborators.export') }}" class="btn btn-sm btn-outline-info">
        <i class="fas fa-users mr-1"></i> Reporte colaboradores con activos
    </a>
</div>

{{-- Tabla --}}
<div class="card shadow-sm">
    <div class="card-header py-2 d-flex justify-content-between align-items-center">
        <span class="font-weight-bold" style="font-size:.9rem;">
            <i class="fas fa-table mr-1 text-muted"></i>
            {{ $assets->total() }} activo(s) encontrado(s)
        </span>
        <a href="{{ route('assets.reports.export', request()->all()) }}" class="btn btn-sm btn-success">
            <i class="fas fa-file-csv mr-1"></i> Exportar Excel
        </a>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0">
                <thead class="thead-light" style="font-size:.75rem;text-transform:uppercase;">
                    <tr>
                        <th class="pl-3">Código</th>
                        <th>Tipo</th>
                        <th>Subcategoría</th>
                        <th>Nombre / Marca</th>
                        <th>Estado</th>
                        <th>Sucursal</th>
                        <th>Propiedad</th>
                        <th>Ingreso</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($assets as $asset)
                    <tr>
                        <td class="pl-3"><code style="font-size:.78rem;">{{ $asset->internal_code }}</code></td>
                        <td><small>{{ $asset->type?->name ?? '—' }}</small></td>
                        <td>
                            @if($asset->type?->subcategory)
                                <span class="badge badge-light border text-muted" style="font-size:.65rem;">{{ $asset->type->subcategory }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td><small>{{ $asset->brand }} {{ $asset->model }}</small></td>
                        <td>
                            @if($asset->status)
                                <span class="badge badge-pill" style="background:{{ $asset->status->color ?? '#6c757d' }};color:#fff;font-size:.68rem;">
                                    {{ $asset->status->name }}
                                </span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td><small>{{ $asset->branch?->name ?? '—' }}</small></td>
                        <td>
                            <span class="badge badge-{{ $asset->property_type === 'PROPIO' ? 'success' : 'info' }}" style="font-size:.65rem;">
                                {{ $asset->property_type }}
                            </span>
                        </td>
                        <td><small class="text-muted">{{ $asset->created_at?->format('d/m/Y') }}</small></td>
                        <td>
                            <a href="{{ route('assets.show', $asset) }}" class="btn btn-xs btn-outline-secondary">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted py-4">
                            <i class="fas fa-inbox fa-2x d-block mb-2" style="opacity:.2;"></i>
                            No se encontraron activos con los filtros aplicados.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if($assets->hasPages())
        <div class="card-footer">{{ $assets->links() }}</div>
    @endif
</div>
@stop
