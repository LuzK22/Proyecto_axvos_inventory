@extends('adminlte::page')

@section('title', 'Mi Perfil')

@section('content_header')
    <div class="d-flex align-items-center">
        <div class="mr-3" style="width:48px;height:48px;border-radius:50%;background:linear-gradient(135deg,#00b4d8,#0077b6);
             display:flex;align-items:center;justify-content:center;font-size:1.3rem;color:#fff;font-weight:700;">
            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
        </div>
        <div>
            <h1 class="m-0" style="color:#0d1b2a;font-size:1.4rem;">
                {{ auth()->user()->name }}
            </h1>
            <small class="text-muted">
                {{ auth()->user()->email }}
                &nbsp;·&nbsp;
                {{ auth()->user()->roles->first()?->name ?? 'Sin rol' }}
                @if(auth()->user()->branch)
                    &nbsp;·&nbsp; {{ auth()->user()->branch->name }}
                @endif
            </small>
        </div>
    </div>
@endsection

@section('content')
<div class="container-fluid">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            {{-- Alertas --}}
            @if(session('status') === 'profile-updated')
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle mr-2"></i> Perfil actualizado correctamente.
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif
            @if(session('status') === 'password-updated')
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-key mr-2"></i> Contraseña actualizada correctamente.
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif

            {{-- Información del perfil --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-user mr-2"></i>Información del perfil</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('profile.update') }}">
                        @csrf
                        @method('patch')

                        <div class="form-group">
                            <label for="name" class="font-weight-bold">Nombre completo</label>
                            <input type="text" id="name" name="name"
                                   class="form-control @error('name') is-invalid @enderror"
                                   value="{{ old('name', $user->name) }}"
                                   required autofocus>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="email" class="font-weight-bold">Correo electrónico</label>
                            <input type="email" id="email" name="email"
                                   class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email', $user->email) }}"
                                   required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Info de solo lectura --}}
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="font-weight-bold text-muted">Rol asignado</label>
                                    <input type="text" class="form-control"
                                           value="{{ $user->roles->first()?->name ?? 'Sin rol' }}"
                                           readonly disabled>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="font-weight-bold text-muted">Sucursal</label>
                                    <input type="text" class="form-control"
                                           value="{{ $user->branch?->name ?? 'Sin sucursal' }}"
                                           readonly disabled>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="font-weight-bold text-muted">Miembro desde</label>
                                    <input type="text" class="form-control"
                                           value="{{ $user->created_at->format('d/m/Y') }}"
                                           readonly disabled>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Guardar cambios
                        </button>
                    </form>
                </div>
            </div>

            {{-- Cambiar contraseña --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-key mr-2"></i>Cambiar contraseña</h5>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('password.update') }}">
                        @csrf
                        @method('put')

                        <div class="form-group">
                            <label for="current_password" class="font-weight-bold">Contraseña actual</label>
                            <input type="password" id="current_password" name="current_password"
                                   class="form-control @error('current_password', 'updatePassword') is-invalid @enderror"
                                   autocomplete="current-password">
                            @error('current_password', 'updatePassword')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="password" class="font-weight-bold">Nueva contraseña</label>
                            <input type="password" id="password" name="password"
                                   class="form-control @error('password', 'updatePassword') is-invalid @enderror"
                                   autocomplete="new-password">
                            @error('password', 'updatePassword')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="password_confirmation" class="font-weight-bold">Confirmar nueva contraseña</label>
                            <input type="password" id="password_confirmation" name="password_confirmation"
                                   class="form-control"
                                   autocomplete="new-password">
                        </div>

                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-lock mr-1"></i> Actualizar contraseña
                        </button>
                    </form>
                </div>
            </div>

            {{-- Eliminación de cuenta --}}
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-user-slash mr-2"></i>Eliminación de cuenta
                    </h5>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-start">
                        <i class="fas fa-info-circle text-secondary mt-1 mr-3" style="font-size:1.1rem;"></i>
                        <div>
                            <strong class="d-block mb-1">Las cuentas no pueden eliminarse directamente.</strong>
                            <span class="text-muted">
                                Si necesitas eliminar tu cuenta, comunícate con el Administrador del sistema.
                                El proceso sigue los procedimientos de seguridad establecidos conforme a la
                                <strong>Ley 1581 de 2012</strong> (protección de datos personales en Colombia).
                            </span>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
