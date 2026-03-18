@extends('adminlte::page')

@section('title', 'Plantillas Excel de Actas')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0">
            <i class="fas fa-file-excel text-success mr-2"></i>
            Plantillas Excel de Actas
        </h1>
        <div>
            <a href="{{ route('admin.acta-templates.create') }}" class="btn btn-success btn-sm">
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

<div class="card shadow-sm">
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

