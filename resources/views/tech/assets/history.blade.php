@extends('adminlte::page')

@section('title', 'Historial de Activos TI')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <h1 class="m-0">
        <i class="fas fa-history text-primary mr-2"></i>Historial de Activos TI
    </h1>
    <a href="{{ route('tech.assets.hub') }}" class="btn btn-secondary btn-sm">
        <i class="fas fa-arrow-left mr-1"></i>Volver al hub
    </a>
</div>
@stop

@section('content')
@include('partials._alerts')

<div class="card card-outline card-primary mb-3">
    <div class="card-body py-2">
        <form method="GET" class="form-inline" style="gap:8px;">
            <input type="text" name="q" value="{{ request('q') }}" class="form-control form-control-sm"
                   placeholder="Buscar por codigo, marca, modelo, serial o detalle..." style="min-width:320px;">
            <select name="event_type" class="form-control form-control-sm">
                <option value="">Todos los eventos</option>
                @foreach($eventTypes as $key => $label)
                    <option value="{{ $key }}" {{ request('event_type') === $key ? 'selected' : '' }}>
                        {{ $label }}
                    </option>
                @endforeach
            </select>
            @if(($subcategories ?? collect())->count())
                <select name="subcategory" class="form-control form-control-sm">
                    <option value="">Todas las subcategorias</option>
                    @foreach($subcategories as $sub)
                        <option value="{{ $sub }}" {{ request('subcategory') === $sub ? 'selected' : '' }}>{{ $sub }}</option>
                    @endforeach
                </select>
            @endif
            <button type="submit" class="btn btn-sm btn-primary">
                <i class="fas fa-filter mr-1"></i>Filtrar
            </button>
            @if(request()->hasAny(['q', 'event_type', 'subcategory']))
                <a href="{{ route('tech.assets.history.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-times mr-1"></i>Limpiar
                </a>
            @endif
        </form>
    </div>
</div>

<div id="historyAccordion">
    <div class="card card-outline card-primary mb-2">
        <div class="card-header py-2">
            <button class="btn btn-link p-0 text-dark font-weight-bold" data-toggle="collapse" data-target="#eventsSection" aria-expanded="true">
                <i class="fas fa-stream mr-1"></i> Eventos de activos TI ({{ $events->total() }})
            </button>
        </div>
        <div id="eventsSection" class="collapse show" data-parent="#historyAccordion">
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
                                        @if($event->asset)
                                            <a href="{{ route('tech.assets.show', $event->asset) }}">
                                                <code>{{ $event->asset->internal_code }}</code>
                                            </a>
                                        @else
                                            <code>-</code>
                                        @endif
                                        <small class="d-block text-muted">{{ $event->asset?->type?->name ?? '-' }}</small>
                                    </td>
                                    <td>
                                        <span class="badge badge-{{ $event->event_color }}" style="font-size:.68rem;">
                                            {{ $event->event_label }}
                                        </span>
                                    </td>
                                    <td><small class="d-block">{{ $event->notes ?: '-' }}</small></td>
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
    </div>

    <div class="card card-outline card-info">
        <div class="card-header py-2">
            <button class="btn btn-link p-0 text-dark font-weight-bold" data-toggle="collapse" data-target="#assetsSection" aria-expanded="false">
                <i class="fas fa-laptop mr-1"></i> Activos TI registrados ({{ $assets->total() }})
            </button>
        </div>
        <div id="assetsSection" class="collapse" data-parent="#historyAccordion">
            <div class="card-body p-0">
                @if($assets->isEmpty())
                    <div class="text-center py-4 text-muted">Sin activos TI registrados para el filtro actual.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="thead-light" style="font-size:.75rem;text-transform:uppercase;">
                            <tr>
                                <th>Codigo</th>
                                <th>Tipo</th>
                                <th>Marca / Modelo</th>
                                <th>Serial</th>
                                <th>Estado</th>
                                <th>Sede</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach($assets as $asset)
                                <tr>
                                    <td>
                                        <a href="{{ route('tech.assets.show', $asset) }}">
                                            <code>{{ $asset->internal_code }}</code>
                                        </a>
                                    </td>
                                    <td>{{ $asset->type?->name ?? '-' }}</td>
                                    <td>{{ trim(($asset->brand ?? '') . ' ' . ($asset->model ?? '')) ?: '-' }}</td>
                                    <td>{{ $asset->serial ?: '-' }}</td>
                                    <td>{{ $asset->status?->name ?? '-' }}</td>
                                    <td>{{ $asset->branch?->name ?? '-' }}</td>
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
    </div>
</div>
@stop
