@extends('adminlte::page')

@section('title', 'Inventario de Otros Activos')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0">
            <i class="fas fa-boxes text-primary mr-2"></i> Inventario de Otros Activos
        </h1>
        <a href="{{ route('assets.hub') }}" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left mr-1"></i> Volver al hub
        </a>
    </div>
@stop

@section('content')
@include('partials._alerts')

<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Código</th>
                    <th>Tipo</th>
                    <th>Marca / Modelo</th>
                    <th>Serial</th>
                    <th>Estado</th>
                    <th>Sucursal</th>
                </tr>
            </thead>
            <tbody>
            @forelse($assets as $asset)
                <tr>
                    <td><code>{{ $asset->internal_code ?? '—' }}</code></td>
                    <td>{{ $asset->type?->name ?? '—' }}</td>
                    <td>{{ trim(($asset->brand ?? '') . ' ' . ($asset->model ?? '')) ?: '—' }}</td>
                    <td>{{ $asset->serial ?? '—' }}</td>
                    <td>{{ $asset->status?->name ?? '—' }}</td>
                    <td>{{ $asset->branch?->name ?? '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center text-muted py-4">No hay otros activos registrados.</td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@stop
