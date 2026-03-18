@extends('adminlte::page')

@php
    $scope = strtoupper($selectedCategory ?? '');
    $scopeLabel = match($scope) {
        'TI' => 'Actas TI',
        'OTRO' => 'Actas OTRO',
        'ALL' => 'Actas MIXTAS',
        default => 'Todas',
    };
@endphp

@section('title', 'Plantillas Excel de Actas')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0">
            <i class="fas fa-file-excel text-success mr-2"></i>
            Plantillas Excel de Actas
        </h1>
        <div>
            <a href="{{ match($scope ?? '') {
                'OTRO' => route('admin.acta-templates.create.otro'),
                'ALL' => route('admin.acta-templates.create.mixta'),
                default => route('admin.acta-templates.create.ti'),
            } }}" class="btn btn-success btn-sm">
                <i class="fas fa-plus mr-1"></i> Subir plantilla
            </a>
            <a href="{{ route('admin.settings') }}" class="btn btn-secondary btn-sm">
                <i class="fas fa-arrow-left mr-1"></i> Volver a Configuración
            </a>
        </div>
    </div>
@stop

@section('content')

@include('partials._alerts')

<div class="mb-3 d-flex flex-wrap">
    <a href="{{ route('admin.acta-templates.ti') }}" class="btn btn-sm mr-2 mb-2 {{ $scope === 'TI' ? 'btn-primary' : 'btn-outline-primary' }}">TI</a>
    <a href="{{ route('admin.acta-templates.otro') }}" class="btn btn-sm mr-2 mb-2 {{ $scope === 'OTRO' ? 'btn-secondary' : 'btn-outline-secondary' }}" style="{{ $scope === 'OTRO' ? 'background:#7c3aed;border-color:#7c3aed;color:#fff;' : 'color:#7c3aed;border-color:#7c3aed;' }}">OTRO</a>
    <a href="{{ route('admin.acta-templates.mixta') }}" class="btn btn-sm mr-2 mb-2 {{ $scope === 'ALL' ? 'btn-success' : 'btn-outline-success' }}">MIXTA</a>
    <a href="{{ route('admin.acta-templates.index') }}" class="btn btn-sm mb-2 {{ empty($scope) ? 'btn-dark' : 'btn-outline-dark' }}">Ver todas</a>
</div>

<div class="card shadow-sm">
    <div class="card-header py-2 bg-white">
        <strong>{{ $scopeLabel }}</strong>
        <small class="text-muted d-block">Administra plantillas separadas para TI, OTRO y MIXTA.</small>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="thead-light">
                <tr>
                    <th>Nombre</th>
                    <th>Tipo</th>
                    <th>Categoría</th>
                    <th>Activa</th>
                    <th>Fila activos</th>
                    <th>Campos</th>
                    <th class="text-right">Acciones</th>
                </tr>
            </thead>
            <tbody>
            @forelse($templates as $t)
                <tr>
                    <td class="font-weight-bold">{{ $t->name }}</td>
                    <td><span class="badge badge-secondary">{{ $t->acta_type }}</span></td>
                    <td><span class="badge badge-info">{{ $t->asset_category }}</span></td>
                    <td>
                        @if($t->active)
                            <span class="badge badge-success">Sí</span>
                        @else
                            <span class="badge badge-light">No</span>
                        @endif
                    </td>
                    <td>{{ $t->assets_start_row ?? '—' }}</td>
                    <td><span class="badge badge-primary">{{ $t->fields_count }}</span></td>
                    <td class="text-right">
                        <a href="{{ route('admin.acta-templates.fields.index', $t) }}" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-th mr-1"></i> Campos
                        </a>
                        <a href="{{ route('admin.acta-templates.edit', $t) }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-edit mr-1"></i> Editar
                        </a>
                        @if(!$t->active)
                            <form method="POST" action="{{ route('admin.acta-templates.toggle', $t) }}" class="d-inline">
                                @csrf
                                @method('PATCH')
                                <button class="btn btn-sm btn-success">
                                    <i class="fas fa-check mr-1"></i> Activar
                                </button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted p-4">
                        No hay plantillas aún. Sube una plantilla Excel para comenzar.
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@stop
