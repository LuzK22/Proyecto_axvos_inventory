@extends('adminlte::page')

@section('title', 'Colaboradores')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="m-0 text-dark">
                <i class="fas fa-users text-primary mr-2"></i>Colaboradores
            </h1>
            <small class="text-muted">
                {{ $collaborators->total() }} resultado(s)
                @if(request()->hasAny(['search','modalidad','branch_id']))
                    &nbsp;·&nbsp;
                    <a href="{{ route('collaborators.index') }}" class="text-danger">
                        <i class="fas fa-times-circle mr-1"></i>Limpiar filtros
                    </a>
                @endif
            </small>
        </div>
        @can('collaborators.create')
            <a href="{{ route('collaborators.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-user-plus mr-1"></i> Nuevo Colaborador
            </a>
        @endcan
    </div>
@stop

@section('content')

@include('partials._alerts')

{{-- ── Barra de filtros ────────────────────────────────────── --}}
<div class="card card-outline card-primary mb-3">
    <div class="card-body py-2 px-3">
        <form id="filterForm" method="GET">

            <div class="row align-items-center" style="gap:.25rem 0;">

                {{-- Buscador principal --}}
                <div class="col-md-4 pr-1">
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text bg-white border-right-0">
                                <i class="fas fa-search text-muted" id="searchSpinner"></i>
                            </span>
                        </div>
                        <input type="text" id="searchInput" name="search"
                               class="form-control border-left-0"
                               placeholder="Nombre, cédula, correo o área..."
                               value="{{ request('search') }}"
                               autocomplete="off">
                    </div>
                </div>

                {{-- Modalidad --}}
                <div class="col-md-2 px-1">
                    <select name="modalidad" id="filterModalidad" class="form-control form-control-sm">
                        <option value="">Todas las modalidades</option>
                        <option value="presencial" {{ request('modalidad') === 'presencial' ? 'selected' : '' }}>
                            🏢 Presencial
                        </option>
                        <option value="remoto" {{ request('modalidad') === 'remoto' ? 'selected' : '' }}>
                            🏠 Remoto
                        </option>
                        <option value="hibrido" {{ request('modalidad') === 'hibrido' ? 'selected' : '' }}>
                            🔄 Híbrido
                        </option>
                    </select>
                </div>

                {{-- Sucursal --}}
                <div class="col-md-3 px-1">
                    <select name="branch_id" id="filterBranch" class="form-control form-control-sm">
                        <option value="">Todas las sucursales</option>
                        @foreach($branches as $b)
                            <option value="{{ $b->id }}" {{ request('branch_id') == $b->id ? 'selected' : '' }}>
                                {{ $b->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- Toggle Activo / Inactivo / Todos --}}
                <div class="col-md-3 pl-1">
                    <div class="btn-group btn-group-sm w-100" role="group">
                        <button type="button" name="active" value="1"
                                class="btn filter-active-btn {{ $activeFilter === '1'   ? 'btn-success'         : 'btn-outline-success' }}"
                                onclick="setActiveFilter('1')">
                            <i class="fas fa-check-circle mr-1"></i>Activos
                        </button>
                        <button type="button" name="active" value="0"
                                class="btn filter-active-btn {{ $activeFilter === '0'   ? 'btn-secondary'       : 'btn-outline-secondary' }}"
                                onclick="setActiveFilter('0')">
                            <i class="fas fa-ban mr-1"></i>Inactivos
                        </button>
                        <button type="button" name="active" value="all"
                                class="btn filter-active-btn {{ $activeFilter === 'all' ? 'btn-dark'            : 'btn-outline-dark' }}"
                                onclick="setActiveFilter('all')">
                            Todos
                        </button>
                    </div>
                    {{-- Hidden input for active filter --}}
                    <input type="hidden" name="active" id="activeInput" value="{{ $activeFilter }}">
                </div>
            </div>

        </form>
    </div>
</div>

