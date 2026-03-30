@extends('adminlte::page')

@section('title', 'Asignaciones TI')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0"><i class="fas fa-user-check text-primary mr-2"></i> Asignaciones TI</h1>
        @can('tech.assets.assign')
            <a href="{{ route('tech.assignments.create') }}" class="btn btn-primary">
                <i class="fas fa-plus mr-1"></i> Nueva Asignacion
            </a>
        @endcan
    </div>
@stop

@section('content')
@include('partials._alerts')

<div class="row mb-3">
    <div class="col-md-4">
        <div class="info-box">
            <span class="info-box-icon bg-primary"><i class="fas fa-users"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Colaboradores con asignacion</span>
                <span class="info-box-number">{{ $groupedAssignments->count() }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="info-box">
            <span class="info-box-icon bg-success"><i class="fas fa-laptop"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Activos TI en uso</span>
                <span class="info-box-number">{{ $assignments->sum(fn($a) => $a->activeAssets->count()) }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="info-box">
            <span class="info-box-icon bg-info"><i class="fas fa-history"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Ver historial TI</span>
                <span class="info-box-number">
                    <a href="{{ route('tech.history.index') }}" class="text-white text-decoration-none">Completo</a>
                </span>
            </div>
        </div>
    </div>
</div>

<div class="card card-outline card-primary">
    <div class="card-header py-2">
        <h3 class="card-title"><i class="fas fa-list mr-1"></i> Vista agrupada por colaborador</h3>
    </div>
    <div class="card-body p-0">
        @if($groupedAssignments->isEmpty())
            <div class="text-center p-5 text-muted">
                <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                No hay asignaciones TI activas.
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-hover table-striped mb-0">
                    <thead class="thead-light">
                    <tr>
                        <th>Colaborador</th>
                        <th>Modalidad</th>
                        <th>Sucursal</th>
                        <th>Destino</th>
                        <th>Asignaciones</th>
                        <th>Activos en uso</th>
                        <th>Ultima</th>
                        <th width="180">Acciones</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($groupedAssignments as $group)
                        @php
                            $collaborator = $group['collaborator'];
                            $latest = $group['latest_assignment'];
                            $mod = $collaborator->modalidad_trabajo ?? 'presencial';
                            $badgeClass = match($mod) { 'remoto' => 'badge-info', 'hibrido' => 'badge-warning text-dark', default => 'badge-success' };
                            $modLabel   = match($mod) { 'remoto' => 'Remoto', 'hibrido' => 'Hibrido', default => 'Presencial' };
                        @endphp
                        <tr>
                            <td>
                                <strong>{{ $collaborator->full_name }}</strong><br>
                                <small class="text-muted">CC {{ $collaborator->document }}</small>
                            </td>
                            <td><span class="badge {{ $badgeClass }}">{{ $modLabel }}</span></td>
                            <td>{{ $collaborator->branch?->name ?? '-' }}</td>
                            <td>
                                @foreach($group['destination_labels'] as $label)
                                    <span class="badge badge-light border">{{ $label }}</span>
                                @endforeach
                            </td>
                            <td><span class="badge badge-primary">{{ $group['assignments_count'] }}</span></td>
                            <td><span class="badge badge-success">{{ $group['assets_count'] }}</span></td>
                            <td>
                                <small>{{ optional($latest->assignment_date)->format('d/m/Y') ?? '-' }}</small><br>
                                <small class="text-muted">#{{ $latest->id }}</small>
                            </td>
                            <td>
                                <a href="{{ route('collaborators.show', $collaborator) }}" class="btn btn-sm btn-outline-primary" title="Expediente">
                                    <i class="fas fa-user"></i>
                                </a>
                                <a href="{{ route('tech.assignments.show', $latest) }}" class="btn btn-sm btn-info" title="Ver ultima">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @can('tech.assets.assign')
                                    <a href="{{ route('tech.assignments.create', ['collaborator_id' => $collaborator->id]) }}" class="btn btn-sm btn-primary" title="Asignar">
                                        <i class="fas fa-plus"></i>
                                    </a>
                                @endcan
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

@section('js')
<script>
setTimeout(() => document.querySelectorAll('.alert.show').forEach(el => el.classList.remove('show')), 4000);
</script>
@stop
