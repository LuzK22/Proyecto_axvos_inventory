<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title>Firmar Acta — {{ $signature->acta->acta_number }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body { background: #f4f6f9; font-family: 'Segoe UI', Arial, sans-serif; }
        .sign-wrapper { max-width: 680px; margin: 30px auto; padding: 0 16px 40px; }
        .acta-header { background: #1e3a8a; color: #fff; border-radius: 10px 10px 0 0; padding: 24px 28px; }
        .acta-header h1 { font-size: 1.2rem; margin: 0; }
        .acta-header p { margin: 4px 0 0; opacity: .85; font-size: .88rem; }
        .acta-body { background: #fff; border-radius: 0 0 10px 10px; box-shadow: 0 4px 18px rgba(0,0,0,.08); padding: 28px; }
        .info-row { display: flex; justify-content: space-between; font-size: .88rem; margin-bottom: 6px; }
        .info-row span:first-child { color: #6b7280; }
        .assets-table { font-size: .82rem; }
        .sign-tabs .nav-link { font-size: .85rem; }
        #signCanvas { width: 100%; touch-action: none; cursor: crosshair; display: block;
                      border: 2px dashed #cbd5e1; border-radius: 8px; background: #fafafa; }
        #signCanvas.signing { border-color: #1e3a8a; }
        .btn-sign { background: #0f766e; border: none; color: #fff; font-size: 1rem; padding: 12px 30px; border-radius: 8px; width: 100%; }
        .btn-sign:hover { background: #0d9488; color: #fff; }
        .success-box { text-align: center; padding: 40px 20px; }
        .success-box i { font-size: 3.5rem; color: #0f766e; }
    </style>
</head>
<body>

<div class="sign-wrapper">

    {{-- Cabecera --}}
    <div class="acta-header">
        <h1><i class="fas fa-file-signature mr-2"></i>Firma Digital de Acta</h1>
        <p>{{ $signature->acta->acta_number }} &middot; {{ ucfirst($signature->acta->acta_type) }}</p>
    </div>

    <div class="acta-body">

        {{-- Información del acta --}}
        <div class="mb-3 p-3 rounded" style="background:#f8fafc;border-left:4px solid #1e3a8a;">
            <div class="info-row"><span>Colaborador</span><strong>{{ $signature->acta->assignment->collaborator->full_name ?? '—' }}</strong></div>
            <div class="info-row"><span>Fecha del acta</span><strong>{{ $signature->acta->created_at->format('d \d\e F \d\e Y') }}</strong></div>
            <div class="info-row"><span>Su rol</span><strong>{{ $signature->role_label }}</strong></div>
            <div class="info-row"><span>Firmante</span><strong>{{ $signature->signer_name }}</strong></div>
        </div>

        {{-- Activos incluidos --}}
        <h6 class="font-weight-bold mb-2" style="font-size:.85rem;color:#374151;">
            <i class="fas fa-laptop mr-1"></i> Activos incluidos en esta acta
        </h6>
        <table class="table table-sm assets-table mb-4">
            <thead class="thead-light">
                <tr>
                    <th>#</th>
                    <th>Código</th>
                    <th>Tipo</th>
                    <th>Marca / Modelo</th>
                    <th>Serial</th>
                </tr>
            </thead>
            <tbody>
                @forelse($signature->acta->assignment->activeAssets as $i => $aa)
                <tr>
                    <td class="text-muted">{{ $i + 1 }}</td>
                    <td><code>{{ $aa->asset->asset_code ?? '—' }}</code></td>
                    <td>{{ $aa->asset->assetType->name ?? '—' }}</td>
                    <td>{{ trim(($aa->asset->brand ?? '') . ' ' . ($aa->asset->model ?? '')) ?: '—' }}</td>
                    <td class="text-muted">{{ $aa->asset->serial ?? '—' }}</td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted">Sin activos</td></tr>
                @endforelse
            </tbody>
        </table>

        {{-- Firma --}}
        <div id="signSection">
            <h6 class="font-weight-bold mb-3" style="font-size:.85rem;color:#374151;">
                <i class="fas fa-signature mr-1"></i> Su firma digital
            </h6>

            {{-- Tabs: Dibujar / Subir imagen --}}
            <ul class="nav nav-tabs sign-tabs mb-3" id="signTabs">
                <li class="nav-item">
                    <a class="nav-link active" data-toggle="tab" href="#tabDraw">
                        <i class="fas fa-pen mr-1"></i> Dibujar firma
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-toggle="tab" href="#tabUpload">
                        <i class="fas fa-upload mr-1"></i> Subir imagen
                    </a>
                </li>
            </ul>

            <div class="tab-content">
                {{-- Tab Dibujar --}}
                <div class="tab-pane fade show active" id="tabDraw">
                    <canvas id="signCanvas" height="150"></canvas>
                    <div class="d-flex justify-content-between mt-2 mb-3">
                        <button type="button" id="clearBtn" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-eraser mr-1"></i> Limpiar
                        </button>
                        <small class="text-muted align-self-center">Use el mouse o su dedo en móvil</small>
                    </div>
                </div>
                {{-- Tab Subir imagen --}}
                <div class="tab-pane fade" id="tabUpload">
                    <div class="text-center p-4 rounded mb-3" style="border:2px dashed #cbd5e1;background:#fafafa;">
                        <label for="signImgInput" class="mb-0" style="cursor:pointer;">
                            <i class="fas fa-image fa-2x text-secondary d-block mb-2"></i>
                            <span class="text-muted">Haga clic para seleccionar una imagen de firma</span>
                        </label>
                        <input type="file" id="signImgInput" accept="image/*" class="d-none">
                    </div>
                    <div id="imgPreview" class="text-center d-none mb-3">
                        <img id="imgPreviewEl" src="" alt="Firma" style="max-height:120px;border:1px solid #dee2e6;border-radius:6px;">
                    </div>
                </div>
            </div>

            {{-- Declaración + botón --}}
            <div class="alert alert-light border" style="font-size:.82rem;">
                <i class="fas fa-info-circle text-muted mr-1"></i>
                Al firmar, declaro haber recibido conforme los activos listados y acepto las condiciones de uso y responsabilidad establecidas por la empresa.
            </div>

            <button type="button" id="submitSignBtn" class="btn-sign mt-1">
                <i class="fas fa-check-circle mr-2"></i> Confirmar y Firmar
            </button>
        </div>

        {{-- Mensaje de éxito (oculto) --}}
        <div id="successSection" class="success-box d-none">
            <i class="fas fa-check-circle"></i>
            <h4 class="mt-3 font-weight-bold" style="color:#1e293b;">¡Acta firmada correctamente!</h4>
            <p class="text-muted mt-2">
                Su firma ha sido registrada. Puede cerrar esta ventana.<br>
                Se notificará al responsable del inventario.
            </p>
        </div>

    </div>
</div>

<script src="https://code.jquery.com/jquery-3.6.4.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.1.7/dist/signature_pad.umd.min.js"></script>
<script>
(function () {
    const canvas = document.getElementById('signCanvas');
    const pad    = new SignaturePad(canvas, { penColor: '#1e293b' });

    // Resize HiDPI
    function resizeCanvas() {
        const ratio = Math.max(window.devicePixelRatio || 1, 1);
        canvas.width  = canvas.offsetWidth * ratio;
        canvas.height = 150 * ratio;
        canvas.getContext('2d').scale(ratio, ratio);
        pad.clear();
    }
    window.addEventListener('resize', resizeCanvas);
    resizeCanvas();

    canvas.addEventListener('mousedown', () => canvas.classList.add('signing'));
    canvas.addEventListener('touchstart', () => canvas.classList.add('signing'));

    document.getElementById('clearBtn').addEventListener('click', () => {
        pad.clear();
        canvas.classList.remove('signing');
    });

    // Image upload preview
    let uploadedDataUrl = null;
    document.getElementById('signImgInput').addEventListener('change', function() {
        const file = this.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = e => {
            uploadedDataUrl = e.target.result;
            document.getElementById('imgPreviewEl').src = uploadedDataUrl;
            document.getElementById('imgPreview').classList.remove('d-none');
        };
        reader.readAsDataURL(file);
    });

    // Determine active tab
    function getActiveTab() {
        return document.querySelector('#signTabs .nav-link.active').getAttribute('href');
    }

    // Submit
    document.getElementById('submitSignBtn').addEventListener('click', function () {
        const tab = getActiveTab();
        let signatureType, signatureData;

        if (tab === '#tabDraw') {
            if (pad.isEmpty()) {
                alert('Por favor dibuje su firma antes de confirmar.');
                return;
            }
            signatureType = 'drawn';
            signatureData = pad.toDataURL('image/png');
        } else {
            if (!uploadedDataUrl) {
                alert('Por favor seleccione una imagen de firma.');
                return;
            }
            signatureType = 'image';
            signatureData = uploadedDataUrl;
        }

        this.disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Guardando...';

        fetch('{{ url("/sign/" . $signature->token) }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            body: JSON.stringify({ signature_type: signatureType, signature_data: signatureData }),
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('signSection').classList.add('d-none');
                document.getElementById('successSection').classList.remove('d-none');
            } else {
                alert(data.error || 'Error al guardar la firma.');
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-check-circle mr-2"></i> Confirmar y Firmar';
            }
        })
        .catch(() => {
            alert('Error de conexión. Intente nuevamente.');
            this.disabled = false;
            this.innerHTML = '<i class="fas fa-check-circle mr-2"></i> Confirmar y Firmar';
        });
    });
})();
</script>
</body>
</html>
