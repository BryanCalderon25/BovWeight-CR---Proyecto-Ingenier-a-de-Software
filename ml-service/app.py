"""
app.py — Servidor Flask del microservicio de estimación de peso bovino.

Expone:
  - GET  /api/health   → Estado del servicio.
  - POST /api/estimate → Estimación de peso a partir de una imagen.

Pipeline:
  1. Recibe imagen + metadatos (raza, género, edad).
  2. Detecta el bovino con YOLOv8 (segmentación o detección).
  3. Extrae morfología de la máscara/bbox.
  4. Estima peso con ensamble de fórmulas veterinarias.
  5. Calcula confianza y margen de error.
"""

import os
import time

import cv2
import numpy as np
from flask import Flask, request, jsonify
from ultralytics import YOLO

from config import logger, BIOMETRIA_ADULTA
from vision import (
    extraer_morfologia_de_mascara,
    extraer_morfologia_de_bbox,
    seleccionar_mejor_deteccion,
)
from estimadores import estimar_peso_ensemble
from biometria import calcular_confianza, calcular_margen_error

# ─────────────────────────────────────────────────────────────────────────────
# Inicialización
# ─────────────────────────────────────────────────────────────────────────────

app = Flask(__name__)
app.config['MAX_CONTENT_LENGTH'] = 16 * 1024 * 1024  # 16 MB

ID_CLASE_VACA = 19  # Clase "cow" en COCO
modelo = None
TIPO_MODELO = 'desconocido'

try:
    modelo = YOLO('yolov8n-seg.pt')
    TIPO_MODELO = 'seg'
    logger.info("Modelo YOLOv8n-Seg cargado exitosamente.")
except Exception:
    logger.warning("No se pudo cargar yolov8n-seg.pt; intentando yolov8n.pt.")
    try:
        modelo = YOLO('yolov8n.pt')
        TIPO_MODELO = 'det'
        logger.info("Modelo YOLOv8n cargado como fallback.")
    except Exception as e:
        logger.critical(f"No se pudo cargar ningún modelo YOLOv8: {e}")
        modelo = None


# ─────────────────────────────────────────────────────────────────────────────
# Rutas
# ─────────────────────────────────────────────────────────────────────────────

@app.route('/api/health', methods=['GET'])
def verificar_salud():
    """Endpoint de salud del servicio."""
    return jsonify({
        'status': 'ok',
        'service': 'BovWeight CR — Microservicio ML',
        'model_loaded': modelo is not None,
        'model_type': TIPO_MODELO,
    })


