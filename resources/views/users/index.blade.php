@extends('adminlte::page')

@section('title', 'Usuarios del Sistema')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <a href="{{ route('admin.hub') }}" class="btn btn-sm btn-secondary mr-3">
                <i class="fas fa-arrow-left mr-1"></i> Administración
            </a>
            <h1 class="m-0 font-weight-bold" style="color:#0d1b2a;">
                <i class="fas fa-users mr-2" style="color:#00b4d8;"></i> Usuarios del Sistema
            </h1>
        </div>
        @can('users.manage')
        <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm">
            <i class="fas fa-plus mr-1"></i> Nuevo Usuario
        </a>
        @endcan
    </div>
@stop

@section('content')

@include('partials._alerts')

{{-- Filtros de búsqueda --}}
<div class="card shadow-sm mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('users.index') }}" class="form-inline flex-wrap" style="gap:8px;">
            <div class="input-group input-group-sm mr-2">
                <div class="input-group-prepend">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                </div>
                <input type="text" name="q" class="form-control" placeholder="Nombre, usuario o email..."
                       value="{{ request('q') }}" style="min-width:200px;">
            </div>
            <select name="role" class="form-control form-control-sm mr-2" style="min-width:160px;">
                <option value="">— Todos los roles —</option>
                @foreach($roles as $role)
                    <option value="{{ $role->name }}" {{ request('role') === $role->name ? 'selected' : '' }}>
                        {{ str_replace('_', ' ', $role->name) }}
                    </option>
                @endforeach
            </select>
            <button type="submit" class="btn btn-sm btn-primary mr-1">
                <i class="fas fa-filter mr-1"></i> Filtrar
            </button>
            @if(request('q') || request('role'))
            <a href="{{ route('users.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="fas fa-times mr-1"></i> Limpiar
            </a>
            @endif
        </form>
    </div>
</div>

{{-- Tabla --}}
<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover table-sm mb-0">
            <thead class="thead-light">
                <tr>
                    <th class="pl-3" style="width:40px;">#</th>
                    <th>Nombre</th>
                    <th>Usuario</th>
                    <th>Email</th>
                    <th>Rol</th>
                    <th class="text-center" style="width:80px;">Estado</th>
                    <th>Creado</th>
                    <th style="width:150px;" class="text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
            @forelse($users as $i => $user)
                <tr>
                    <td class="pl-3 text-muted small">{{ $users->firstItem() + $i }}</td>
                    <td class="font-weight-bold">{{ $user->name }}</td>
                    <td><code class="text-dark">{{ $user->username }}</code></td>
                    <td class="text-muted">{{ $user->email }}</td>
                    <td>
                        @foreach($user->roles as $role)
                            @php
                                $badgeColor = match($role->name) {
                                    'Admin'           => 'danger',
                                    'Auxiliar_TI'     => 'primary',
                                    'Gestor_Activos'  => 'success',
                                    'Gestor_General'  => 'info',
                                    'Aprobador'       => 'warning',
                                    'Auditor'         => 'secondary',
                                    default           => 'dark',
                                };
                            @endphp
                            <span class="badge badge-pill badge-{{ $badgeColor }}">
                                {{ str_replace('_', ' ', $role->name) }}
                            </span>
                        @endforeach
                    </td>
                    <td class="text-center">
                        @if($user->isLocked())
                            <span class="badge badge-danger"
                                  title="Bloqueado hasta {{ $user->locked_until->format('H:i d/m') }} — {{ $user->lockRemainingMinutes() }} min restantes">
                                <i class="fas fa-lock mr-1"></i>Bloqueado
                            </span>
                        @elseif($user->failed_login_attempts > 0)
                            <span class="badge badge-warning"
                                  title="{{ $user->failed_login_attempts }} intento(s) fallido(s)">
                                <i class="fas fa-exclamation-triangle mr-1"></i>{{ $user->failed_login_attempts }} intentos
                            </span>
                        @else
                            <span class="badge badge-success"><i class="fas fa-check mr-1"></i>OK</span>
                        @endif
                    </td>
                    <td class="text-muted small">{{ optional($user->created_at)->format('d/m/Y') ?? '—' }}</td>
                    <td class="text-center">
                        @can('users.manage')
                        <a href="{{ route('users.edit', $user) }}" class="btn btn-xs btn-outline-primary mr-1"
                           title="Editar">
                            <i class="fas fa-edit"></i>
                        </a>

                        {{-- Desbloquear cuenta --}}
                        @if($user->isLocked() && $user->id !== auth()->id())
                        <form method="POST" action="{{ route('users.unlock', $user) }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-xs btn-warning mr-1" title="Desbloquear cuenta">
                                <i class="fas fa-unlock-alt"></i>
                            </button>
                        </form>
                        @endif

                        @if($user->id === auth()->id())
                            <button type="button" class="btn btn-xs btn-outline-secondary" disabled title="No puedes eliminar tu propia cuenta">
                                <i class="fas fa-trash"></i>
                            </button>
                        @else
                        <form method="POST" action="{{ route('users.destroy', $user) }}" class="d-inline"
                              onsubmit="return confirm('¿Eliminar al usuario {{ addslashes($user->name) }}? Esta acción no se puede deshacer.');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-xs btn-outline-danger" title="Eliminar">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                        @endif
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted py-5">
                        <i class="fas fa-users fa-2x mb-2 d-block"></i>
                        No se encontraron usuarios.
                        @if(request('q') || request('role'))
                            <br><a href="{{ route('users.index') }}">Ver todos los usuarios</a>
                        @endif
                    </td>
                </tr>
            @endforelse
            </tbody>
        </table>
    </div>
    @if($users->hasPages())
    <div class="card-footer border-top-0 bg-transparent">
        <div class="d-flex justify-content-between align-items-center">
            <small class="text-muted">
                Mostrando {{ $users->firstItem() }}–{{ $users->lastItem() }} de {{ $users->total() }} usuarios
            </small>
            {{ $users->links() }}
        </div>
    </div>
    @endif
</div>

@stop
