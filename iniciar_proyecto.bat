@echo off
color 0A
echo =======================================================
echo          INICIALIZANDO BOVWEIGHT CR (AUTO-SETUP)
echo =======================================================
echo.
echo Este script configurara automaticamente todo el sistema.
echo Asegurate de tener instalado Node.js, Composer, PHP y Docker.
echo.
pause

echo.
echo [1/3] INSTALANDO DEPENDENCIAS FRONTEND (MOBILE-APP)...
echo -------------------------------------------------------
cd mobile-app
call npm install
cd ..
echo Frontend listo.
echo.

echo [2/3] CONFIGURANDO BACKEND (LARAVEL)...
echo -------------------------------------------------------
REM Hacemos un respaldo de nuestro codigo customizado
move backend backend_custom
echo Creando proyecto base Laravel (esto puede tardar unos minutos)...
call composer create-project laravel/laravel backend
echo Integrando codigo customizado de BovWeight CR...
xcopy /Y /S backend_custom\* backend\
rd /S /Q backend_custom

cd backend
echo Configurando entorno...
copy .env.example .env
call php artisan key:generate
cd ..
echo Backend listo.
echo.

echo [3/3] LEVANTANDO SERVICIOS DOCKER (BD MySQL e IA Python)...
echo -------------------------------------------------------
echo Asegurate de que Docker Desktop este abierto.
docker-compose up -d --build
echo.

echo =======================================================
echo                   TODO LISTO! 🎉
echo =======================================================
echo.
echo Para ver la aplicacion movil, abre una nueva terminal y ejecuta:
echo    cd mobile-app
echo    npm run dev
echo.
echo Presiona cualquier tecla para salir...
pause >nul
