@extends('adminlte::page')

@section('title', 'Asignaciones TI')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0"><i class="fas fa-user-check text-primary mr-2"></i> Asignaciones TI</h1>
        @can('tech.assets.assign')
            <a href="{{ route('tech.assignments.create') }}" class="btn btn-primary">
                <i class="fas fa-plus mr-1"></i> Nueva Asignación
            </a>
        @endcan
    </div>
@stop

@section('content')

@include('partials._alerts')

<div class="row mb-3">
    <div class="col-md-4">
        <div class="info-box">
            <span class="info-box-icon bg-primary"><i class="fas fa-user-check"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Asignaciones Activas</span>
                <span class="info-box-number">{{ $assignments->count() }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="info-box">
            <span class="info-box-icon bg-success"><i class="fas fa-laptop"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Activos en Uso</span>
                <span class="info-box-number">{{ $assignments->sum(fn($a) => $a->activeAssets->count()) }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="info-box">
            <span class="info-box-icon bg-info"><i class="fas fa-history"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Ver Historial</span>
                <span class="info-box-number">
                    <a href="{{ route('tech.history.index') }}" class="text-white text-decoration-none">Completo</a>
                </span>
            </div>
        </div>
    </div>
</div>

<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-list mr-1"></i> Asignaciones Activas</h3>
    </div>
    <div class="card-body p-0">
        @if($assignments->isEmpty())
            <div class="text-center p-5 text-muted">
                <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                No hay asignaciones activas.
                <br>
                <a href="{{ route('tech.assignments.create') }}" class="btn btn-primary mt-3">
                    <i class="fas fa-plus mr-1"></i> Crear Primera Asignación
                </a>
            </div>
        @else
            <table class="table table-hover table-striped mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>Colaborador</th>
                        <th>Modalidad</th>
                        <th>Sucursal</th>
                        <th>Activos Asignados</th>
                        <th>Fecha</th>
                        <th>Registrado por</th>
                        <th width="100">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($assignments as $assignment)
                        <tr>
                            <td><small class="text-muted">#{{ $assignment->id }}</small></td>
                            <td>
                                <strong>{{ $assignment->collaborator->full_name }}</strong><br>
                                <small class="text-muted">CC {{ $assignment->collaborator->document }}</small>
                            </td>
                            <td>
                                @php
                                    $mod = $assignment->collaborator->modalidad_trabajo ?? 'presencial';
                                    $badgeClass = match($mod) { 'remoto' => 'badge-info', 'hibrido' => 'badge-warning text-dark', default => 'badge-success' };
                                    $modLabel   = match($mod) { 'remoto' => 'Remoto', 'hibrido' => 'Híbrido', default => 'Presencial' };
                                @endphp
                                <span class="badge {{ $badgeClass }}">{{ $modLabel }}</span>
                            </td>
                            <td>{{ $assignment->collaborator->branch?->name ?? '-' }}</td>
                            <td>
                                @foreach($assignment->activeAssets->take(3) as $aa)
                                    <span class="badge badge-light border">{{ $aa->asset->internal_code }}</span>
                                @endforeach
                                @if($assignment->activeAssets->count() > 3)
                                    <span class="badge badge-secondary">+{{ $assignment->activeAssets->count() - 3 }} más</span>
                                @endif
                                <small class="text-muted d-block">{{ $assignment->activeAssets->count() }} activo(s)</small>
                            </td>
                            <td>{{ $assignment->assignment_date->format('d/m/Y') }}</td>
                            <td><small>{{ $assignment->assignedBy?->name ?? '-' }}</small></td>
                            <td>
                                <a href="{{ route('tech.assignments.show', $assignment) }}" class="btn btn-sm btn-info" title="Ver">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @can('tech.assets.assign')
                                    <a href="{{ route('tech.assignments.return', $assignment) }}" class="btn btn-sm btn-warning" title="Devolución">
                                        <i class="fas fa-undo"></i>
                                    </a>
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

@stop
@section('js')
<script>
    setTimeout(() => document.querySelectorAll('.alert.show').forEach(el => el.classList.remove('show')), 4000);
</script>
@stop
