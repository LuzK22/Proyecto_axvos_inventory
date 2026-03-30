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

@section('js')
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const canvas = document.getElementById('profileSignCanvas');
    const signatureDataInput = document.getElementById('signature_data');
    const signatureTypeInput = document.getElementById('signature_type');
    const form = document.getElementById('signatureForm');
    const uploadInput = document.getElementById('profileSignUpload');
    const chooseBtn = document.getElementById('chooseSignImg');
    const previewWrap = document.getElementById('profileSignPreviewWrap');
    const previewImg = document.getElementById('profileSignPreview');

    if (!canvas || !form) return;

    const pad = new SignaturePad(canvas, { penColor: '#1e293b' });
    let uploadedDataUrl = null;

    function resizeCanvas() {
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        canvas.width = canvas.offsetWidth * ratio;
        canvas.height = 150 * ratio;
        canvas.getContext('2d').scale(ratio, ratio);
        pad.clear();
    }

    window.addEventListener('resize', resizeCanvas);
    resizeCanvas();

    document.getElementById('clearProfileSign')?.addEventListener('click', function () {
        pad.clear();
    });

    chooseBtn?.addEventListener('click', function () {
        uploadInput?.click();
    });

    uploadInput?.addEventListener('change', function () {
        const file = this.files && this.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = function (e) {
            uploadedDataUrl = e.target.result;
            previewImg.src = uploadedDataUrl;
            previewWrap.classList.remove('d-none');
        };
        reader.readAsDataURL(file);
    });

    form.addEventListener('submit', function (e) {
        const activeTab = document.querySelector('#signatureTabs .nav-link.active')?.getAttribute('href');
        if (activeTab === '#upload-sign-tab') {
            if (!uploadedDataUrl) {
                e.preventDefault();
                alert('Selecciona una imagen de firma.');
                return;
            }
            signatureTypeInput.value = 'image';
            signatureDataInput.value = uploadedDataUrl;
            return;
        }

        if (pad.isEmpty()) {
            e.preventDefault();
            alert('Dibuja tu firma antes de guardar.');
            return;
        }
        signatureTypeInput.value = 'drawn';
        signatureDataInput.value = pad.toDataURL('image/png');
    });
});
</script>
@stop

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
            @if(session('status') === 'signature-updated')
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-signature mr-2"></i> Firma base guardada correctamente.
                    <button type="button" class="close" data-dismiss="alert"><span>&times;</span></button>
                </div>
            @endif

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
                <div class="card-header bg-dark text-white">
                    <h5 class="mb-0"><i class="fas fa-signature mr-2"></i>Firma base para actas</h5>
                </div>
                <div class="card-body">
                    <p class="text-muted mb-3">
                        Guarda tu firma una sola vez. Cuando crees un acta como responsable, se pondra automaticamente.
                    </p>

                    @if(!empty($user->default_signature_data))
                        <div class="mb-3 p-2 border rounded text-center bg-light">
                            <small class="text-muted d-block mb-2">Firma actual guardada</small>
                            <img src="{{ $user->default_signature_data }}" alt="Firma actual" style="max-height:90px;max-width:100%;">
                        </div>
                    @endif

                    <form method="POST" action="{{ route('profile.signature.update') }}" id="signatureForm">
                        @csrf
                        <input type="hidden" name="signature_type" id="signature_type" value="drawn">
                        <input type="hidden" name="signature_data" id="signature_data">

                        <ul class="nav nav-tabs mb-3" id="signatureTabs">
                            <li class="nav-item">
                                <a class="nav-link active" data-toggle="tab" href="#draw-sign-tab">
                                    <i class="fas fa-pen mr-1"></i> Dibujar
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" data-toggle="tab" href="#upload-sign-tab">
                                    <i class="fas fa-upload mr-1"></i> Subir imagen
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="draw-sign-tab">
                                <canvas id="profileSignCanvas" height="150" style="width:100%;border:2px dashed #cbd5e1;border-radius:8px;background:#fafafa;"></canvas>
                                <div class="d-flex justify-content-between mt-2">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="clearProfileSign">
                                        <i class="fas fa-eraser mr-1"></i> Limpiar
                                    </button>
                                    <small class="text-muted align-self-center">Usa mouse o dedo</small>
                                </div>
                            </div>
                            <div class="tab-pane fade" id="upload-sign-tab">
                                <div class="border rounded p-3 text-center bg-light">
                                    <input type="file" id="profileSignUpload" accept="image/*" class="d-none">
                                    <button type="button" class="btn btn-outline-primary btn-sm" id="chooseSignImg">
                                        <i class="fas fa-image mr-1"></i> Seleccionar imagen
                                    </button>
                                    <div id="profileSignPreviewWrap" class="mt-3 d-none">
                                        <img id="profileSignPreview" src="" alt="Preview firma" style="max-height:120px;max-width:100%;">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-success mt-3" id="saveSignatureBtn">
                            <i class="fas fa-save mr-1"></i> Guardar firma base
                        </button>
                    </form>
                </div>
            </div>

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
