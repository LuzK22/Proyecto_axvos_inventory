@extends('adminlte::page')

@section('title', 'Configuración del Sistema')

@section('content_header')
    <div class="d-flex justify-content-between align-items-center">
        <h1 class="m-0 font-weight-bold" style="color:#0d1b2a;">
            <i class="fas fa-cog mr-2" style="color:#00b4d8;"></i> Configuración del Sistema
        </h1>
        <a href="{{ route('admin.hub') }}" class="btn btn-sm btn-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Administración
        </a>
    </div>
@stop

@section('content')

@include('partials._alerts')

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show shadow-sm">
        <i class="fas fa-exclamation-circle mr-2"></i>
        <strong>Por favor corrige los siguientes errores:</strong>
        <ul class="mb-0 mt-1">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
@endif

<form method="POST" action="{{ route('admin.settings.update') }}" enctype="multipart/form-data">
@csrf
@method('PUT')

<div class="row">

    {{-- ── COLUMNA IZQUIERDA ───────────────────────────────────────────── --}}
    <div class="col-lg-8">

        {{-- ■ Datos de la Empresa ──────────────────────────────────────── --}}
        <div class="card card-outline shadow-sm mb-4" style="border-top-color:#00b4d8;" id="empresa">
            <div class="card-header" style="background:#f8f9fa;">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-building mr-2" style="color:#00b4d8;"></i> Datos de la Empresa
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label class="font-weight-bold">Nombre de la Empresa <span class="text-danger">*</span></label>
                            <input type="text" name="company_name" class="form-control @error('company_name') is-invalid @enderror"
                                   value="{{ old('company_name', $settings['company_name']->value ?? '') }}"
                                   placeholder="Ej: Mi Empresa S.A.S." required>
                            @error('company_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="font-weight-bold">NIT / Cédula</label>
                            <input type="text" name="company_nit" class="form-control @error('company_nit') is-invalid @enderror"
                                   value="{{ old('company_nit', $settings['company_nit']->value ?? '') }}"
                                   placeholder="Ej: 900.123.456-7">
                            @error('company_nit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-8">
                        <div class="form-group">
                            <label class="font-weight-bold">Dirección</label>
                            <input type="text" name="company_address" class="form-control"
                                   value="{{ old('company_address', $settings['company_address']->value ?? '') }}"
                                   placeholder="Ej: Calle 15 # 10-20, Bogotá">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label class="font-weight-bold">Teléfono</label>
                            <input type="text" name="company_phone" class="form-control"
                                   value="{{ old('company_phone', $settings['company_phone']->value ?? '') }}"
                                   placeholder="Ej: 601 234 5678">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label class="font-weight-bold">Correo Electrónico</label>
                    <input type="email" name="company_email" class="form-control @error('company_email') is-invalid @enderror"
                           value="{{ old('company_email', $settings['company_email']->value ?? '') }}"
                           placeholder="contacto@miempresa.com">
                    @error('company_email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

        {{-- ■ Configuración del Sistema ────────────────────────────────── --}}
        <div class="card card-outline shadow-sm mb-4" style="border-top-color:#3a57e8;" id="sistema">
            <div class="card-header" style="background:#f8f9fa;">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-sliders-h mr-2" style="color:#3a57e8;"></i> Sistema de Inventario
                </h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-7">
                        <div class="form-group">
                            <label class="font-weight-bold">Nombre del Sistema <span class="text-danger">*</span></label>
                            <input type="text" name="system_name" class="form-control @error('system_name') is-invalid @enderror"
                                   value="{{ old('system_name', $settings['system_name']->value ?? 'AXVOS Inventory') }}"
                                   required>
                            @error('system_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                    <div class="col-md-5">
                        <div class="form-group">
                            <label class="font-weight-bold">Eslogan</label>
                            <input type="text" name="system_slogan" class="form-control"
                                   value="{{ old('system_slogan', $settings['system_slogan']->value ?? '') }}"
                                   placeholder="Conecta. Controla. Traza.">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ■ Usuarios ─────────────────────────────────────────────────── --}}
        <div class="card card-outline shadow-sm mb-4" style="border-top-color:#6f42c1;" id="usuarios">
            <div class="card-header" style="background:#f8f9fa;">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-users mr-2" style="color:#6f42c1;"></i> Usuarios
                </h3>
            </div>
            <div class="card-body">
                <div class="form-group mb-0">
                    <label class="font-weight-bold">Dominios de correo permitidos</label>
                    <input type="text" name="user_email_domains" class="form-control"
                           value="{{ old('user_email_domains', $settings['user_email_domains']->value ?? 'axvos.local') }}"
                           placeholder="axvos.local,miempresa.com">
                    <small class="text-muted">
                        Dominios disponibles al crear usuarios. Separar con comas.
                        Ej: <code>axvos.local,miempresa.com</code>
                    </small>
                </div>
            </div>
        </div>

        {{-- ■ Plantillas de Actas ───────────────────────────────────────── --}}
        <div class="card card-outline shadow-sm mb-4" style="border-top-color:#28a745;" id="actas">
            <div class="card-header" style="background:#f8f9fa;">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-file-signature mr-2" style="color:#28a745;"></i> Plantillas de Actas
                </h3>
                <div class="card-tools">
                    <small class="text-muted">Configura textos y la plantilla Excel para actas</small>
                </div>
            </div>
            <div class="card-body">
                <div class="alert alert-light border d-flex justify-content-between align-items-center">
                    <div>
                        <strong>Plantilla Excel (.xlsx)</strong>
                        <div class="text-muted small">Administra plantillas separadas para TI, OTRO y MIXTA.</div>
                    </div>
                    <div class="btn-group">
                        <a href="{{ route('admin.acta-templates.ti') }}" class="btn btn-sm btn-primary">
                            <i class="fas fa-laptop mr-1"></i> TI
                        </a>
                        <a href="{{ route('admin.acta-templates.otro') }}" class="btn btn-sm" style="background:#7c3aed;color:#fff;">
                            <i class="fas fa-box mr-1"></i> OTRO
                        </a>
                        <a href="{{ route('admin.acta-templates.mixta') }}" class="btn btn-sm btn-success">
                            <i class="fas fa-layer-group mr-1"></i> MIXTA
                        </a>
                    </div>
                </div>
                <div class="form-group">
                    <label class="font-weight-bold">
                        <i class="fas fa-arrow-up mr-1 text-success"></i> Encabezado del Acta
                    </label>
                    <textarea name="acta_header_text" class="form-control" rows="4"
                              placeholder="Texto que aparece en la parte superior de las actas. Ej: ciudad, fecha, datos de la empresa..."
                              >{{ old('acta_header_text', $settings['acta_header_text']->value ?? '') }}</textarea>
                    <small class="text-muted">
                        Puedes usar variables: <code>@{{company_name}}</code>, <code>@{{company_nit}}</code>, <code>@{{fecha}}</code>
                    </small>
                </div>
                <div class="form-group mb-0">
                    <label class="font-weight-bold">
                        <i class="fas fa-arrow-down mr-1 text-danger"></i> Pie del Acta
                    </label>
                    <textarea name="acta_footer_text" class="form-control" rows="4"
                              placeholder="Texto al final del acta. Ej: cláusulas de responsabilidad, firma del colaborador..."
                              >{{ old('acta_footer_text', $settings['acta_footer_text']->value ?? '') }}</textarea>
                </div>
            </div>
        </div>

        {{-- ■ Seguridad 2FA ─────────────────────────────────────────────── --}}
        <div class="card card-outline shadow-sm mb-4" style="border-top-color:#dc3545;" id="seguridad">
            <div class="card-header" style="background:#f8f9fa;">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-shield-alt mr-2" style="color:#dc3545;"></i> Seguridad — Autenticación de Dos Factores (2FA)
                </h3>
            </div>
            <div class="card-body">

                {{-- Toggle global --}}
                <div class="form-group">
                    <div class="custom-control custom-switch">
                        <input type="checkbox" class="custom-control-input" id="security_2fa_enabled"
                               name="security_2fa_enabled" value="1"
                               {{ old('security_2fa_enabled', $settings['security_2fa_enabled']->value ?? '1') === '1' ? 'checked' : '' }}>
                        <label class="custom-control-label font-weight-bold" for="security_2fa_enabled">
                            Habilitar autenticación de dos factores (2FA)
                        </label>
                    </div>
                    <small class="text-muted d-block mt-1">
                        Al desactivar, <strong>ningún rol</strong> requerirá 2FA independientemente de la configuración inferior.
                    </small>
                </div>

                <hr>

                {{-- Roles que requieren 2FA --}}
                <div class="form-group">
                    <label class="font-weight-bold">Roles que requieren 2FA</label>
                    <small class="text-muted d-block mb-2">
                        Los usuarios de estos roles serán redirigidos a configurar o verificar 2FA al iniciar sesión.
                    </small>
                    @php
                        $currentRoles = array_map('trim', explode(',', $settings['security_2fa_required_roles']->value ?? 'Admin,Aprobador'));
                        $oldRoles     = old('security_2fa_required_roles', $currentRoles);
                    @endphp
                    <div class="row">
                        @foreach($roles as $role)
                            <div class="col-sm-6 col-md-4">
                                <div class="custom-control custom-checkbox mb-1">
                                    <input type="checkbox"
                                           class="custom-control-input"
                                           id="role_{{ $role }}"
                                           name="security_2fa_required_roles[]"
                                           value="{{ $role }}"
                                           {{ in_array($role, (array)$oldRoles) ? 'checked' : '' }}>
                                    <label class="custom-control-label" for="role_{{ $role }}">
                                        {{ $role }}
                                    </label>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    @error('security_2fa_required_roles')
                        <div class="text-danger small mt-1">{{ $message }}</div>
                    @enderror
                </div>

                <hr>

                {{-- Modo de aplicación --}}
                <div class="form-group">
                    <label class="font-weight-bold">Modo de aplicación</label>
                    @php $enforcement = old('security_2fa_enforcement', $settings['security_2fa_enforcement']->value ?? 'required'); @endphp
                    <div class="row mt-1">
                        <div class="col-sm-6">
                            <div class="custom-control custom-radio">
                                <input type="radio" class="custom-control-input" id="enforce_required"
                                       name="security_2fa_enforcement" value="required"
                                       {{ $enforcement === 'required' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="enforce_required">
                                    <strong>Obligatorio</strong>
                                    <small class="d-block text-muted">Bloquea acceso si no verifica 2FA</small>
                                </label>
                            </div>
                        </div>
                        <div class="col-sm-6">
                            <div class="custom-control custom-radio">
                                <input type="radio" class="custom-control-input" id="enforce_recommended"
                                       name="security_2fa_enforcement" value="recommended"
                                       {{ $enforcement === 'recommended' ? 'checked' : '' }}>
                                <label class="custom-control-label" for="enforce_recommended">
                                    <strong>Recomendado</strong>
                                    <small class="d-block text-muted">Muestra aviso pero permite el acceso</small>
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <hr>

                {{-- Días de gracia --}}
                <div class="form-group mb-0">
                    <label class="font-weight-bold">Días de gracia para configurar 2FA</label>
                    <div class="input-group" style="max-width:200px;">
                        <input type="number"
                               name="security_2fa_grace_days"
                               class="form-control @error('security_2fa_grace_days') is-invalid @enderror"
                               min="0" max="365"
                               value="{{ old('security_2fa_grace_days', $settings['security_2fa_grace_days']->value ?? '0') }}">
                        <div class="input-group-append">
                            <span class="input-group-text">días</span>
                        </div>
                        @error('security_2fa_grace_days')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                    <small class="text-muted">
                        Número de días desde la creación de la cuenta antes de exigir 2FA.
                        <strong>0</strong> = obligatorio desde el primer inicio de sesión.
                    </small>
                </div>

            </div>
        </div>

    </div>

    {{-- ── COLUMNA DERECHA ─────────────────────────────────────────────── --}}
    <div class="col-lg-4">

        {{-- ■ Logo de la Empresa ───────────────────────────────────────── --}}
        <div class="card card-outline shadow-sm mb-4" style="border-top-color:#fd7e14;" id="logo">
            <div class="card-header" style="background:#f8f9fa;">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-image mr-2" style="color:#fd7e14;"></i> Logo de la Empresa
                </h3>
            </div>
            <div class="card-body text-center">

                {{-- Vista previa actual --}}
                <div id="logoPreview" class="mb-3">
                    @if(!empty($settings['company_logo']->value))
                        <img src="{{ $settings['company_logo']->value }}"
                             alt="Logo actual" class="img-fluid rounded shadow-sm"
                             style="max-height:120px;">
                    @else
                        <div class="d-flex align-items-center justify-content-center rounded"
                             style="height:120px;background:#f1f3f5;border:2px dashed #dee2e6;">
                            <div class="text-muted text-center">
                                <i class="fas fa-image fa-2x d-block mb-1"></i>
                                <small>Sin logo</small>
                            </div>
                        </div>
                    @endif
                </div>

                <div class="custom-file mb-3">
                    <input type="file" class="custom-file-input" id="company_logo"
                           name="company_logo" accept="image/png,image/jpeg,image/svg+xml">
                    <label class="custom-file-label text-left" for="company_logo">
                        Seleccionar imagen...
                    </label>
                </div>

                <small class="text-muted d-block">
                    PNG, JPG o SVG &mdash; máx. 2 MB<br>
                    Recomendado: fondo transparente (PNG)
                </small>
            </div>
        </div>

        {{-- ■ Accesos rápidos de configuración ───────────────────────── --}}
        <div class="card shadow-sm mb-4">
            <div class="card-header" style="background:#f8f9fa;">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-link mr-2 text-secondary"></i> Navegación
                </h3>
            </div>
            <div class="list-group list-group-flush">
                <a href="#empresa" class="list-group-item list-group-item-action">
                    <i class="fas fa-building mr-2 text-info"></i> Datos de la empresa
                </a>
                <a href="#sistema" class="list-group-item list-group-item-action">
                    <i class="fas fa-sliders-h mr-2" style="color:#3a57e8;"></i> Sistema
                </a>
                <a href="#usuarios" class="list-group-item list-group-item-action">
                    <i class="fas fa-users mr-2" style="color:#6f42c1;"></i> Usuarios
                </a>
                <a href="#actas" class="list-group-item list-group-item-action">
                    <i class="fas fa-file-signature mr-2 text-success"></i> Plantillas de actas
                </a>
                <a href="#logo" class="list-group-item list-group-item-action">
                    <i class="fas fa-image mr-2" style="color:#fd7e14;"></i> Logo
                </a>
                <a href="#seguridad" class="list-group-item list-group-item-action">
                    <i class="fas fa-shield-alt mr-2 text-danger"></i> Seguridad 2FA
                </a>
            </div>
        </div>

        {{-- ■ Botón guardar (sticky) ───────────────────────────────────── --}}
        <div class="card shadow-sm" style="position:sticky;top:70px;">
            <div class="card-body p-3">
                <button type="submit" class="btn btn-block font-weight-bold"
                        style="background:linear-gradient(135deg,#0d1b2a,#1a3050);color:#fff;padding:12px;">
                    <i class="fas fa-save mr-2"></i> Guardar Configuración
                </button>
                <a href="{{ route('dashboard') }}" class="btn btn-block btn-outline-secondary btn-sm mt-2">
                    <i class="fas fa-times mr-1"></i> Cancelar
                </a>
            </div>
        </div>

    </div>
</div>

</form>

@stop

@section('js')
<script>
// Vista previa del logo al seleccionar archivo
document.getElementById('company_logo').addEventListener('change', function (e) {
    const file = e.target.files[0];
    if (!file) return;

    // Actualizar etiqueta
    e.target.nextElementSibling.textContent = file.name;

    // Previsualizar
    const reader = new FileReader();
    reader.onload = function (ev) {
        document.getElementById('logoPreview').innerHTML =
            '<img src="' + ev.target.result + '" class="img-fluid rounded shadow-sm" style="max-height:120px;" alt="Preview">';
    };
    reader.readAsDataURL(file);
});
</script>
@stop
