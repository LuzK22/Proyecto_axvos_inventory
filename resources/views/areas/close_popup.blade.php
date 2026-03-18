<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Área creada</title>
    <style>
        body { font-family: sans-serif; display: flex; align-items: center; justify-content: center;
               min-height: 100vh; margin: 0; background: #f4f6f9; }
        .box { text-align: center; padding: 2rem; }
        .icon { font-size: 2.5rem; color: #7c3aed; margin-bottom: .5rem; }
        .msg { color: #444; font-size: .95rem; }
    </style>
</head>
<body>
<div class="box">
    <div class="icon">✓</div>
    <p class="msg">Área creada correctamente.<br>Puedes cerrar esta ventana.</p>
    <script>
        // Auto-close after brief delay, then try to reload opener
        setTimeout(function() {
            if (window.opener && !window.opener.closed) {
                // Trigger select2 / select refresh if available
                try { window.opener.location.reload(); } catch(e) {}
            }
            window.close();
        }, 1200);
    </script>
</div>
</body>
</html>
