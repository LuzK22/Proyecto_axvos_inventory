<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Política de Datos — {{ $company }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        :root {
            --navy: #0d1b2a;
            --cyan:  #00b4d8;
        }
        body {
            background: #ffffff;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            /* Barras laterales cyan */
            box-shadow: inset 8px 0 0 var(--cyan), inset -8px 0 0 var(--cyan);
        }
        .consent-card {
            background: #fff;
            border-radius: 16px;
            max-width: 760px;
            width: 100%;
            box-shadow: 0 8px 32px rgba(0,0,0,0.12);
            overflow: hidden;
            border: 1px solid #e9ecef;
        }
        .consent-header {
            background: var(--navy);
            color: #fff;
            padding: 2rem 2.5rem 1.5rem;
            border-bottom: 3px solid var(--cyan);
        }
        .consent-header .badge-version {
            background: var(--cyan);
            color: var(--navy);
            font-weight: 700;
            font-size: 0.7rem;
            padding: 3px 10px;
            border-radius: 20px;
            letter-spacing: 0.5px;
        }
        .consent-body {
            padding: 2rem 2.5rem;
            max-height: 55vh;
            overflow-y: auto;
        }
        .consent-body h5 {
            color: var(--navy);
            font-weight: 700;
            margin-top: 1.5rem;
        }
        .consent-footer {
            padding: 1.5rem 2.5rem 2rem;
            background: #f8f9fa;
            border-top: 1px solid #e9ecef;
        }
        .btn-accept {
            background: var(--cyan);
            color: var(--navy);
            font-weight: 700;
            border: none;
            padding: 0.65rem 2rem;
            border-radius: 8px;
            transition: opacity .2s;
        }
        .btn-accept:hover { opacity: 0.88; }
        .btn-accept:disabled { opacity: 0.5; cursor: not-allowed; }
        .law-ref {
            font-size: 0.78rem;
            color: #6c757d;
            background: #f0f9ff;
            border-left: 3px solid var(--cyan);
            padding: 0.5rem 0.75rem;
            border-radius: 0 6px 6px 0;
        }
        /* Scrollbar sutil */
        .consent-body::-webkit-scrollbar { width: 5px; }
        .consent-body::-webkit-scrollbar-track { background: #f1f1f1; }
        .consent-body::-webkit-scrollbar-thumb { background: var(--cyan); border-radius: 4px; }
    </style>
</head>
<body>

<div class="consent-card">

    {{-- ── CABECERA ── --}}
    <div class="consent-header">
        <div class="d-flex align-items-center gap-3 mb-1">
            <i class="fa-solid fa-shield-halved fa-2x" style="color: var(--cyan);"></i>
            <div>
                <h4 class="mb-0 fw-bold">Política de Tratamiento de Datos</h4>
                <small class="text-muted">AXVOS INVENTORY</small>
            </div>
            <span class="badge-version ms-auto">v{{ $version }}</span>
        </div>
        <p class="text-muted small mb-0 mt-2">
            Para continuar usando el sistema debes aceptar el tratamiento de tus datos personales
            conforme a la Ley 1581 de 2012 (Colombia).
        </p>
    </div>

    {{-- ── ALERTA FLASH ── --}}
    @if(session('info'))
        <div class="alert alert-info rounded-0 mb-0 py-2 px-4 small">
            <i class="fa fa-info-circle me-1"></i> {{ session('info') }}
        </div>
    @endif

    {{-- ── CONTENIDO POLÍTICA ── --}}
    <div class="consent-body">

        <div class="law-ref mb-3">
            <strong>Marco legal:</strong> Ley 1581/2012 · Decreto 1377/2013 · SIC Colombia
        </div>

        <h5><i class="fa fa-building me-2 text-primary"></i>1. Responsable del tratamiento</h5>
        <p>
            <strong>AXVOS INVENTORY</strong> actúa como responsable del tratamiento de sus datos personales.
            Los datos recopilados en este sistema de inventario serán tratados exclusivamente para
            las finalidades descritas en esta política.
        </p>

        <h5><i class="fa fa-database me-2 text-primary"></i>2. Datos que tratamos</h5>
        <ul>
            <li>Datos de identificación: nombre, cédula, correo electrónico, cargo, área.</li>
            <li>Datos de acceso: nombre de usuario, contraseña (cifrada), registros de sesión.</li>
            <li>Datos de trazabilidad: dirección IP, navegador, fecha y hora de actividad.</li>
            <li>Datos de asignación: activos asignados, préstamos, historial de devoluciones.</li>
        </ul>

        <h5><i class="fa fa-bullseye me-2 text-primary"></i>3. Finalidades del tratamiento</h5>
        <ul>
            <li>Gestión y control del inventario de activos de la organización.</li>
            <li>Generación de actas de entrega, préstamo y devolución.</li>
            <li>Auditoría y cumplimiento normativo (ISO 27001, NIIF).</li>
            <li>Control de acceso y seguridad del sistema.</li>
            <li>Reportes internos de gestión de activos.</li>
        </ul>

        <h5><i class="fa fa-lock me-2 text-primary"></i>4. Seguridad de la información</h5>
        <p>
            Aplicamos medidas técnicas y organizativas conforme a ISO 27001:
            cifrado en reposo y tránsito, control de acceso por roles, auditoría de actividad,
            bloqueo por intentos fallidos y autenticación de dos factores (2FA).
        </p>

        <h5><i class="fa fa-user-shield me-2 text-primary"></i>5. Derechos del titular (Art. 8 Ley 1581)</h5>
        <p>Como titular de datos personales usted tiene derecho a:</p>
        <ul>
            <li><strong>Conocer</strong> los datos que tenemos sobre usted.</li>
            <li><strong>Actualizar</strong> sus datos cuando estén desactualizados.</li>
            <li><strong>Rectificar</strong> datos inexactos o incompletos.</li>
            <li><strong>Suprimir</strong> sus datos cuando no sean necesarios (derecho al olvido).</li>
            <li><strong>Revocar</strong> el consentimiento otorgado en cualquier momento.</li>
        </ul>
        <p class="small text-muted">
            Para ejercer sus derechos, contacte al administrador del sistema o al oficial de privacidad de su organización.
        </p>

        <h5><i class="fa fa-clock me-2 text-primary"></i>6. Vigencia y retención</h5>
        <p>
            Sus datos serán conservados durante la vigencia de la relación laboral/contractual y
            por el período adicional exigido por la normativa colombiana aplicable.
        </p>

        <h5><i class="fa fa-share-nodes me-2 text-primary"></i>7. Transferencia de datos</h5>
        <p>
            Los datos <strong>no serán vendidos ni cedidos</strong> a terceros sin autorización expresa.
            Únicamente podrán ser compartidos con entidades reguladoras cuando exista obligación legal.
        </p>

    </div>

    {{-- ── PIE — ACEPTACIÓN ── --}}
    <div class="consent-footer">
        <form method="POST" action="{{ route('consent.accept') }}" id="consentForm">
            @csrf

            @error('accepted')
                <div class="alert alert-danger py-2 small mb-3">
                    <i class="fa fa-triangle-exclamation me-1"></i>{{ $message }}
                </div>
            @enderror

            <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" name="accepted" id="acceptCheck"
                       value="1" {{ old('accepted') ? 'checked' : '' }}
                       onchange="document.getElementById('btnAccept').disabled = !this.checked">
                <label class="form-check-label fw-semibold" for="acceptCheck">
                    He leído y acepto la <strong>Política de Tratamiento de Datos Personales</strong>
                    de AXVOS INVENTORY (versión {{ $version }}).
                </label>
            </div>

            <div class="d-flex align-items-center gap-3">
                <button type="submit" class="btn btn-accept" id="btnAccept" disabled>
                    <i class="fa fa-check me-2"></i>Acepto y continúo
                </button>
                <a href="{{ route('logout') }}" class="text-muted small"
                   onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                    No acepto — cerrar sesión
                </a>
            </div>
        </form>

        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
            @csrf
        </form>

        <p class="text-muted small mt-3 mb-0">
            <i class="fa fa-info-circle me-1"></i>
            Al aceptar queda un registro de su consentimiento con fecha, hora e IP según Ley 1581/2012.
            Este registro tiene valor legal.
        </p>
    </div>

</div>

</body>
</html>
