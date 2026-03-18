@extends('adminlte::page')
@section('title', 'Asignaciones — Otros Activos')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <div>
        <h1 class="m-0 text-dark">
            <i class="fas fa-boxes text-purple mr-2" style="color:#7c3aed;"></i>Asignaciones — Otros Activos
        </h1>
        <small class="text-muted">{{ $assignments->total() }} resultado(s)</small>
    </div>
    @can('assets.assign')
    <a href="{{ route('assets.assignments.create') }}" class="btn btn-sm btn-primary">
        <i class="fas fa-plus mr-1"></i> Nueva Asignación
    </a>
    @endcan
</div>
@stop

@section('content')
@include('partials._alerts')

{{-- Filtros --}}
<div class="card card-outline card-primary mb-3">
    <div class="card-body py-2 px-3">
        <form id="filterForm" method="GET" class="row align-items-center" style="gap:.25rem 0;">
            <div class="col-md-4 pr-1">
                <div class="input-group input-group-sm">
                    <div class="input-group-prepend">
                        <span class="input-group-text bg-white border-right-0">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                    </div>
                    <input type="text" name="search" id="searchInput"
                           class="form-control border-left-0"
                           placeholder="Colaborador o área..."
                           value="{{ request('search') }}" autocomplete="off">
                </div>
            </div>
            <div class="col-md-3 px-1">
                <select name="status" class="form-control form-control-sm auto-submit">
                    <option value="">Todos los estados</option>
                    <option value="activa"   {{ request('status') === 'activa'   ? 'selected' : '' }}>Activa</option>
                    <option value="devuelta" {{ request('status') === 'devuelta' ? 'selected' : '' }}>Devuelta</option>
                </select>
            </div>
            <div class="col-md-3 px-1">
                <select name="branch_id" class="form-control form-control-sm auto-submit">
                    <option value="">Todas las sucursales</option>
                    @foreach($branches as $b)
                        <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>
                            {{ $b->name }}
                        </option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>
</div>

{{-- Tabla --}}
<div class="card card-outline card-primary">
    <div class="card-body p-0">
        @if($assignments->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="fas fa-boxes fa-3x mb-3 d-block" style="opacity:.3;"></i>
                <p class="mb-1">No se encontraron asignaciones</p>
            </div>
        @else
        <div class="table-scroll-container">
        <table class="table table-hover mb-0">
            <thead style="background:#f4f6f9;font-size:.78rem;text-transform:uppercase;letter-spacing:.5px;">
                <tr>
                    <th class="border-top-0 pl-3">Destinatario</th>
                    <th class="border-top-0">Activos</th>
                    <th class="border-top-0">Fecha</th>
                    <th class="border-top-0">Estado</th>
                    <th class="border-top-0 text-center" style="width:90px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($assignments as $a)
                <tr>
                    <td class="align-middle pl-3 py-2">
                        @if($a->collaborator_id)
                            <i class="fas fa-user mr-1 text-primary"></i>
                            <a href="{{ route('assets.assignments.show', $a) }}" class="font-weight-bold text-dark">
                                {{ $a->collaborator->full_name }}
                            </a>
                            <br><small class="text-muted">{{ $a->collaborator->branch?->name ?? '—' }}</small>
                        @else
                            <i class="fas fa-map-marker-alt mr-1" style="color:#7c3aed;"></i>
                            <a href="{{ route('assets.assignments.show', $a) }}" class="font-weight-bold text-dark">
                                {{ $a->area->name ?? '—' }}
                            </a>
                            <br><small class="text-muted">{{ $a->area?->branch?->name ?? '—' }}</small>
                        @endif
                    </td>
                    <td class="align-middle py-2">
                        <span class="badge badge-secondary">
                            {{ $a->assignmentAssets->whereNull('returned_at')->count() }} activo(s)
                        </span>
                    </td>
                    <td class="align-middle py-2">
                        <small>{{ $a->assignment_date?->format('d/m/Y') }}</small>
                    </td>
                    <td class="align-middle py-2">
                        <span class="badge {{ $a->status === 'activa' ? 'badge-success' : 'badge-secondary' }}">
                            {{ ucfirst($a->status) }}
                        </span>
                    </td>
                    <td class="align-middle py-2 text-center">
                        <a href="{{ route('assets.assignments.show', $a) }}"
                           class="btn btn-xs btn-info" title="Ver detalle">
                            <i class="fas fa-eye"></i>
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        </div>
        @endif
    </div>
    @if($assignments->hasPages())
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
