"""
vision.py — Procesamiento de visión por computadora.

Contiene funciones para extraer descriptores morfológicos de las
detecciones de YOLOv8 (máscaras de segmentación o bounding boxes)
y seleccionar la mejor detección entre múltiples candidatos.
"""

import math
import cv2
import numpy as np
from config import logger


# ─────────────────────────────────────────────────────────────────────────────
# Extracción de Morfología
# ─────────────────────────────────────────────────────────────────────────────

def extraer_morfologia_de_mascara(mascara: np.ndarray, bbox: np.ndarray,
                                  ancho_img: int, alto_img: int) -> dict:
    """Extrae descriptores morfológicos de la máscara de segmentación.

    Analiza el contorno más grande de la máscara binaria para obtener:
    solidez, compacidad, relación de aspecto, excentricidad, etc.

    Args:
        mascara:   Máscara binaria (uint8, 0/255).
        bbox:      Bounding box [x1, y1, x2, y2].
        ancho_img: Ancho de la imagen original (px).
        alto_img:  Alto de la imagen original (px).

    Returns:
        Diccionario con descriptores morfológicos o {'valido': False}.
    """
    resultado = {'valido': False}
    if mascara is None or mascara.size == 0:
        return resultado

    mascara_bin = (mascara > 0).astype(np.uint8)
    contornos, _ = cv2.findContours(mascara_bin, cv2.RETR_EXTERNAL,
                                    cv2.CHAIN_APPROX_SIMPLE)
    if not contornos:
        return resultado

    contorno = max(contornos, key=cv2.contourArea)
    area_px = cv2.contourArea(contorno)
    if area_px < 100:
        return resultado

    # Solidez = área / área del casco convexo
    casco = cv2.convexHull(contorno)
    area_casco_px = cv2.contourArea(casco)
    solidez = area_px / area_casco_px if area_casco_px > 0 else 0.0

    # Compacidad = (4π × área) / perímetro²
    perimetro_px = cv2.arcLength(contorno, closed=True)
    compacidad = (4.0 * math.pi * area_px) / (perimetro_px ** 2) if perimetro_px > 0 else 0.0

    # Dimensiones efectivas (por elipse ajustada o bbox)
    if len(contorno) >= 5:
        elipse = cv2.fitEllipse(contorno)
        (_cx, _cy), (eje_menor, eje_mayor), angulo = elipse
        longitud_px = max(eje_mayor, eje_menor)
        altura_px = min(eje_mayor, eje_menor)
        orientacion_deg = angulo
    else:
        x1, y1, x2, y2 = bbox
        longitud_px = float(x2 - x1)
        altura_px = float(y2 - y1)
        orientacion_deg = 0.0

    relacion_aspecto = longitud_px / altura_px if altura_px > 0 else 1.0

    # Excentricidad de la elipse
    a, b = longitud_px / 2.0, altura_px / 2.0
    excentricidad = math.sqrt(1.0 - (b ** 2) / (a ** 2)) if a > b > 0 else 0.0

    # Ancho máximo perpendicular al eje mayor (rotando el contorno)
    angulo_rad = math.radians(orientacion_deg)
    cos_a, sin_a = math.cos(-angulo_rad), math.sin(-angulo_rad)
    puntos = contorno.reshape(-1, 2).astype(np.float64)
    cx, cy = puntos[:, 0].mean(), puntos[:, 1].mean()
    puntos_centrados = puntos - np.array([cx, cy])
    rotados_y = -puntos_centrados[:, 0] * sin_a + puntos_centrados[:, 1] * cos_a
    ancho_max_px = float(rotados_y.max() - rotados_y.min())

    # Extensión = área del contorno / área del bbox
    x1, y1, x2, y2 = bbox
    area_bbox_px = float((x2 - x1) * (y2 - y1))
    extension = area_px / area_bbox_px if area_bbox_px > 0 else 0.0

    # Proporciones del bbox respecto a la imagen
    prop_alto_bbox = float(y2 - y1) / alto_img
    prop_ancho_bbox = float(x2 - x1) / ancho_img

    return {
        'valido':           True,
        'area_px':          float(area_px),
        'area_relativa':    float(area_px) / (ancho_img * alto_img),
        'area_casco_px':    float(area_casco_px),
        'solidez':          round(float(solidez), 4),
        'perimetro_px':     round(float(perimetro_px), 1),
        'compacidad':       round(float(compacidad), 4),
        'longitud_px':      round(float(longitud_px), 1),
        'altura_px':        round(float(altura_px), 1),
        'relacion_aspecto': round(float(relacion_aspecto), 4),
        'orientacion_deg':  round(float(orientacion_deg), 1),
        'ancho_max_px':     round(float(ancho_max_px), 1),
        'excentricidad':    round(float(excentricidad), 4),
        'extension':        round(float(extension), 4),
        'area_bbox_px':     round(area_bbox_px, 1),
        'prop_alto_bbox':   round(prop_alto_bbox, 4),
        'prop_ancho_bbox':  round(prop_ancho_bbox, 4),
    }


