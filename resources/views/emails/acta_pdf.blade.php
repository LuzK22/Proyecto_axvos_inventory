<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acta PDF — {{ $acta->acta_number }}</title>
    <style>
        body { font-family: Arial, sans-serif; background:#f4f6f9; margin:0; padding:0; }
        .wrapper { max-width: 640px; margin: 40px auto; background: #fff; border-radius: 10px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,.08); }
        .header { background: #0f766e; color: #fff; padding: 22px 28px; }
        .header h1 { margin: 0; font-size: 1.2rem; }
        .body { padding: 28px; color: #374151; }
        .body p { line-height: 1.7; margin: 0 0 14px; }
        .info { background:#f8fafc; border-left: 4px solid #0f766e; border-radius: 6px; padding: 14px 16px; margin: 16px 0; font-size: .92rem; }
        .info strong { color:#0f766e; }
        .footer { background: #f8fafc; padding: 16px 28px; font-size: .78rem; color: #6b7280; border-top: 1px solid #e5e7eb; }
    </style>
</head>
<body>
<div class="wrapper">
    <div class="header">
        <h1>Acta {{ $acta->acta_number }}</h1>
    </div>
    <div class="body">
        <p>Se adjunta el <strong>PDF final</strong> del acta.</p>

        <div class="info">
            <div><strong>Tipo:</strong> {{ ucfirst($acta->acta_type) }}</div>
            <div><strong>Categoría:</strong> {{ $acta->asset_category }}</div>
            <div><strong>Asignación:</strong> #{{ $acta->assignment_id }}</div>
            <div><strong>Generada:</strong> {{ $acta->created_at?->format('d/m/Y H:i') }}</div>
        </div>

        <p>Si necesitas ajustes, por favor responde a la persona responsable del inventario.</p>
    </div>
    <div class="footer">
        Sistema de Inventario — correo automático.
    </div>
</div>
</body>
</html>

