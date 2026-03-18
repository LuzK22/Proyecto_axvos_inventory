@extends('adminlte::page')
@section('title', 'Áreas / Espacios')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <h1 class="m-0 text-dark" style="font-size:1.3rem;">
        <i class="fas fa-map-marker-alt mr-2" style="color:#7c3aed;"></i>Áreas / Espacios Físicos
    </h1>
    <a href="{{ route('areas.create') }}" class="btn btn-sm btn-primary">
        <i class="fas fa-plus mr-1"></i> Nueva Área
    </a>
</div>
@stop

@section('content')
@include('partials._alerts')

<div class="card card-outline card-primary">
    <div class="card-header py-2">
        <h6 class="mb-0 font-weight-bold">
            <i class="fas fa-list mr-1"></i>
            {{ $areas->total() }} área(s) registrada(s)
        </h6>
    </div>
    <div class="card-body p-0">
        @if($areas->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="fas fa-map-marker-alt fa-3x mb-3 d-block" style="opacity:.2;"></i>
                No hay áreas registradas aún.
                <div class="mt-2">
                    <a href="{{ route('areas.create') }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus mr-1"></i> Crear primera área
                    </a>
                </div>
            </div>
        @else
        <table class="table table-sm table-hover mb-0">
            <thead style="background:#f4f6f9;font-size:.75rem;text-transform:uppercase;">
                <tr>
                    <th class="pl-3">Nombre</th>
                    <th>Sucursal</th>
                    <th>Descripción</th>
                    <th class="text-center">Estado</th>
                    <th class="text-center" style="width:80px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach($areas as $area)
                <tr>
                    <td class="pl-3 py-2 font-weight-bold">
                        <i class="fas fa-map-marker-alt mr-1" style="color:#7c3aed;font-size:.75rem;"></i>
                        {{ $area->name }}
                    </td>
                    <td class="py-2">
                        <small>{{ $area->branch?->name ?? '—' }}</small>
                    </td>
                    <td class="py-2">
                        <small class="text-muted">{{ $area->description ?: '—' }}</small>
                    </td>
                    <td class="py-2 text-center">
                        @if($area->active)
                            <span class="badge badge-success" style="font-size:.65rem;">Activa</span>
                        @else
                            <span class="badge badge-secondary" style="font-size:.65rem;">Inactiva</span>
                        @endif
                    </td>
                    <td class="py-2 text-center">
                        <a href="{{ route('areas.edit', $area) }}"
                           class="btn btn-xs btn-outline-secondary"
                           title="Editar">
                            <i class="fas fa-pencil-alt"></i>
                        </a>
                    </td>
                </tr>
                @endforeach
            </tbody>
        </table>
        @endif
    </div>
    @if($areas->hasPages())
    <div class="card-footer py-2">
        {{ $areas->links() }}
    </div>
    @endif
</div>
@stop
