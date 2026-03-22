@extends('adminlte::page')

@section('title', 'Inventario — Otros Activos')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div>
            <h1 class="m-0">
                <i class="fas fa-boxes mr-2" style="color:#7c3aed;"></i>
                Inventario — Otros Activos
            </h1>
            <small class="text-muted">Mobiliario, enseres, electrodomésticos y activos no TI</small>
        </div>
        <div>
            @can('assets.create')
                <a href="{{ route('assets.create') }}" class="btn btn-sm btn-primary">
                    <i class="fas fa-plus mr-1"></i> Registrar Activo
                </a>
            @endcan
            <a href="{{ route('assets.hub') }}" class="btn btn-sm btn-outline-secondary ml-1">
                <i class="fas fa-arrow-left mr-1"></i> Hub
            </a>
        </div>
    </div>
@stop

@section('content')
@include('partials._alerts')

{{-- ── Filtros ─────────────────────────────────────────────────────────── --}}
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('assets.index') }}" class="form-inline flex-wrap" style="gap:.5rem;">

            <input type="text" name="q" class="form-control form-control-sm"
                   placeholder="Código, marca, serial..." style="min-width:200px;"
                   value="{{ request('q') }}">

            <select name="subcategory" class="form-control form-control-sm">
                <option value="">— Subcategoría —</option>
                @foreach($subcategories as $sub)
                    <option value="{{ $sub }}" {{ request('subcategory') === $sub ? 'selected' : '' }}>
                        {{ $sub }}
                    </option>
                @endforeach
            </select>

            <select name="status" class="form-control form-control-sm">
                <option value="">— Estado —</option>
                @foreach($statuses as $s)
                    <option value="{{ $s->name }}" {{ request('status') === $s->name ? 'selected' : '' }}>
                        {{ $s->name }}
                    </option>
                @endforeach
            </select>

            <select name="branch" class="form-control form-control-sm">
                <option value="">— Sucursal —</option>
                @foreach($branches as $b)
                    <option value="{{ $b->id }}" {{ request('branch') == $b->id ? 'selected' : '' }}>
                        {{ $b->name }}
                    </option>
                @endforeach
            </select>

            <button type="submit" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-search mr-1"></i> Filtrar
            </button>
            @if(request()->hasAny(['q','status','branch','subcategory']))
                <a href="{{ route('assets.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-times mr-1"></i> Limpiar
                </a>
            @endif
        </form>
    </div>
</div>

{{-- ── Tabla ───────────────────────────────────────────────────────────── --}}
<div class="card shadow-sm">
    <div class="card-header py-2 d-flex align-items-center justify-content-between"
         style="border-left:4px solid #7c3aed;">
        <h6 class="mb-0 font-weight-bold">
            <i class="fas fa-boxes mr-1" style="color:#7c3aed;"></i>
            Activos registrados
            <span class="badge badge-secondary ml-1">{{ $assets->count() }}</span>
        </h6>
    </div>
    <div class="card-body p-0">
        @if($assets->isEmpty())
            <div class="text-center py-5 text-muted">
                <i class="fas fa-box-open fa-3x mb-3 d-block" style="opacity:.2;"></i>
                <p class="mb-2">No hay activos registrados.</p>
                @can('assets.create')
                    <a href="{{ route('assets.create') }}" class="btn btn-sm btn-primary">
                        <i class="fas fa-plus mr-1"></i> Registrar primer activo
                    </a>
                @endcan
            </div>
        @else
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Código</th>
                            <th>Nombre / Tipo</th>
                            <th>Subcategoría</th>
                            <th>Marca / Modelo</th>
                            <th>Serial</th>
                            <th>Estado</th>
                            <th>Sucursal</th>
                            <th style="width:60px;"></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($assets as $asset)
                        <tr>
                            <td>
                                <a href="{{ route('assets.show', $asset) }}">
                                    <code style="font-size:.78rem;">{{ $asset->internal_code ?? '—' }}</code>
                                </a>
                            </td>
                            <td class="font-weight-bold">
                                {{ $asset->type?->name ?? '—' }}
                            </td>
                            <td>
                                @if($asset->type?->subcategory)
                                    <span class="badge badge-light border text-muted" style="font-size:.72rem;">
                                        {{ $asset->type->subcategory }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>{{ trim(($asset->brand ?? '') . ' ' . ($asset->model ?? '')) ?: '—' }}</td>
                            <td class="text-muted small">{{ $asset->serial ?? '—' }}</td>
                            <td>
                                @if($asset->status)
                                    <span class="badge badge-pill"
                                          style="background:{{ $asset->status->color ?? '#6c757d' }};color:#fff;font-size:.72rem;">
                                        {{ $asset->status->name }}
                                    </span>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td class="text-muted small">{{ $asset->branch?->name ?? '—' }}</td>
                            <td class="text-center">
                                <a href="{{ route('assets.show', $asset) }}"
                                   class="btn btn-xs btn-outline-primary" title="Ver detalle">
                                    <i class="fas fa-eye"></i>
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

@section('css')
<style>
.card { border-radius: 10px; }
.table th { font-size:.75rem; text-transform:uppercase; letter-spacing:.04em; }
</style>
@stop