@app.route('/api/estimate', methods=['POST'])
def estimar_peso():
    """Endpoint principal: recibe imagen y devuelve estimación de peso."""
    inicio = time.time()

    # ── Validar imagen ──
    if 'image' not in request.files:
        return jsonify({'success': False, 'error': 'No se proporcionó imagen.'}), 400

    archivo = request.files['image']
    if not archivo.filename:
        return jsonify({'success': False, 'error': 'Nombre de archivo vacío.'}), 400

    # ── Extraer metadatos ──
    raza = request.form.get('raza', 'Desconocida')
    genero = request.form.get('genero', 'Hembra')
    if raza not in BIOMETRIA_ADULTA:
        raza = 'Desconocida'
    if genero not in ('Macho', 'Hembra'):
        genero = 'Hembra'

    try:
        valor_edad = request.form.get('edad_meses')
        edad_meses = int(valor_edad) if valor_edad and valor_edad.lower() not in ('null', 'none', '') else None
    except (ValueError, TypeError):
        edad_meses = None

    # Peso actual conocido (del backend) como referencia
    try:
        valor_peso = request.form.get('peso_actual')
        peso_actual_ref = float(valor_peso) if valor_peso and valor_peso.lower() not in ('null', 'none', '') else None
    except (ValueError, TypeError):
        peso_actual_ref = None

    edad_conocida = edad_meses is not None

    # ── Verificar modelo ──
    if modelo is None:
        return jsonify({'success': False, 'error': 'Modelo de detección no disponible.'}), 503

    try:
        # ── Decodificar imagen ──
        img_bytes = archivo.read()
        np_img = np.frombuffer(img_bytes, np.uint8)
        img = cv2.imdecode(np_img, cv2.IMREAD_COLOR)

        if img is None:
            return jsonify({'success': False, 'error': 'No se pudo decodificar la imagen.'}), 400

        alto_img, ancho_img = img.shape[:2]

        # ── Inferencia YOLO ──
        resultados = modelo(img, classes=[ID_CLASE_VACA], conf=0.35)

        if not resultados or len(resultados[0].boxes) == 0:
            return jsonify({
                'success': False,
                'error': 'No se detectó un bovino en la imagen.',
                'processing_time_ms': _ms_transcurridos(inicio),
                'num_detecciones': 0,
            }), 422

        tiene_mascara = (
            TIPO_MODELO == 'seg'
            and hasattr(resultados[0], 'masks')
            and resultados[0].masks is not None
        )
        mascaras = resultados[0].masks if tiene_mascara else None

        # ── Seleccionar mejor detección ──
        idx = seleccionar_mejor_deteccion(resultados[0].boxes, mascaras,
                                          ancho_img, alto_img)

        if idx < 0:
            return jsonify({
                'success': False,
                'error': 'Las detecciones no superan el umbral mínimo de calidad.',
                'processing_time_ms': _ms_transcurridos(inicio),
                'num_detecciones': len(resultados[0].boxes),
            }), 422

        mejor_caja = resultados[0].boxes[idx]
        confianza_yolo = float(mejor_caja.conf[0])
        bbox = mejor_caja.xyxy[0].cpu().numpy()

        # ── Extraer morfología ──
        if tiene_mascara and idx < len(mascaras):
            datos_mascara = mascaras[idx].data.cpu().numpy()
            if datos_mascara.ndim == 3:
                datos_mascara = datos_mascara[0]
            mascara_redim = cv2.resize(
                datos_mascara.astype(np.float32),
                (ancho_img, alto_img),
                interpolation=cv2.INTER_LINEAR,
            )
            mascara_bin = (mascara_redim > 0.5).astype(np.uint8) * 255
            morfo = extraer_morfologia_de_mascara(mascara_bin, bbox,
                                                  ancho_img, alto_img)
            fuente_morfo = 'segmentación'
        else:
            morfo = extraer_morfologia_de_bbox(bbox, ancho_img, alto_img)
            fuente_morfo = 'bbox'

        if not morfo.get('valido', False):
            return jsonify({
                'success': False,
                'error': 'No se pudieron extraer características morfológicas válidas.',
                'processing_time_ms': _ms_transcurridos(inicio),
                'num_detecciones': len(resultados[0].boxes),
            }), 422

        # ── Estimación de peso ──
        resultado_est = estimar_peso_ensemble(morfo, raza, genero, edad_meses)
        peso_estimado = resultado_est['peso_kg']

        confianza_pct = calcular_confianza(
            confianza_yolo, morfo, ancho_img, alto_img,
            edad_conocida, tiene_mascara,
        )
        margen_kg = calcular_margen_error(
            confianza_pct, morfo, ancho_img, alto_img,
            peso_estimado, edad_conocida, tiene_mascara,
        )

        t_total = _ms_transcurridos(inicio)

        logger.info(
            f"Resultado: {peso_estimado} kg ±{margen_kg} kg | "
            f"confianza={confianza_pct}% | método={resultado_est['metodo']}"
        )

        # ── Respuesta ──
        # NOTA: Las claves del JSON se mantienen para compatibilidad con el
        # backend Laravel (MLIntegrationController.php).
        return jsonify({
            'success':              True,
            'peso_estimado_kg':     peso_estimado,
            'margen_error_kg':      margen_kg,
            'confianza_porcentaje': confianza_pct,
            'raza_detectada':       raza,
            'genero':               genero,
            'edad_meses':           edad_meses,
            'biometria':            resultado_est.get('metricas', {}),
            'morfologia': {
                'fuente':           fuente_morfo,
                'solidez':          morfo.get('solidez'),
                'compacidad':       morfo.get('compacidad'),
                'relacion_aspecto': morfo.get('relacion_aspecto'),
                'excentricidad':    morfo.get('excentricidad'),
                'extension':        morfo.get('extension'),
            },
            'bbox':                 bbox.tolist(),
            'processing_time_ms':   t_total,
            'tipo_estimacion':      (
                f"YOLOv8-{'Seg' if tiene_mascara else 'Det'} + "
                f"{resultado_est['metodo']}"
            ),
            'is_fallback':          False,
            'num_detecciones':      len(resultados[0].boxes),
        })

    except Exception as e:
        logger.exception(f"Error inesperado: {e}")
        return jsonify({
            'success': False,
            'error':   f'Error interno: {str(e)}',
            'processing_time_ms': _ms_transcurridos(inicio),
        }), 500


# ─────────────────────────────────────────────────────────────────────────────
# Utilidades
# ─────────────────────────────────────────────────────────────────────────────

def _ms_transcurridos(inicio: float) -> int:
    """Retorna los milisegundos transcurridos desde `inicio`."""
    return int((time.time() - inicio) * 1000)


# ─────────────────────────────────────────────────────────────────────────────
# Punto de Entrada
# ─────────────────────────────────────────────────────────────────────────────

if __name__ == '__main__':
    puerto = int(os.environ.get('PORT', 5000))
    app.run(host='0.0.0.0', port=puerto, debug=False)