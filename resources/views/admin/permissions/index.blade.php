@extends('adminlte::page')
@section('title', 'Permisos por Rol')

@section('content_header')
<div class="d-flex justify-content-between align-items-center">
    <div>
        <h1 class="m-0 text-dark">
            <i class="fas fa-key mr-2" style="color:#374151;"></i> Permisos por Rol
        </h1>
        <small class="text-muted">Define qué acciones puede realizar cada rol en el sistema</small>
    </div>
    <a href="{{ route('admin.hub') }}" class="btn btn-sm btn-secondary">
        <i class="fas fa-arrow-left mr-1"></i> Volver
    </a>
</div>
@stop

@section('content')
@include('partials._alerts')

@php
// Etiquetas legibles por módulo
$moduleLabels = [
    'collaborators'      => 'Colaboradores',
    'asset-types'        => 'Tipos de Activo',
    'tech.types'         => 'Tipos TI',
    'tech.assets'        => 'Activos TI',
    'tech.history'       => 'Historial TI',
    'tech.reports'       => 'Reportes TI',
    'assets'             => 'Otros Activos',
    'loans'              => 'Préstamos',
    'audit'              => 'Auditoría',
    'users'              => 'Usuarios',
    'branches'           => 'Sucursales',
    'categories'         => 'Categorías',
    'statuses'           => 'Estados',
    'admin'              => 'Administración',
    'reports'            => 'Reportes Globales',
    'actas'              => 'Actas Digitales',
];

// Etiquetas para permisos individuales
$permLabels = [
    'collaborators.view'          => 'Ver colaboradores',
    'collaborators.create'        => 'Crear colaboradores',
    'collaborators.edit'          => 'Editar colaboradores',
    'asset-types.view'            => 'Ver tipos de activo',
    'asset-types.create'          => 'Crear tipos de activo',
    'asset-types.edit'            => 'Editar tipos de activo',
    'asset-types.delete'          => 'Eliminar tipos de activo',
    'tech.types.view'             => 'Ver tipos TI',
    'tech.types.create'           => 'Crear tipos TI',
    'tech.types.edit'             => 'Editar tipos TI',
    'tech.assets.view'            => 'Ver activos TI',
    'tech.assets.create'          => 'Crear activos TI',
    'tech.assets.edit'            => 'Editar activos TI',
    'tech.assets.assign'          => 'Asignar activos TI / Préstamos',
    'tech.assets.disposal.view'   => 'Ver bajas TI',
    'tech.assets.disposal.request'=> 'Solicitar baja TI',
    'tech.assets.disposal.approve'=> 'Aprobar baja TI',
    'tech.history.view'           => 'Ver historial TI',
    'tech.reports.view'           => 'Ver reportes TI',
    'assets.view'                 => 'Ver otros activos',
    'assets.create'               => 'Crear otros activos',
    'assets.edit'                 => 'Editar otros activos',
    'assets.assign'               => 'Asignar / Préstamos otros activos',
    'assets.disposal.view'        => 'Ver bajas otros activos',
    'assets.disposal.request'     => 'Solicitar baja otros activos',
    'assets.disposal.approve'     => 'Aprobar baja otros activos',
    'assets.reports.view'         => 'Ver reportes otros activos',
    'assets.history.view'         => 'Ver historial otros activos',
    'assets.request.edit'         => 'Editar solicitudes',
    'assets.request.delete'       => 'Eliminar solicitudes',
    'assets.approve.edit'         => 'Editar aprobaciones',
    'assets.approve.delete'       => 'Eliminar aprobaciones',
    'assets.maintenance.approve'  => 'Aprobar mantenimiento',
    'loans.approve'               => 'Aprobar préstamos',
    'audit.discrepancy.resolve'   => 'Resolver discrepancias auditoría',
    'audit.adjustment.approve'    => 'Aprobar ajustes auditoría',
    'audit.view'                  => 'Ver auditoría',
    'audit.export'                => 'Exportar auditoría',
    'users.manage'                => 'Gestionar usuarios',
    'branches.manage'             => 'Gestionar sucursales',
    'categories.manage'           => 'Gestionar categorías',
    'statuses.manage'             => 'Gestionar estados',
    'admin.settings'              => 'Configuración del sistema',
    'reports.view'                => 'Ver reportes globales',
    'reports.global'              => 'Reportes globales completos',
    'actas.validate'              => 'Validar actas digitales',
];
@endphp

