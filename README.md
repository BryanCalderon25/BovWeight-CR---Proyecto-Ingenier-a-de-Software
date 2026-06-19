# BovWeight CR 🐄🤖

Plataforma de precisión para la estimación de peso bovino utilizando Inteligencia Artificial, basada en arquitectura limpia y diseño *agrotech premium* estilo Stitch.

## 🏗️ Arquitectura del Proyecto

El proyecto está dividido en componentes principales orquestados para trabajar en conjunto:

1. **`mobile-app/` (Frontend Móvil)**
   - Framework: Ionic 8 + Vue 3 (Composition API)
   - Gestión de estado: Pinia
   - Componentes creados manualmente con CSS puro (variables CSS, glassmorphism) para coincidir exactamente con el diseño solicitado.
   
2. **`backend/` (API RESTful & Servidor Web)**
   - Framework: Laravel 12 (PHP 8.x)
   - Servidor Web: Nginx
   - Base de Datos: MySQL 8
   - Caché y Colas: Redis
   - Autenticación: Laravel Sanctum
   
3. **`ml-service/` (Microservicio de IA)**
   - Framework: Python Flask
   - Modelo: YOLOv8 (ultralytics) + OpenCV
   - Endpoint `/api/estimate` que recibe imágenes de Laravel y devuelve pesos simulados basados en detección de bounding boxes.

## 🚀 Guía de Instalación y Arranque Automático

Hemos simplificado el proceso de instalación y ejecución mediante un script automatizado para Windows.

### 1. Requisitos Previos
- Node.js 20+ y pnpm/npm
- PHP 8.3+ y Composer
- Docker Desktop (abierto y ejecutándose en segundo plano)
- Python 3.11+ (para el entorno local del ML si no se usa Docker)

### 2. Arranque Automático con un Clic (Recomendado para Windows)

Desde la raíz del proyecto, simplemente ejecuta el siguiente archivo:

```bat
iniciar_proyecto.bat
```

Este script inteligente se encargará automáticamente de:
1. Instalar las dependencias del frontend.
2. Configurar el backend de Laravel (instalación, entorno y claves).
3. Levantar toda la infraestructura en Docker (MySQL, Nginx, Redis, ML Service).
4. Abrir automáticamente ventanas de terminal con los servidores de desarrollo de Laravel (`php artisan serve`) y Vite (`pnpm run dev`).

¡Una vez que el script finalice, la aplicación estará lista para usarse!

### 3. Ejecución Manual (Alternativa)

Si prefieres levantar los servicios manualmente o estás en otro sistema operativo:

**Infraestructura Docker (Nginx, MySQL, Redis, ML Service):**
```bash
docker-compose up -d --build
```

**Frontend (Ionic App):**
```bash
cd mobile-app
npm install
npm run dev
```

**Backend (Laravel):**
```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

**Microservicio de IA (Python Local sin Docker):**
Es indispensable levantar este servicio para que las estimaciones de peso funcionen:
```bash
cd ml-service
pip install -r requirements.txt
python app.py
```

## 🤖 Ejecución y Despliegue en Android

El frontend móvil está desarrollado con **Ionic 8 + Capacitor**. Sigue estos pasos para compilar y ejecutar la aplicación en Android (emulador o dispositivo físico):

### 1. Requisitos Previos de Android
- Tener instalado **Android Studio**.
- Configurar la variable de entorno `ANDROID_HOME` y agregar las herramientas del SDK (`platform-tools`) al PATH de tu sistema.

### 2. Configurar el archivo `.env` del Frontend
Dado que el dispositivo móvil o emulador requiere comunicarse con el backend (Laravel) levantado en tu máquina local:
- **Para Emuladores de Android (AVD):**
  Usa la IP especial `10.0.2.2` que apunta al localhost de la máquina host.
  En [mobile-app/.env](file:///c:/Users/calde/Desktop/BovWeightCR/mobile-app/.env), configura:
  ```env
  VITE_API_URL=http://10.0.2.2:8000/api
  ```
- **Para Dispositivos Físicos:**
  Tu teléfono móvil y tu PC deben estar conectados a la **misma red Wi-Fi**. Configura la IP local de tu PC en [mobile-app/.env](file:///c:/Users/calde/Desktop/BovWeightCR/mobile-app/.env):
  ```env
  VITE_API_URL=http://<IP_DE_TU_PC>:8000/api
  ```
  *(Puedes obtener la IP de tu PC ejecutando `ipconfig` en la consola de Windows).*

### 3. Compilar y Sincronizar el Proyecto
Desde la raíz del proyecto, ejecuta:

```bash
# 1. Acceder al directorio del frontend
cd mobile-app

# 2. Generar la compilación web de producción
npm run build

# 3. Sincronizar el build y plugins con el proyecto nativo de Android
npm run cap:sync
```

### 4. Abrir y Ejecutar el Proyecto
- **Opción A: Desde Android Studio (Recomendado)**
  Abre el proyecto nativo ejecutando:
  ```bash
  npm run cap:open:android
  ```
  Esto abrirá **Android Studio** cargando la carpeta `mobile-app/android`. Una vez que se complete la sincronización de Gradle, selecciona tu dispositivo o emulador en la barra superior y haz clic en **Run (Play)**.

- **Opción B: Desde la Consola (CLI)**
  Puedes compilar e iniciar la app directamente ejecutando:
  ```bash
  npx cap run android
  ```


## 🎨 Notas sobre el Diseño Frontend

Se ha implementado **estrictamente** la línea visual proporcionada en las capturas de Stitch:
- Paleta de colores verde oliva/tierra (`#414833`, `#656D4A`, `#7F4F24`).
- Tipografías *Work Sans* (para encabezados/métricas) e *Inter* (para cuerpo).
- Efectos modernos: *Glassmorphism*, botones interactivos, skeleton loaders, y tarjetas redondeadas con sombras suaves.
- Todas las variables y clases CSS han sido escritas en español para mantener consistencia.
- Datos de simulación (Demos) incluidos en las Stores de Pinia para que puedas ver y navegar por la interfaz de usuario inmediatamente sin necesitar el backend corriendo.

## 🔒 Variables de Entorno (.env)

El archivo `.env` del backend debe apuntar al microservicio ML:
```env
ML_SERVICE_URL="http://localhost:5000/api/estimate"
```

## 📱 Flujo de la Aplicación
1. **Splash Screen / Onboarding**: Animaciones fluidas presentando la app.
2. **Login**: Acceso seguro.
3. **Inicio (Dashboard)**: Métricas clave, accesos rápidos y actividad reciente.
4. **Animales**: CRUD de ganado con barra de búsqueda rápida.
5. **Pesaje**: Selección de foto (o cámara nativa), que envía la imagen a Laravel, que a su vez consulta al microservicio ML en Python.
6. **Historial**: Gráficos Chart.js interactivos.
7. **Fincas / Reportes**: Vistas de gestión territorial y exportación a PDF.

## 😏 API's desde el navegador 

Enlace:
http://127.0.0.1:8000/docs/api#/