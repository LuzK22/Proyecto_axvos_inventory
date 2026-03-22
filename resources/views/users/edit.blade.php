@extends('adminlte::page')

@section('title', 'Editar Usuario')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0 font-weight-bold" style="color:#0d1b2a;">
            <i class="fas fa-user-edit mr-2" style="color:#00b4d8;"></i> Editar Usuario
        </h1>
        <a href="{{ route('users.index') }}" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Volver a Usuarios
        </a>
    </div>
@stop

@section('content')

@include('partials._alerts')

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show shadow-sm">
        <i class="fas fa-exclamation-circle mr-2"></i>
        <strong>Corrige los siguientes errores:</strong>
        <ul class="mb-0 mt-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
@endif

@php
    $rawDomains = \App\Models\Setting::get('user_email_domains', 'axvos.local');
    $domains = array_map('trim', explode(',', $rawDomains));
    $currentRole = $user->roles->first()?->name ?? '';
    $currentEmailPrefix = explode('@', $user->email)[0];
    $currentEmailDomain = str_contains($user->email, '@') ? explode('@', $user->email)[1] : '';
    $domainIsKnown = in_array($currentEmailDomain, $domains);
@endphp

<div class="row justify-content-center">
    <div class="col-lg-7">
        <div class="card card-outline shadow-sm" style="border-top-color:#00b4d8;">
            <div class="card-header" style="background:#f8f9fa;">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-id-card mr-2" style="color:#00b4d8;"></i>
                    Editando: <strong>{{ $user->name }}</strong>
                </h3>
            </div>
            <div class="card-body">
                <form method="POST" action="{{ route('users.update', $user) }}" id="editUserForm">
                    @csrf
                    @method('PUT')

                    {{-- Nombre completo --}}
                    <div class="form-group">
                        <label class="font-weight-bold">Nombre completo <span class="text-danger">*</span></label>
                        <input type="text" name="name"
                               class="form-control @error('name') is-invalid @enderror"
                               value="{{ old('name', $user->name) }}"
                               placeholder="Ej: Juan Pérez">
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- Email --}}
                    <div class="form-group">
                        <label class="font-weight-bold">Correo electrónico <span class="text-danger">*</span></label>
                        <div id="emailDomainMode" style="{{ !$domainIsKnown ? 'display:none;' : '' }}">
                            <div class="input-group">
                                <input type="text" id="emailPrefix" placeholder="usuario"
                                       class="form-control"
                                       value="{{ old('_email_prefix', $currentEmailPrefix) }}"
                                       autocomplete="off">
                                <div class="input-group-prepend input-group-append">
                                    <span class="input-group-text">@</span>
                                </div>
                                <select id="emailDomain" class="form-control" style="max-width:200px;">
                                    @foreach($domains as $domain)
                                        <option value="{{ $domain }}"
                                            {{ (old('email') ? str_ends_with(old('email'), '@'.$domain) : $currentEmailDomain === $domain) ? 'selected' : '' }}>
                                            {{ $domain }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <input type="hidden" name="email" id="emailHidden"
                                   value="{{ old('email', $user->email) }}">
                            @error('email')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
                        </div>
                        <div id="emailCustomMode" style="{{ $domainIsKnown ? 'display:none;' : '' }}">
                            <input type="email" id="emailCustom"
                                   class="form-control @error('email') is-invalid @enderror"
                                   value="{{ old('email', $user->email) }}"
                                   placeholder="usuario@dominio.com">
                        </div>
                        <div class="mt-1">
                            <a href="#" id="toggleEmailMode" class="small text-muted">
                                <i class="fas fa-toggle-{{ !$domainIsKnown ? 'on text-primary' : 'off' }} mr-1" id="toggleEmailIcon"></i>
                                <span id="toggleEmailLabel">{{ !$domainIsKnown ? 'Usar dominio predefinido' : 'Usar email personalizado' }}</span>
                            </a>
                        </div>
                    </div>

                    {{-- Nombre de usuario --}}
                    <div class="form-group">
                        <label class="font-weight-bold">Nombre de usuario <span class="text-danger">*</span></label>
                        <input type="text" name="username" id="username"
                               class="form-control @error('username') is-invalid @enderror"
                               value="{{ old('username', $user->username) }}"
                               placeholder="nombre de usuario">
                        <small class="text-muted">Solo letras, números y guiones.</small>
                        @error('username')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    {{-- Rol --}}
                    <div class="form-group">
                        <label class="font-weight-bold">Rol <span class="text-danger">*</span></label>
                        <select name="role" class="form-control @error('role') is-invalid @enderror">
                            <option value="">— Selecciona un rol —</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}"
                                    {{ (old('role', $currentRole) === $role->name) ? 'selected' : '' }}>
                                    {{ str_replace('_', ' ', $role->name) }}
                                </option>
                            @endforeach
                        </select>
                        @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <hr>

                    {{-- Contraseña (opcional) --}}
                    <div class="form-group">
                        <label class="font-weight-bold">
                            Nueva contraseña
                            <small class="text-muted font-weight-normal">(dejar vacío para no cambiar)</small>
                        </label>
                        <input type="password" name="password"
                               class="form-control @error('password') is-invalid @enderror"
                               placeholder="Dejar vacío para mantener la contraseña actual"
                               autocomplete="new-password">
                        <small class="text-muted">Si cambias la contraseña: mínimo 8 caracteres, mayúsculas y números.</small>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="form-group" id="passwordConfirmGroup">
                        <label class="font-weight-bold">Confirmar nueva contraseña</label>
                        <input type="password" name="password_confirmation"
                               class="form-control"
                               placeholder="Repite la nueva contraseña"
                               autocomplete="new-password">
                    </div>

                    <div class="d-flex justify-content-end mt-3">
                        <a href="{{ route('users.index') }}" class="btn btn-outline-secondary mr-2">
                            <i class="fas fa-times mr-1"></i> Cancelar
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-1"></i> Guardar Cambios
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@stop

