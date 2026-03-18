@extends('adminlte::page')

@section('title', 'Historial de Activos TI')

@section('content_header')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('tech.assets.hub') }}">Activos TI</a></li>
            <li class="breadcrumb-item active">Historial</li>
        </ol>
    </nav>
@stop

@section('content')

{{-- Filtros --}}
<div class="card card-outline card-secondary">
    <div class="card-header">
        <h3 class="card-title"><i class="fas fa-filter mr-1"></i> Filtros</h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
            </button>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('tech.history.index') }}" class="row">
            <div class="col-md-5">
                <label>Buscar por Colaborador (nombre o cédula)</label>
                <input type="text" name="collaborator" class="form-control"
                       value="{{ request('collaborator') }}"
                       placeholder="Ej: Juan García o 123456789">
            </div>
            <div class="col-md-3">
                <label>Estado</label>
                <select name="status" class="form-control">
                    <option value="">Todos</option>
                    <option value="activa"   {{ request('status') === 'activa'   ? 'selected' : '' }}>Activa</option>
                    <option value="devuelta" {{ request('status') === 'devuelta' ? 'selected' : '' }}>Devuelta</option>
                </select>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <button type="submit" class="btn btn-primary w-100">
                    <i class="fas fa-search mr-1"></i> Buscar
                </button>
            </div>
            <div class="col-md-2 d-flex align-items-end">
                <a href="{{ route('tech.history.index') }}" class="btn btn-secondary w-100">
                    <i class="fas fa-times mr-1"></i> Limpiar
                </a>
            </div>
        </form>
    </div>
</div>

{{-- Resultados --}}
<div class="card card-outline card-primary">
    <div class="card-header">
        <h3 class="card-title">
            <i class="fas fa-list mr-1"></i> Historial
            <span class="badge badge-info ml-1">{{ $assignments->total() }} registros</span>
        </h3>
    </div>
    <div class="card-body p-0">
        @if($assignments->isEmpty())
            <div class="text-center p-5 text-muted">
                <i class="fas fa-inbox fa-3x mb-3 d-block"></i>
                No se encontraron registros.
            </div>
        @else
            <table class="table table-hover table-sm mb-0">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>Colaborador</th>
                        <th>Modalidad</th>
                        <th>Activos</th>
                        <th>Fecha Asignación</th>
                        <th>Estado</th>
                        <th>Registrado por</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($assignments as $assignment)
                        <tr>
                            <td><small class="text-muted">#{{ $assignment->id }}</small></td>
                            <td>
                                <a href="{{ route('collaborators.show', $assignment->collaborator) }}">
                                    {{ $assignment->collaborator->full_name }}
                                </a><br>
                                <small class="text-muted">CC {{ $assignment->collaborator->document }}</small>
                            </td>
                            <td>
                                @php
                                    $mod = $assignment->collaborator->modalidad_trabajo ?? 'presencial';
                                    $bc = match($mod) { 'remoto' => 'badge-info', 'hibrido' => 'badge-warning text-dark', default => 'badge-success' };
                                    $ml = match($mod) { 'remoto' => 'Remoto', 'hibrido' => 'Híbrido', default => 'Presencial' };
                                @endphp
                                <span class="badge {{ $bc }}">{{ $ml }}</span>
                            </td>
                            <td>
                                <small>
                                    @foreach($assignment->assignmentAssets->take(2) as $aa)
                                        <span class="{{ $aa->isReturned() ? 'text-muted' : 'text-success' }}">
                                            {{ $aa->asset->internal_code }}@if(!$loop->last), @endif
                                        </span>
                                    @endforeach
                                    @if($assignment->assignmentAssets->count() > 2)
                                        <span class="text-muted">+{{ $assignment->assignmentAssets->count() - 2 }} más</span>
                                    @endif
                                </small>
                            </td>
                            <td>{{ $assignment->assignment_date->format('d/m/Y') }}</td>
                            <td>
                                @if($assignment->status === 'activa')
                                    <span class="badge badge-success">Activa</span>
                                @else
                                    <span class="badge badge-secondary">Devuelta</span>
                                @endif
                            </td>
                            <td><small>{{ $assignment->assignedBy?->name ?? '-' }}</small></td>
                            <td>
                                <a href="{{ route('tech.assignments.show', $assignment) }}"
                                   class="btn btn-xs btn-info">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
    @if($assignments->hasPages())
        <div class="card-footer">
            {{ $assignments->withQueryString()->links() }}
        </div>
    @endif
</div>

@stop
