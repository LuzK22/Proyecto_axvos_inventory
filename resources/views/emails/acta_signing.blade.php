<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Firma requerida — {{ $signature->acta->acta_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; background:#f4f6f9; margin:0; padding:0; }
        .wrapper { max-width: 600px; margin: 40px auto; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,.1); }
        .header { background: #1e3a8a; color: #fff; padding: 28px 32px; }
        .header h1 { margin: 0; font-size: 1.3rem; }
        .header p { margin: 6px 0 0; opacity: .85; font-size: .9rem; }
        .body { padding: 32px; color: #374151; }
        .body p { line-height: 1.7; margin: 0 0 14px; }
        .info-box { background: #f8fafc; border-left: 4px solid #1e3a8a; border-radius: 4px; padding: 14px 18px; margin: 20px 0; font-size: .9rem; }
        .info-box strong { display: block; margin-bottom: 6px; color: #1e3a8a; }
        .btn { display: inline-block; background: #1e3a8a; color: #fff !important; padding: 14px 32px; border-radius: 8px; text-decoration: none !important; font-weight: 700; font-size: 1rem; margin: 8px 0; }
        .btn:hover { background: #1d4ed8; }
        .footer { background: #f8fafc; padding: 18px 32px; font-size: .78rem; color: #9ca3af; border-top: 1px solid #e5e7eb; }
        .expires { font-size: .82rem; color: #6b7280; margin-top: 16px; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>📋 Firma Digital Requerida</h1>
        <p>{{ $signature->acta->acta_number }}</p>
    </div>
    <div class="body">
        <p>Estimado/a <strong>{{ $signature->signer_name }}</strong>,</p>

        <p>Se ha generado un acta de {{ $signature->acta->acta_type === 'entrega' ? 'entrega de activos' : ($signature->acta->acta_type === 'devolucion' ? 'devolución de activos' : 'reemplazo de activos') }} que requiere su firma digital.</p>

        <div class="info-box">
            <strong>Detalle del acta</strong>
            Número: <strong>{{ $signature->acta->acta_number }}</strong><br>
            Tipo: {{ ucfirst($signature->acta->acta_type) }}<br>
            Colaborador: {{ $signature->acta->assignment->collaborator->full_name ?? '—' }}<br>
            Fecha: {{ $signature->acta->created_at->format('d/m/Y') }}<br>
            Su rol: <strong>{{ $signature->role_label }}</strong>
        </div>

        <p>Para firmar el acta, haga clic en el botón a continuación. No necesita crear una cuenta ni iniciar sesión.</p>

        <p style="text-align:center; margin: 28px 0;">
            <a href="{{ url('/sign/' . $signature->token) }}" class="btn">
                ✍️ Firmar Acta Digital
            </a>
        </p>

        <p class="expires">
            ⏳ Este enlace es válido por 7 días
            @if($signature->token_expires_at)
                (hasta el {{ $signature->token_expires_at->format('d/m/Y H:i') }}).
            @endif
            Si tiene algún problema con el enlace, comuníquese con el responsable del inventario.
        </p>
    </div>
    <div class="footer">
        Sistema de Inventario &mdash; Este es un correo automático. No responda a este mensaje.
    </div>
</div>
</body>
</html>
