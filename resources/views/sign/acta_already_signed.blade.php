<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Acta ya firmada</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css">
    <style>
        body { background:#f4f6f9; display:flex; align-items:center; justify-content:center; min-height:100vh; }
        .box { background:#fff; border-radius:12px; padding:48px 40px; max-width:480px; text-align:center; box-shadow:0 4px 20px rgba(0,0,0,.08); }
        .box i { font-size:3rem; color:#0f766e; }
    </style>
</head>
<body>
    <div class="box">
        <i class="fas fa-check-circle mb-3"></i>
        <h4 class="font-weight-bold">Este acta ya fue firmada</h4>
        <p class="text-muted mt-2">
            La firma de <strong>{{ $signature->signer_name }}</strong> ya fue registrada el
            {{ $signature->signed_at?->format('d/m/Y H:i') }}.
        </p>
        <p class="text-muted small">Puede cerrar esta ventana.</p>
    </div>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/js/all.min.js"></script>
</body>
</html>