def extraer_morfologia_de_bbox(bbox: np.ndarray,
                               ancho_img: int, alto_img: int) -> dict:
    """Genera descriptores aproximados a partir del bounding box.

    Se usa como fallback cuando no hay máscara de segmentación disponible.
    Aproxima la silueta como una elipse inscrita en el bbox.
    """
    x1, y1, x2, y2 = bbox
    ancho_px, alto_px = float(x2 - x1), float(y2 - y1)

    if ancho_px <= 0 or alto_px <= 0:
        return {'valido': False}

    area_bbox_px = ancho_px * alto_px
    area_estimada = area_bbox_px * 0.70
    a, b = ancho_px / 2.0, alto_px / 2.0
    perimetro_estimado = math.pi * (3 * (a + b) - math.sqrt((3 * a + b) * (a + 3 * b)))

    if perimetro_estimado > 0:
        compacidad = round((4.0 * math.pi * area_estimada) / (perimetro_estimado ** 2), 4)
    else:
        compacidad = 0.30

    if max(a, b) > 0:
        excentricidad = round(math.sqrt(1.0 - (min(a, b) ** 2) / (max(a, b) ** 2)), 4)
    else:
        excentricidad = 0.0

    return {
        'valido':           True,
        'area_px':          area_estimada,
        'area_relativa':    area_estimada / (ancho_img * alto_img),
        'area_casco_px':    area_bbox_px,
        'solidez':          0.70,
        'perimetro_px':     round(perimetro_estimado, 1),
        'compacidad':       compacidad,
        'longitud_px':      max(ancho_px, alto_px),
        'altura_px':        min(ancho_px, alto_px),
        'relacion_aspecto': round(max(ancho_px, alto_px) / min(ancho_px, alto_px), 4),
        'orientacion_deg':  0.0,
        'ancho_max_px':     alto_px,
        'excentricidad':    excentricidad,
        'extension':        0.70,
        'area_bbox_px':     area_bbox_px,
        'prop_alto_bbox':   round(alto_px / alto_img, 4),
        'prop_ancho_bbox':  round(ancho_px / ancho_img, 4),
    }


# ─────────────────────────────────────────────────────────────────────────────
# Selección de Detección
# ─────────────────────────────────────────────────────────────────────────────

def seleccionar_mejor_deteccion(cajas, mascaras,
                                ancho_img: int, alto_img: int) -> int:
    """Selecciona la detección más adecuada para la estimación de peso.

    Puntúa cada detección combinando:
      - Confianza YOLO (40%)
      - Área relativa en la imagen (30%)
      - Completitud — que no esté recortada (20%)
      - Relación de aspecto lateral (10%)

    Retorna el índice de la mejor detección, o -1 si no hay ninguna.
    """
    if len(cajas) == 0:
        return -1

    area_imagen = ancho_img * alto_img
    MARGEN = 8
    mejor_idx = -1
    mejor_puntaje = -1.0

    for i in range(len(cajas)):
        xyxy = cajas[i].xyxy[0].cpu().numpy()
        confianza = float(cajas[i].conf[0])
        x1, y1, x2, y2 = xyxy

        # Área relativa (máscara si existe, sino bbox)
        if mascaras is not None and i < len(mascaras):
            mascara_i = mascaras[i].data.cpu().numpy()
            if mascara_i.ndim == 3:
                mascara_i = mascara_i[0]
            area_rel = float(np.sum(mascara_i > 0.5)) / area_imagen
        else:
            area_rel = ((x2 - x1) * (y2 - y1)) / area_imagen

        # Factor de área (ideal: 8%–55% de la imagen)
        if area_rel < 0.08:
            f_area = max(0.30, area_rel / 0.08)
        elif area_rel <= 0.55:
            f_area = 1.0
        else:
            f_area = max(0.55, 1.0 - (area_rel - 0.55) * 1.5)

        # Factor de completitud (penaliza bordes recortados)
        bordes = sum([
            x1 <= MARGEN,
            y1 <= MARGEN,
            x2 >= ancho_img - MARGEN,
            y2 >= alto_img - MARGEN,
        ])
        f_completitud = max(0.40, 1.0 - bordes * 0.20)

        # Factor de relación de aspecto (ideal: 1.35–1.75 para vista lateral)
        ra = (x2 - x1) / max(1, (y2 - y1))
        if 1.35 <= ra <= 1.75:
            f_ra = 1.0
        elif ra < 1.35:
            f_ra = max(0.50, ra / 1.35)
        else:
            f_ra = max(0.50, 1.0 - (ra - 1.75) * 0.20)

        puntaje = (
            confianza * 0.40
            + f_area * 0.30
            + f_completitud * 0.20
            + f_ra * 0.10
        )

        if puntaje > mejor_puntaje:
            mejor_puntaje = puntaje
            mejor_idx = i

    return mejor_idx
