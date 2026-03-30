@extends('adminlte::page')

@section('title', 'Buscar Colaboradores - Asignaciones TI')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <div>
        <h1 class="m-0"><i class="fas fa-search text-primary mr-2"></i> Buscar Colaboradores</h1>
        <small class="text-muted">Vista TI: activos TI y prestamos TI (directo y por area)</small>
    </div>
    <a href="{{ route('tech.assignments.hub') }}" class="btn btn-secondary btn-sm">
        <i class="fas fa-arrow-left mr-1"></i> Volver
    </a>
</div>
@stop

@section('content')
<div class="card card-outline card-primary mb-3">
    <div class="card-body">
        <form method="GET" action="{{ route('tech.assignments.search') }}" class="form-inline" style="gap:8px;">
            <input type="text" name="q" value="{{ $q }}" class="form-control form-control-sm"
                   placeholder="Buscar por nombre..." style="min-width:320px;">
            <button type="submit" class="btn btn-sm btn-primary">
                <i class="fas fa-search mr-1"></i> Buscar
            </button>
            @if($q !== '')
                <a href="{{ route('tech.assignments.search') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-times mr-1"></i> Limpiar
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
                        <th>Area</th>
                        <th>Sucursal</th>
                        <th>TI directo</th>
                        <th>TI por area</th>
                        <th>Prestamos TI</th>
                        <th>Codigos TI</th>
                        <th></th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($results as $row)
                        <tr>
                            <td>
                                <strong>{{ $row['full_name'] }}</strong><br>
                                <small class="text-muted">CC {{ $row['document'] }}</small>
                            </td>
                            <td>{{ $row['area'] ?: '-' }}</td>
                            <td>{{ $row['branch'] ?: '-' }}</td>
                            <td><span class="badge badge-primary">{{ $row['ti_direct_count'] }}</span></td>
                            <td><span class="badge badge-info">{{ $row['ti_area_count'] }}</span></td>
                            <td><span class="badge badge-warning text-dark">{{ $row['ti_loans_count'] }}</span></td>
                            <td style="max-width:280px;">
                                <small class="text-muted d-block">{{ $row['ti_codes']->take(6)->implode(', ') ?: '-' }}</small>
                            </td>
                            <td class="text-nowrap">
                                <a href="{{ route('collaborators.show', $row['id']) }}" class="btn btn-xs btn-outline-primary" title="Expediente">
                                    <i class="fas fa-user"></i>
                                </a>
                                @if(!empty($row['latest_ti_assignment_id']))
                                    <a href="{{ route('tech.assignments.return', $row['latest_ti_assignment_id']) }}"
                                       class="btn btn-xs btn-warning" title="Devolver activos TI">
                                        <i class="fas fa-undo"></i>
                                    </a>
                                @endif
                                <a href="{{ route('tech.assignments.create', ['collaborator_id' => $row['id']]) }}"
                                   class="btn btn-xs btn-primary">
                                    <i class="fas fa-plus mr-1"></i> Asignar TI
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
