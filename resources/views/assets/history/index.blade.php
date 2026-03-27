@extends('adminlte::page')
@section('title', 'Historial - Otros Activos')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <h1 class="m-0">
        <i class="fas fa-history text-secondary mr-2"></i>Historial - Otros Activos
    </h1>
    <a href="{{ route('assets.hub') }}" class="btn btn-secondary btn-sm">
        <i class="fas fa-arrow-left mr-1"></i>Volver al hub
    </a>
</div>
@stop

@section('content')
@include('partials._alerts')

<div class="row mb-3">
    <div class="col-md-3">
        <div class="small-box bg-primary">
            <div class="inner">
                <h3>{{ $totalAssetsCount }}</h3>
                <p>Activos OTRO registrados</p>
            </div>
            <div class="icon"><i class="fas fa-boxes"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-info">
            <div class="inner">
                <h3>{{ $activeAssignmentsCount }}</h3>
                <p>Asignaciones activas</p>
            </div>
            <div class="icon"><i class="fas fa-user-tag"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-warning">
            <div class="inner">
                <h3>{{ $activeLoansCount }}</h3>
                <p>Prestamos activos/vencidos</p>
            </div>
            <div class="icon"><i class="fas fa-handshake"></i></div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="small-box bg-success">
            <div class="inner">
                <h3>{{ $availableAssetsCount }}</h3>
                <p>Disponibles</p>
            </div>
            <div class="icon"><i class="fas fa-box-open"></i></div>
        </div>
    </div>
</div>

<div class="card card-outline card-primary mb-3">
    <div class="card-body py-2">
        <form method="GET" class="form-inline" style="gap:8px;">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm"
                   placeholder="Buscar por codigo, marca, modelo, colaborador o area..." style="min-width:320px;">
            <select name="event_type" class="form-control form-control-sm">
                <option value="">Todos los eventos</option>
                @foreach($eventTypes as $key => $label)
                    <option value="{{ $key }}" {{ request('event_type') === $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-sm btn-primary">
                <i class="fas fa-filter mr-1"></i>Filtrar
            </button>
            @if(request()->hasAny(['q', 'event_type']))
                <a href="{{ route('assets.history.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-times mr-1"></i>Limpiar
                </a>
            @endif
        </form>
    </div>
</div>

<div class="card card-outline card-success mb-3">
    <div class="card-header py-2">
        <strong><i class="fas fa-boxes mr-1"></i>Activos registrados (Otros Activos)</strong>
        <span class="text-muted ml-2">({{ $assets->total() }})</span>
    </div>
    <div class="card-body p-0">
        @if($assets->isEmpty())
            <div class="text-center py-4 text-muted">Sin activos registrados para el filtro actual.</div>
        @else
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="thead-light" style="font-size:.75rem;text-transform:uppercase;">
                    <tr>
                        <th>Codigo</th>
                        <th>Tipo</th>
                        <th>Marca / Modelo</th>
                        <th>Estado</th>
                        <th>Fecha registro</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($assets as $asset)
                        <tr>
                            <td><code>{{ $asset->internal_code }}</code></td>
                            <td>{{ $asset->type?->name ?? '-' }}</td>
                            <td>{{ trim(($asset->brand ?? '') . ' ' . ($asset->model ?? '')) ?: '-' }}</td>
                            <td>{{ $asset->status?->name ?? '-' }}</td>
                            <td><small>{{ $asset->created_at?->format('d/m/Y H:i') }}</small></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    @if($assets->hasPages())
        <div class="card-footer py-2">{{ $assets->links() }}</div>
    @endif
</div>

<div class="card card-outline card-info mb-3">
    <div class="card-header py-2">
        <strong><i class="fas fa-user-tag mr-1"></i>Historial de asignaciones realizadas (Otros Activos)</strong>
        <span class="text-muted ml-2">({{ $assignmentsHistory->total() }})</span>
    </div>
    <div class="card-body p-0">
        @if($assignmentsHistory->isEmpty())
            <div class="text-center py-4 text-muted">No hay asignaciones registradas para el filtro actual.</div>
        @else
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="thead-light" style="font-size:.75rem;text-transform:uppercase;">
                    <tr>
                        <th>ID</th>
                        <th>Destinatario</th>
                        <th>Activos</th>
                        <th>Fecha</th>
                        <th>Estado</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($assignmentsHistory as $a)
                        <tr>
                            <td>#{{ $a->id }}</td>
                            <td>{{ $a->recipient_name }}</td>
                            <td>{{ $a->assignmentAssets->count() }}</td>
                            <td><small>{{ $a->assignment_date?->format('d/m/Y') }}</small></td>
                            <td>{{ ucfirst($a->status) }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    @if($assignmentsHistory->hasPages())
        <div class="card-footer py-2">{{ $assignmentsHistory->links() }}</div>
    @endif
</div>

<div class="card card-outline card-secondary">
    <div class="card-header py-2">
        <strong><i class="fas fa-stream mr-1"></i>Eventos de historial</strong>
        <span class="text-muted ml-2">({{ $events->total() }})</span>
    </div>
    <div class="card-body p-0">
        @if($events->isEmpty())
            <div class="text-center py-4 text-muted">No hay eventos para los filtros seleccionados.</div>
        @else
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="thead-light" style="font-size:.75rem;text-transform:uppercase;">
                    <tr>
                        <th>Fecha</th>
                        <th>Activo</th>
                        <th>Evento</th>
                        <th>Detalle</th>
                        <th>Usuario</th>
                    </tr>
                    </thead>
                    <tbody>
                    @foreach($events as $event)
                        <tr>
                            <td><small>{{ $event->created_at?->format('d/m/Y H:i') }}</small></td>
                            <td>
                                <code>{{ $event->asset?->internal_code ?? '-' }}</code>
                                <small class="d-block text-muted">{{ $event->asset?->type?->name ?? '-' }}</small>
                            </td>
                            <td>
                                <span class="badge badge-{{ $event->event_color }}" style="font-size:.68rem;">
                                    {{ $event->event_label }}
                                </span>
                            </td>
                            <td>
                                <small class="d-block">{{ $event->notes ?: '-' }}</small>
                            </td>
                            <td><small>{{ $event->user?->name ?? '-' }}</small></td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
    @if($events->hasPages())
        <div class="card-footer py-2">{{ $events->links() }}</div>
    @endif
</div>
@stop