@section('js')
<script>
(function () {
    var customMode = {{ !$domainIsKnown ? 'true' : 'false' }};

    function buildEmail() {
        if (!customMode) {
            var prefix = document.getElementById('emailPrefix').value.trim();
            var domain = document.getElementById('emailDomain').value;
            var full   = prefix && domain ? prefix + '@' + domain : '';
            document.getElementById('emailHidden').value = full;
        } else {
            var emailCustom = document.getElementById('emailCustom').value.trim();
            document.getElementById('emailHidden').value = emailCustom;
        }
    }

    document.getElementById('emailPrefix').addEventListener('input', buildEmail);
    document.getElementById('emailDomain').addEventListener('change', buildEmail);
    document.getElementById('emailCustom').addEventListener('input', buildEmail);

    document.getElementById('toggleEmailMode').addEventListener('click', function (e) {
        e.preventDefault();
        customMode = !customMode;
        document.getElementById('emailDomainMode').style.display = customMode ? 'none' : 'block';
        document.getElementById('emailCustomMode').style.display = customMode ? 'block' : 'none';
        document.getElementById('toggleEmailLabel').textContent  = customMode
            ? 'Usar dominio predefinido'
            : 'Usar email personalizado';
        document.getElementById('toggleEmailIcon').className = customMode
            ? 'fas fa-toggle-on mr-1 text-primary'
            : 'fas fa-toggle-off mr-1';

        if (customMode) {
            document.getElementById('emailCustom').setAttribute('name', 'email');
            document.getElementById('emailHidden').removeAttribute('name');
        } else {
            document.getElementById('emailCustom').removeAttribute('name');
            document.getElementById('emailHidden').setAttribute('name', 'email');
            buildEmail();
        }
    });

    document.getElementById('editUserForm').addEventListener('submit', function () {
        if (!customMode) buildEmail();
    });

    // Init
    buildEmail();
})();
</script>
@stop