{{-- ── Tabla de colaboradores ──────────────────────────────── --}}
<div class="card card-outline card-primary">
    <div class="card-body p-0">

        @if($collaborators->isEmpty())
            <div class="text-center py-5 text-muted" style="overflow:hidden;">
                <i class="fas fa-user-slash fa-3x mb-3 d-block" style="opacity:.3;"></i>
                <p class="mb-1 font-weight-semibold">No se encontraron colaboradores</p>
                @if(request()->hasAny(['search','modalidad','branch_id']))
                    <small>Intenta con otros filtros o</small>
                    <a href="{{ route('collaborators.index') }}" class="btn btn-sm btn-outline-primary mt-2 d-block mx-auto" style="width:fit-content;">
                        <i class="fas fa-times mr-1"></i> Limpiar filtros
                    </a>
                @endif
            </div>

        @else
            <div class="table-scroll-container">
            <table class="table table-hover mb-0" id="collabTable">
                <thead style="background:#f4f6f9; font-size:.8rem; text-transform:uppercase; letter-spacing:.5px;">
                    <tr>
                        <th class="pl-3 border-top-0" style="width:44px;"></th>
                        <th class="border-top-0">Colaborador</th>
                        <th class="border-top-0">Cédula</th>
                        <th class="border-top-0">Cargo / Área</th>
                        <th class="border-top-0">Sucursal</th>
                        <th class="border-top-0">Modalidad</th>
                        <th class="border-top-0 text-center" title="Activos asignados actualmente">
                            <i class="fas fa-laptop"></i>
                        </th>
                        <th class="border-top-0 text-center" style="width:90px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($collaborators as $c)
                        @php
                            // Avatar initial(es)
                            $words    = array_filter(explode(' ', $c->full_name));
                            $initials = strtoupper(implode('', array_map(fn($w) => $w[0], array_slice(array_values($words), 0, 2))));

                            // Color determinístico según documento
                            $palette = ['#0d6efd','#198754','#dc3545','#fd7e14','#6f42c1','#20c997','#0dcaf0','#d63384'];
                            $bgColor = $palette[abs(crc32($c->document)) % count($palette)];

                            // Modalidad badge
                            $mod      = $c->modalidad_trabajo ?? 'presencial';
                            $modLabel = match($mod) { 'remoto' => 'Remoto', 'hibrido' => 'Híbrido', default => 'Presencial' };
                            $modClass = match($mod) { 'remoto' => 'badge-info', 'hibrido' => 'badge-warning text-dark', default => 'badge-success' };
                            $modIcon  = match($mod) { 'remoto' => 'home', 'hibrido' => 'random', default => 'building' };
                        @endphp

                        <tr class="{{ !$c->active ? 'text-muted' : '' }}"
                            style="{{ !$c->active ? 'opacity:.7;' : '' }}">

                            {{-- Avatar --}}
                            <td class="pl-3 align-middle py-2">
                                <div class="rounded-circle d-flex align-items-center justify-content-center text-white font-weight-bold"
                                     style="width:36px;height:36px;font-size:.7rem;background:{{ $bgColor }};flex-shrink:0;letter-spacing:0;">
                                    {{ $initials }}
                                </div>
                            </td>

                            {{-- Nombre + email --}}
                            <td class="align-middle py-2">
                                <a href="{{ route('collaborators.show', $c) }}"
                                   class="font-weight-bold {{ $c->active ? 'text-dark' : 'text-muted' }} stretched-link-text">
                                    {{ $c->full_name }}
                                </a>
                                @if(!$c->active)
                                    <span class="badge badge-secondary ml-1" style="font-size:.6rem;">inactivo</span>
                                @endif
                                @if($c->email)
                                    <br><small class="text-muted">{{ $c->email }}</small>
                                @endif
                            </td>

                            {{-- Cédula --}}
                            <td class="align-middle py-2">
                                <small class="font-monospace">{{ $c->document }}</small>
                            </td>

                            {{-- Cargo / Área --}}
                            <td class="align-middle py-2">
                                {{ $c->position ?? '—' }}
                                @if($c->area)
                                    <br><small class="text-muted">{{ $c->area }}</small>
                                @endif
                            </td>

                            {{-- Sucursal --}}
                            <td class="align-middle py-2">
                                <small>{{ $c->branch?->name ?? '—' }}</small>
                            </td>

                            {{-- Modalidad --}}
                            <td class="align-middle py-2">
                                <span class="badge {{ $modClass }}">
                                    <i class="fas fa-{{ $modIcon }} mr-1"></i>{{ $modLabel }}
                                </span>
                            </td>

                            {{-- Activos activos --}}
                            <td class="align-middle py-2 text-center">
                                @if($c->active_assignments_count > 0)
                                    <span class="badge badge-primary px-2" title="Activos TI asignados actualmente">
                                        {{ $c->active_assignments_count }}
                                    </span>
                                @else
                                    <span class="text-muted" style="font-size:.75rem;">—</span>
                                @endif
                            </td>

                            {{-- Acciones --}}
                            <td class="align-middle py-2 text-center">
                                <a href="{{ route('collaborators.show', $c) }}"
                                   class="btn btn-xs btn-info" title="Ver expediente">
                                    <i class="fas fa-id-card"></i>
                                </a>
                                @can('collaborators.edit')
                                    <a href="{{ route('collaborators.edit', $c) }}"
                                       class="btn btn-xs btn-warning" title="Editar colaborador">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            </div>{{-- /table-scroll-container --}}
        @endif
    </div>

    @if($collaborators->hasPages())
        <div class="card-footer py-2">
            {{ $collaborators->links() }}
        </div>
    @endif
</div>

@stop

@section('js')
<script>
/* ── Live search (debounce 400 ms) ───────────────────────── */
let _searchTimer;
const searchInput = document.getElementById('searchInput');
const filterForm  = document.getElementById('filterForm');

searchInput?.addEventListener('input', function () {
    document.getElementById('searchSpinner').className = 'fas fa-spinner fa-spin text-primary';
    clearTimeout(_searchTimer);
    _searchTimer = setTimeout(() => filterForm.submit(), 400);
});

/* ── Auto-submit en selects ──────────────────────────────── */
['filterModalidad', 'filterBranch'].forEach(id => {
    document.getElementById(id)?.addEventListener('change', () => filterForm.submit());
});

/* ── Toggle activo/inactivo/todos ────────────────────────── */
function setActiveFilter(val) {
    document.getElementById('activeInput').value = val;
    filterForm.submit();
}

/* ── Auto-dismiss alerts ─────────────────────────────────── */
setTimeout(() => {
    document.querySelectorAll('.alert.show').forEach(el => el.classList.remove('show'));
}, 4000);
</script>
@stop
