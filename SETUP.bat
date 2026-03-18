@echo off
echo ============================================================
echo  AXVOS INVENTORY - Setup Inicial
echo  Conecta. Controla. Traza.
echo ============================================================
echo.
echo IMPORTANTE: Asegurate de que XAMPP MySQL este corriendo.
echo.
pause

echo [1/4] Creando base de datos axvos_inventory...
"C:\xampp\mysql\bin\mysql.exe" -u root -e "CREATE DATABASE IF NOT EXISTS axvos_inventory CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
if %errorlevel% neq 0 (
    echo ERROR: No se pudo conectar a MySQL.
    echo Verifica que XAMPP MySQL este iniciado.
    pause
    exit /b 1
)
echo    OK - Base de datos creada.

echo.
echo [2/4] Ejecutando migraciones...
cd /d "C:\xampp\htdocs\inventario"
php artisan migrate --force
if %errorlevel% neq 0 (
    echo ERROR en migraciones. Revisa el log.
    pause
    exit /b 1
)
echo    OK - Migraciones completadas.

echo.
echo [3/4] Creando datos iniciales (roles, permisos, admin)...
php artisan db:seed --force
echo    OK - Datos iniciales creados.

echo.
echo [4/4] Limpiando cache...
php artisan config:clear
php artisan cache:clear
php artisan view:clear
echo    OK - Cache limpiada.

echo.
echo ============================================================
echo  SETUP COMPLETADO
echo ============================================================
echo.
echo  Acceso al sistema:
echo  URL:      http://localhost/inventario/public
echo  Usuario:  admin
echo  Password: admin123
echo.
echo  Recuerda cambiar la password del admin despues!
echo.
pause
