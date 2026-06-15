# ─────────────────────────────────────────────────────────────────────────────
# BovWeight CR — Microservicio ML (Producción)
# Pipeline: YOLOv8-Seg + Morfología + Ensamble Biométrico
# ─────────────────────────────────────────────────────────────────────────────
FROM python:3.11-slim

# Evitar la escritura de archivos .pyc y forzar salida sin búfer
ENV PYTHONDONTWRITEBYTECODE=1
ENV PYTHONUNBUFFERED=1

WORKDIR /app

# ── Capa 1: Dependencias del sistema operativo para OpenCV ──
RUN apt-get update && apt-get install -y --no-install-recommends \
    libglib2.0-0 \
    libsm6 \
    libxext6 \
    libxrender-dev \
    libgl1 \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# ── Capa 2: Dependencias de Python ──
COPY requirements.txt .
RUN --mount=type=cache,target=/root/.cache/pip \
    pip install --no-cache-dir -r requirements.txt

# ── Capa 3: Precarga de modelos YOLOv8 ──
# Descarga segmentación (primario) y detección (fallback).
RUN python -c "\
from ultralytics import YOLO; \
print('Descargando yolov8n-seg.pt...'); YOLO('yolov8n-seg.pt'); \
print('Descargando yolov8n.pt (fallback)...'); YOLO('yolov8n.pt'); \
print('Modelos listos.')"

# ── Capa 4: Código fuente ──
COPY . .

# ── Puerto expuesto ──
EXPOSE ${PORT:-5000}

# ── Variables de entorno ──
ENV PORT=5000
ENV WORKERS=2
ENV TIMEOUT=120

# ── Inicio: Gunicorn con preload ──
# --preload carga el modelo YOLO una vez en el proceso maestro
# y lo comparte con los workers mediante fork, ahorrando memoria.
CMD gunicorn \
    --bind "0.0.0.0:${PORT}" \
    --workers "${WORKERS}" \
    --timeout "${TIMEOUT}" \
    --preload \
    --access-logfile - \
    --error-logfile - \
    app:app
