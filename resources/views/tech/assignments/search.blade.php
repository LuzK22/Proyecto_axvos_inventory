@extends('adminlte::page')

@section('title', 'Buscar colaborador TI')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="m-0">
                <i class="fas fa-search text-primary mr-2"></i>
                Buscar colaborador
            </h1>
            <small class="text-muted">Solo colaboradores con activos TI asignados actualmente</small>
        </div>
        <a href="{{ route('tech.assignments.hub') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Volver
        </a>
    </div>
@stop

@section('content')
@include('partials._alerts')

{{-- Barra de búsqueda --}}
<div class="card shadow-sm mb-3">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('tech.assignments.search') }}" id="searchForm">
            <div class="input-group">
                <div class="input-group-prepend">
                    <span class="input-group-text bg-white border-right-0">
                        <i class="fas fa-search text-muted"></i>
                    </span>
                </div>
                <input type="text"
                       name="q"
                       id="searchInput"
                       class="form-control border-left-0"
                       placeholder="Buscar por nombre o cédula..."
                       value="{{ $q }}"
                       autocomplete="off">
                @if($q !== '')
                    <div class="input-group-append">
                        <a href="{{ route('tech.assignments.search') }}" class="btn btn-outline-secondary" title="Limpiar">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                @endif
            </div>
        </form>
    </div>
</div>

@php
    // Solo mostrar colaboradores que tengan activos TI activos (directo o por área)
    $withAssets = $results->filter(fn($r) => ($r['ti_direct_count'] + $r['ti_area_count']) > 0)->values();
@endphp

@if($withAssets->isEmpty())
    <div class="card shadow-sm">
        <div class="card-body text-center py-5 text-muted">
            <i class="fas fa-user-slash fa-3x mb-3 d-block" style="opacity:.2;"></i>
            @if($q !== '')
                <p class="mb-1">
                    No hay colaboradores con activos TI que coincidan con
                    <strong>"{{ $q }}"</strong>.
                </p>
                <a href="{{ route('tech.assignments.search') }}" class="btn btn-sm btn-outline-secondary mt-2">
                    <i class="fas fa-times mr-1"></i> Limpiar búsqueda
                </a>
            @else
                <p class="mb-0">No hay colaboradores con activos TI asignados actualmente.</p>
            @endif
        </div>
    </div>
@else
    <div class="card shadow-sm">
        <div class="card-header py-2 d-flex align-items-center justify-content-between"
             style="border-left:4px solid #1d4ed8;">
            <h6 class="mb-0 font-weight-bold">
                <i class="fas fa-users mr-1 text-primary"></i>
                {{ $withAssets->count() }} colaborador{{ $withAssets->count() !== 1 ? 'es' : '' }} con activos TI activos
                @if($q !== '')
                    <span class="badge badge-light ml-1" style="font-size:.72rem;">
                        Filtrado: "{{ $q }}"
                    </span>
                @endif
            </h6>
            @can('tech.assets.assign')
                <a href="{{ route('tech.assignments.create') }}" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-plus mr-1"></i> Nueva asignación
                </a>
            @endcan
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover table-sm mb-0">
                    <thead class="thead-light" style="font-size:.75rem;text-transform:uppercase;letter-spacing:.03em;">
                        <tr>
                            <th class="pl-3">Colaborador</th>
                            <th>Sucursal</th>
                            <th>Modalidad</th>
                            <th class="text-center">Activos TI</th>
                            <th class="text-center">Por área</th>
                            <th class="text-center">Préstamos</th>
                            <th>Últimos códigos</th>
                            <th class="text-center" style="width:110px;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach($withAssets as $row)
                        @php
                            $mod = $row['modality'] ?? 'presencial';
                            $modClass = match($mod) {
                                'remoto'  => 'badge-info',
                                'hibrido' => 'badge-warning text-dark',
                                default   => 'badge-success',
                            };
                            $modLabel = match($mod) {
                                'remoto'  => 'Remoto',
                                'hibrido' => 'Híbrido',
                                default   => 'Presencial',
                            };
                            $totalTi = $row['ti_direct_count'] + $row['ti_area_count'];
                        @endphp
                        <tr>
                            {{-- Nombre + cédula + cargo --}}
                            <td class="pl-3 align-middle py-2">
                                <div class="d-flex align-items-center">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center
                                                text-white mr-2 flex-shrink-0"
                                         style="width:34px;height:34px;font-size:.8rem;
                                                background:linear-gradient(135deg,#1d4ed8,#0ea5e9);">
                                        {{ strtoupper(substr($row['full_name'], 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="font-weight-bold" style="font-size:.9rem;">
                                            {{ $row['full_name'] }}
                                        </div>
                                        <small class="text-muted">CC {{ $row['document'] }}</small>
                                        @if($row['position'])
                                            · <small class="text-muted">{{ $row['position'] }}</small>
                                        @endif
                                    </div>
                                </div>
                            </td>

                            {{-- Sucursal --}}
                            <td class="align-middle">
                                <small>{{ $row['branch'] ?? '—' }}</small>
                            </td>

                            {{-- Modalidad --}}
                            <td class="align-middle">
                                <span class="badge {{ $modClass }}" style="font-size:.72rem;">
                                    {{ $modLabel }}
                                </span>
                            </td>

                            {{-- Activos TI directos --}}
                            <td class="align-middle text-center">
                                @if($row['ti_direct_count'] > 0)
                                    <span class="badge badge-primary" style="font-size:.78rem;">
                                        {{ $row['ti_direct_count'] }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            {{-- Activos TI por área --}}
                            <td class="align-middle text-center">
                                @if($row['ti_area_count'] > 0)
                                    <span class="badge badge-info" style="font-size:.78rem;">
                                        {{ $row['ti_area_count'] }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            {{-- Préstamos TI --}}
                            <td class="align-middle text-center">
                                @if($row['ti_loans_count'] > 0)
                                    <span class="badge badge-warning text-dark" style="font-size:.78rem;">
                                        {{ $row['ti_loans_count'] }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>

                            {{-- Muestra de códigos --}}
                            <td class="align-middle" style="max-width:220px;">
                                <small class="text-muted" style="font-family:monospace;font-size:.75rem;">
                                    {{ $row['ti_codes']->take(4)->implode(', ') ?: '—' }}
                                    @if($row['ti_codes']->count() > 4)
                                        <span class="text-primary">+{{ $row['ti_codes']->count() - 4 }}</span>
                                    @endif
                                </small>
                            </td>

                            {{-- Acciones --}}
                            <td class="align-middle text-center" style="white-space:nowrap;">
                                <a href="{{ route('tech.expediente.show', $row['id']) }}"
                                   class="btn btn-sm btn-primary"
                                   title="Ver activos TI del colaborador">
                                    <i class="fas fa-eye mr-1"></i> Ver
                                </a>
                                @can('tech.assets.assign')
                                    <a href="{{ route('tech.assignments.create', ['collaborator_id' => $row['id']]) }}"
                                       class="btn btn-sm btn-outline-success ml-1"
                                       title="Nueva asignación TI">
                                        <i class="fas fa-plus"></i>
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endif

@stop

@section('js')
<script>
(function () {
    let _t;
    document.getElementById('searchInput')?.addEventListener('input', function () {
        clearTimeout(_t);
        _t = setTimeout(() => document.getElementById('searchForm').submit(), 350);
    });
})();
</script>
@stop
