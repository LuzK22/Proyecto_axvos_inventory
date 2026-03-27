@extends('adminlte::page')

@section('title', 'Buscar Colaboradores - Asignaciones TI')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <div>
        <h1 class="m-0"><i class="fas fa-search text-primary mr-2"></i>Buscar Colaboradores</h1>
        <small class="text-muted">Modulo de apoyo para asignaciones TI</small>
    </div>
    <a href="{{ route('tech.assignments.hub') }}" class="btn btn-secondary btn-sm">
        <i class="fas fa-arrow-left mr-1"></i>Volver
    </a>
</div>
@stop

@section('content')
<div class="card card-outline card-primary mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('tech.assignments.search') }}" class="form-inline" style="gap:8px;">
            <input type="text" name="q" value="{{ $q }}" class="form-control form-control-sm"
                   placeholder="Buscar por nombre del colaborador..." style="min-width:320px;">
            <button type="submit" class="btn btn-sm btn-primary">
                <i class="fas fa-search mr-1"></i>Buscar
            </button>
            @if($q !== '')
            <a href="{{ route('tech.assignments.search') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-times mr-1"></i>Limpiar
            </a>
            @endif
        </form>
    </div>
</div>

<div class="card card-outline card-secondary">
    <div class="card-header py-2">
        <strong>Resultados</strong>
        <span class="text-muted ml-2">({{ $results->count() }})</span>
    </div>
    <div class="card-body p-0">
        @if($results->isEmpty())
            <div class="text-center py-4 text-muted">
                <i class="fas fa-user-slash fa-2x mb-2 d-block" style="opacity:.2;"></i>
                No se encontraron colaboradores.
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="thead-light" style="font-size:.75rem;text-transform:uppercase;">
                    <tr>
                        <th>Colaborador</th>
                        <th>Documento</th>
                        <th>Cargo</th>
                        <th>Area</th>
                        <th>Sucursal</th>
                        <th>Modalidad</th>
                        <th>Activos TI asignados</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($results as $row)
                        @php
                            $assignedAssets = $assetsByCollaborator->get($row['id'], collect());
                        @endphp
                        <tr>
                            <td>{{ $row['full_name'] }}</td>
                            <td>{{ $row['document'] }}</td>
                            <td>{{ $row['position'] ?: '-' }}</td>
                            <td>{{ $row['area'] ?: '-' }}</td>
                            <td>{{ $row['branch'] ?: '-' }}</td>
                            <td>{{ ucfirst($row['modality'] ?: '-') }}</td>
                            <td>
                                @if($assignedAssets->isEmpty())
                                    <span class="text-muted">Sin activos activos</span>
                                @else
                                    <div class="small mb-1">
                                        <strong>{{ $assignedAssets->count() }}</strong> activo(s)
                                    </div>
                                    <div class="small text-muted">
                                        {{ $assignedAssets->pluck('internal_code')->take(5)->implode(', ') }}
                                        @if($assignedAssets->count() > 5)
                                            +{{ $assignedAssets->count() - 5 }} mas
                                        @endif
                                    </div>
                                @endif
                            </td>
                            <td>
                                <a href="{{ route('tech.assignments.create', ['collaborator_id' => $row['id']]) }}"
                                   class="btn btn-xs btn-primary">
                                    <i class="fas fa-plus mr-1"></i>Asignar
                                </a>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
</div>
@stop
