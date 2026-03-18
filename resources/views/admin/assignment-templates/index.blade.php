@extends('adminlte::page')

@section('title', 'Plantillas de Asignación')

@section('content_header')
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
            <li class="breadcrumb-item"><a href="{{ route('admin.hub') }}">Administración</a></li>
            <li class="breadcrumb-item active">Plantillas de Asignación</li>
        </ol>
    </nav>
@stop

@section('content')

@include('partials._alerts')

{{-- Encabezado --}}
<div class="card card-outline" style="border-top:none;border-right:none;border-bottom:none;border-left:4px solid #374151;">
    <div class="card-body pb-2">
        <div class="d-flex align-items-center justify-content-between">
            <div class="d-flex align-items-center">
                <div class="rounded d-flex align-items-center justify-content-center mr-3"
                     style="width:48px;height:48px;background:#374151;flex-shrink:0;">
                    <i class="fas fa-layer-group text-white"></i>
                </div>
                <div>
                    <h4 class="mb-0 font-weight-bold">Plantillas de Asignación</h4>
                    <p class="text-muted mb-0 small">Configure qué activos se asignan según la modalidad u otros criterios de su empresa</p>
                </div>
            </div>
            @can('admin.settings')
            <a href="{{ route('admin.assignment-templates.create') }}" class="btn btn-primary btn-sm">
                <i class="fas fa-plus mr-1"></i> Nueva Plantilla
            </a>
            @endcan
        </div>
    </div>
</div>

{{-- Tipos y plantillas --}}
@forelse($types as $type)
<div class="card shadow-sm">
    <div class="card-header d-flex align-items-center justify-content-between py-2">
        <div>
            <h6 class="mb-0 font-weight-bold">
                <i class="fas fa-tag mr-1 text-muted"></i> {{ $type->name }}
            </h6>
            <small class="text-muted">
                Campo: <code>{{ $type->trigger_field ?? 'manual' }}</code> ·
                Asignación a: {{ match($type->target) { 'person'=>'Persona','area'=>'Área','project'=>'Proyecto',default=>'Pool' } }}
            </small>
        </div>
        <span class="badge {{ $type->active ? 'badge-success' : 'badge-secondary' }}">
            {{ $type->active ? 'Activo' : 'Inactivo' }}
        </span>
    </div>

    <div class="card-body p-0">
        @if($type->templates->isEmpty())
            <p class="text-muted text-center py-3 mb-0 small">Sin plantillas configuradas</p>
        @else
            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="thead-light">
                        <tr>
                            <th>Plantilla</th>
                            <th>Disparador</th>
                            <th>Activos incluidos</th>
                            <th>Estado</th>
                            <th class="text-center" style="width:80px">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($type->templates as $template)
                        <tr>
                            <td>
                                <strong>{{ $template->name }}</strong>
                                @if($template->description)
                                    <br><small class="text-muted">{{ $template->description }}</small>
                                @endif
                            </td>
                            <td>
                                @if($template->trigger_value)
                                    <code>{{ $template->trigger_value }}</code>
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            <td>
                                @foreach($template->items as $item)
                                    <span class="badge mr-1 mb-1
                                        {{ $item->goes_to === 'assignee' ? 'badge-primary' : 'badge-secondary' }}">
                                        {{ $item->quantity }}× {{ $item->assetType->name }}
                                        @if($item->goes_to !== 'assignee')
                                            <i class="fas fa-arrow-right ml-1"></i> {{ $item->goes_to_label }}
                                        @endif
                                    </span>
                                @endforeach
                                @if($template->items->isEmpty())
                                    <span class="text-muted small">Sin ítems</span>
                                @endif
                            </td>
                            <td>
                                <span class="badge {{ $template->active ? 'badge-success' : 'badge-secondary' }}">
                                    {{ $template->active ? 'Activa' : 'Inactiva' }}
                                </span>
                            </td>
                            <td class="text-center">
                                @can('admin.settings')
                                <a href="{{ route('admin.assignment-templates.edit', $template) }}"
                                   class="btn btn-xs btn-outline-primary" title="Editar">
                                    <i class="fas fa-pen"></i>
                                </a>
                                <button type="button"
                                        class="btn btn-xs btn-outline-secondary btn-toggle-template"
                                        data-id="{{ $template->id }}"
                                        data-active="{{ $template->active ? 1 : 0 }}"
                                        title="{{ $template->active ? 'Desactivar' : 'Activar' }}">
                                    <i class="fas fa-{{ $template->active ? 'pause' : 'play' }}"></i>
                                </button>
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
@empty
    <div class="card shadow-sm">
        <div class="card-body text-center py-5 text-muted">
            <i class="fas fa-layer-group fa-3x mb-3 d-block opacity-50"></i>
            No hay tipos de asignación configurados.
            <br>
            <a href="{{ route('admin.assignment-templates.create') }}" class="btn btn-primary btn-sm mt-3">
                <i class="fas fa-plus mr-1"></i> Crear primera plantilla
            </a>
        </div>
    </div>
@endforelse

@stop

@section('js')
<script>
$(function() {
    $('.btn-toggle-template').on('click', function() {
        const btn = $(this);
        const id = btn.data('id');

        $.ajax({
            url: '/admin/assignment-templates/' + id + '/toggle',
            method: 'POST',
            data: { _method: 'PATCH', _token: '{{ csrf_token() }}' },
            success: function(res) {
                location.reload();
            }
        });
    });
});
</script>
@stop
