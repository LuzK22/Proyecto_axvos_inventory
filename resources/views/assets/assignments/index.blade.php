@extends('adminlte::page')
@section('title', 'Asignaciones - Otros Activos')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <div>
        <h1 class="m-0 text-dark">
            <i class="fas fa-boxes mr-2" style="color:#7c3aed;"></i> Asignaciones - Otros Activos
        </h1>
        <small class="text-muted">
            @if($view === 'grouped')
                {{ $groupedRows->count() }} grupo(s)
            @else
                {{ $assignments->total() }} asignacion(es)
            @endif
        </small>
    </div>
    @can('assets.assign')
        <a href="{{ route('assets.assignments.create') }}" class="btn btn-sm btn-primary">
            <i class="fas fa-plus mr-1"></i> Nueva Asignacion
        </a>
    @endcan
</div>
@stop

@section('content')
@include('partials._alerts')

<div class="card card-outline card-primary mb-3">
    <div class="card-body py-2 px-3">
        <form id="filterForm" method="GET" class="row align-items-center" style="gap:.25rem 0;">
            <div class="col-md-3 pr-1">
                <select name="view" class="form-control form-control-sm auto-submit">
                    <option value="grouped" {{ $view === 'grouped' ? 'selected' : '' }}>Vista agrupada</option>
                    <option value="detail" {{ $view === 'detail' ? 'selected' : '' }}>Vista detalle</option>
                </select>
            </div>
            <div class="col-md-3 px-1">
                <select name="group_by" class="form-control form-control-sm auto-submit" {{ $view !== 'grouped' ? 'disabled' : '' }}>
                    <option value="area" {{ $groupBy === 'area' ? 'selected' : '' }}>Agrupar por Area/Pool</option>
                    <option value="collaborator" {{ $groupBy === 'collaborator' ? 'selected' : '' }}>Agrupar por Colaborador</option>
                    <option value="jefe" {{ $groupBy === 'jefe' ? 'selected' : '' }}>Agrupar por Jefe</option>
                </select>
            </div>
            <div class="col-md-3 px-1">
                <select name="status" class="form-control form-control-sm auto-submit">
                    <option value="">Todos los estados</option>
                    <option value="activa" {{ request('status') === 'activa' ? 'selected' : '' }}>Activa</option>
                    <option value="devuelta" {{ request('status') === 'devuelta' ? 'selected' : '' }}>Devuelta</option>
                </select>
            </div>
            <div class="col-md-3 pl-1">
                <select name="branch_id" class="form-control form-control-sm auto-submit">
                    <option value="">Todas las sucursales</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>{{ $b->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-12 mt-2">
                <div class="input-group input-group-sm">
                    <div class="input-group-prepend">
                        <span class="input-group-text bg-white border-right-0"><i class="fas fa-search text-muted"></i></span>
                    </div>
                    <input type="text" name="search" id="searchInput" class="form-control border-left-0"
                           placeholder="Buscar por colaborador o area..." value="{{ request('search') }}">
                </div>
            </div>
        </form>
    </div>
</div>

<div class="card card-outline card-primary">
    <div class="card-body p-0">
        @if($view === 'grouped')
            @if($groupedRows->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-layer-group fa-2x mb-2 d-block" style="opacity:.3;"></i>
                    Sin datos para la agrupacion seleccionada.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-sm table-hover mb-0">
                        <thead class="thead-light" style="font-size:.75rem;text-transform:uppercase;">
                        <tr>
                            <th class="pl-3">Grupo</th>
                            <th>Tipo</th>
                            <th>Sucursal</th>
                            <th>Asignaciones</th>
                            <th>Activos activos</th>
                            <th>Codigos (muestra)</th>
                            <th>Ultima</th>
                            <th class="text-center">Accion</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($groupedRows as $row)
                            <tr>
                                <td class="pl-3"><strong>{{ $row['name'] }}</strong></td>
                                <td><span class="badge badge-light border">{{ $row['destination_label'] }}</span></td>
                                <td>{{ $row['branch'] }}</td>
                                <td><span class="badge badge-primary">{{ $row['assignments_count'] }}</span></td>
                                <td><span class="badge badge-success">{{ $row['assets_count'] }}</span></td>
                                <td>
                                    <small class="text-muted">{{ $row['sample_codes']->implode(', ') ?: '-' }}</small>
                                </td>
                                <td>
                                    <small>{{ optional($row['latest_assignment']->assignment_date)->format('d/m/Y') ?? '-' }}</small><br>
                                    <small class="text-muted">#{{ $row['latest_assignment']->id }}</small>
                                </td>
                                <td class="text-center">
                                    <a href="{{ route('assets.assignments.show', $row['latest_assignment']) }}" class="btn btn-xs btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @else
            @if($assignments->isEmpty())
                <div class="text-center py-5 text-muted">
                    <i class="fas fa-boxes fa-3x mb-3 d-block" style="opacity:.3;"></i>
                    No se encontraron asignaciones.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="thead-light" style="font-size:.78rem;text-transform:uppercase;">
                        <tr>
                            <th class="pl-3">Destinatario</th>
                            <th>Tipo destino</th>
                            <th>Activos</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th class="text-center" style="width:90px;">Acciones</th>
                        </tr>
                        </thead>
                        <tbody>
                        @foreach($assignments as $a)
                            <tr>
                                <td class="align-middle pl-3 py-2">
                                    @if($a->destination_type === 'collaborator' || $a->destination_type === 'jefe')
                                        <i class="fas {{ $a->destination_type === 'jefe' ? 'fa-user-tie' : 'fa-user' }} mr-1 text-primary"></i>
                                        <a href="{{ route('assets.assignments.show', $a) }}" class="font-weight-bold text-dark">
                                            {{ $a->collaborator->full_name ?? '-' }}
                                        </a>
                                        <br><small class="text-muted">{{ $a->collaborator->branch?->name ?? '-' }}</small>
                                    @else
                                        <i class="fas fa-map-marker-alt mr-1" style="color:#7c3aed;"></i>
                                        <a href="{{ route('assets.assignments.show', $a) }}" class="font-weight-bold text-dark">
                                            {{ $a->area->name ?? '-' }}
                                        </a>
                                        <br><small class="text-muted">{{ $a->area?->branch?->name ?? '-' }}</small>
                                    @endif
                                </td>
                                <td class="align-middle">
                                    <span class="badge badge-light border">{{ \App\Models\Assignment::destinationLabel($a->destination_type ?? 'collaborator') }}</span>
                                </td>
                                <td class="align-middle">
                                    <span class="badge badge-secondary">{{ $a->assignmentAssets->whereNull('returned_at')->count() }} activo(s)</span>
                                </td>
                                <td class="align-middle"><small>{{ $a->assignment_date?->format('d/m/Y') }}</small></td>
                                <td class="align-middle">
                                    <span class="badge {{ $a->status === 'activa' ? 'badge-success' : 'badge-secondary' }}">{{ ucfirst($a->status) }}</span>
                                </td>
                                <td class="align-middle text-center">
                                    <a href="{{ route('assets.assignments.show', $a) }}" class="btn btn-xs btn-info">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        @endif
    </div>
    @if($view === 'detail' && $assignments->hasPages())
        <div class="card-footer py-2">{{ $assignments->links() }}</div>
    @endif
</div>
@stop

@section('js')
<script>
let _t;
document.getElementById('searchInput')?.addEventListener('input', function() {
    clearTimeout(_t);
    _t = setTimeout(() => document.getElementById('filterForm').submit(), 400);
});
document.querySelectorAll('.auto-submit').forEach(el => {
    el.addEventListener('change', () => document.getElementById('filterForm').submit());
});
</script>
@stop