<form method="POST" action="{{ route('admin.permissions.update') }}">
@csrf

<div class="card shadow-sm">
    <div class="card-header py-2 d-flex justify-content-between align-items-center">
        <span class="font-weight-bold">
            <i class="fas fa-table mr-1"></i> Matriz de Permisos
        </span>
        <button type="submit" class="btn btn-sm btn-primary">
            <i class="fas fa-save mr-1"></i> Guardar Cambios
        </button>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
        <table class="table table-sm table-bordered mb-0" style="font-size:.78rem;">
            <thead class="thead-dark" style="position:sticky;top:0;z-index:10;">
                <tr>
                    <th style="min-width:220px;background:#1f2937;color:#fff;">Permiso</th>
                    @foreach($roles as $role)
                        <th class="text-center" style="min-width:100px;background:{{ $role->name==='Admin'?'#1e3a8a':'#1f2937' }};color:#fff;">
                            {{ $role->name }}
                            @if($role->name === 'Admin')
                                <br><small style="font-size:.65rem;opacity:.7;">(todos)</small>
                            @endif
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach($permissions as $module => $perms)
                    {{-- Fila de encabezado de módulo --}}
                    <tr style="background:#f1f5f9;">
                        <td colspan="{{ $roles->count() + 1 }}" class="font-weight-bold py-1 px-3"
                            style="font-size:.72rem;text-transform:uppercase;letter-spacing:.05em;color:#374151;">
                            <i class="fas fa-folder mr-1" style="color:#6366f1;"></i>
                            {{ $moduleLabels[$module] ?? $module }}
                        </td>
                    </tr>
                    @foreach($perms as $perm)
                    <tr>
                        <td class="pl-4 py-1" style="color:#374151;">
                            {{ $permLabels[$perm->name] ?? $perm->name }}
                            <br><small class="text-muted" style="font-size:.67rem;">{{ $perm->name }}</small>
                        </td>
                        @foreach($roles as $role)
                        <td class="text-center py-1">
                            @if($role->name === 'Admin')
                                {{-- Admin siempre tiene todo, checkbox decorativo --}}
                                <input type="checkbox" checked disabled
                                       title="Admin siempre tiene todos los permisos"
                                       style="accent-color:#1e3a8a;width:16px;height:16px;">
                            @else
                                <input type="checkbox"
                                       name="perms[{{ $role->id }}][]"
                                       value="{{ $perm->id }}"
                                       {{ isset($rolePerms[$role->id][$perm->id]) ? 'checked' : '' }}
                                       style="width:16px;height:16px;">
                            @endif
                        </td>
                        @endforeach
                    </tr>
                    @endforeach
                @endforeach
            </tbody>
        </table>
        </div>
    </div>
    <div class="card-footer py-2 d-flex justify-content-between align-items-center">
        <small class="text-muted">
            <i class="fas fa-info-circle mr-1"></i>
            El rol <strong>Admin</strong> siempre tiene todos los permisos y no puede modificarse.
            Los cambios se aplican de inmediato al guardar.
        </small>
        <button type="submit" class="btn btn-sm btn-primary">
            <i class="fas fa-save mr-1"></i> Guardar Cambios
        </button>
    </div>
</div>

</form>
@stop

@section('css')
<style>
.table-bordered td, .table-bordered th { border-color: #e2e8f0 !important; }
.table tbody tr:hover { background: #f8fafc; }
</style>
@stop
