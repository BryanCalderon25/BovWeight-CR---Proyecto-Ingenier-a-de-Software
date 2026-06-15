"""
biometria.py — Funciones de biometría bovina.

Contiene:
  - Interpolación de curva de crecimiento por edad.
  - Estimación de fracción de crecimiento por morfología.
  - Corrección de perspectiva.
  - Cálculo de confianza y margen de error.
"""

import math
from config import CURVA_CRECIMIENTO


# ─────────────────────────────────────────────────────────────────────────────
# Fracción de Crecimiento
# ─────────────────────────────────────────────────────────────────────────────

def fraccion_crecimiento_por_edad(edad_meses: int) -> float:
    """Interpola la fracción de desarrollo (0.0–1.0) según la edad en meses.

    Utiliza interpolación lineal entre los puntos definidos en
    CURVA_CRECIMIENTO (config.py).
    """
    if edad_meses <= 0:
        return CURVA_CRECIMIENTO[0][1]
    if edad_meses >= CURVA_CRECIMIENTO[-1][0]:
        return 1.0

    for i in range(len(CURVA_CRECIMIENTO) - 1):
        m0, f0 = CURVA_CRECIMIENTO[i]
        m1, f1 = CURVA_CRECIMIENTO[i + 1]
        if m0 <= edad_meses < m1:
            t = (edad_meses - m0) / (m1 - m0)
            return f0 + t * (f1 - f0)

    return 1.0


def estimar_fraccion_crecimiento_por_morfo(morfo: dict) -> float:
    """Estima la fracción de desarrollo (0.0–1.0) usando el bbox y solidez.

    Se usa cuando no se conoce la edad del animal. Combina la proporción
    del bounding box en la imagen con la solidez de la silueta.
    """
    indice = (morfo.get('prop_alto_bbox', 0.2) + morfo.get('prop_ancho_bbox', 0.3)) / 2.0

    if indice < 0.12:
        fraccion = 0.62 + (indice / 0.12) * 0.10
    elif indice < 0.22:
        fraccion = 0.72 + ((indice - 0.12) / 0.10) * 0.16
    elif indice < 0.40:
        fraccion = 0.88 + ((indice - 0.22) / 0.18) * 0.12
    else:
        fraccion = 1.00

    solidez = morfo.get('solidez', 0.75)
    if solidez < 0.70:
        fraccion *= 0.96
    elif solidez > 0.83:
        fraccion = min(1.0, fraccion * 1.02)

    return min(1.0, max(0.55, fraccion))


# ─────────────────────────────────────────────────────────────────────────────
# Corrección de Perspectiva
# ─────────────────────────────────────────────────────────────────────────────

def factor_correccion_perspectiva(relacion_aspecto: float) -> float:
    """Corrige la longitud aparente por efecto de perspectiva.

    Cuando la relación de aspecto está fuera del rango ideal (1.35–1.75),
    se aplica un factor corrector que compensa el escorzo.
    """
    AR_MIN, AR_MAX, AR_MED = 1.35, 1.75, 1.55

    if AR_MIN <= relacion_aspecto <= AR_MAX:
        return 1.0
    elif relacion_aspecto < AR_MIN:
        cos_theta = max(0.40, relacion_aspecto / AR_MED)
        return min(1.55, 1.0 / cos_theta)
    else:
        exceso = relacion_aspecto - AR_MAX
        return max(0.88, 1.0 - exceso * 0.12)


# ─────────────────────────────────────────────────────────────────────────────
# Confianza y Margen de Error
# ─────────────────────────────────────────────────────────────────────────────

def calcular_confianza(confianza_yolo: float, morfo: dict,
                       ancho_img: int, alto_img: int,
                       edad_conocida: bool = False,
                       tiene_mascara: bool = False) -> int:
    """Calcula la confianza global (50–98%) de la estimación.

    Combina la confianza del detector YOLO con factores de calidad
    derivados de la pose, completitud, tamaño, calidad de máscara
    y disponibilidad de datos externos (edad).
    """
    f_yolo = max(0.30, confianza_yolo)

    # 1. Normalizar YOLO a un rango más amigable (ej. 0.7 → 0.88)
    yolo_norm = 0.80 + (max(0, f_yolo - 0.5) * 0.4)

    # 2. Calidad de la pose
    ra = morfo.get('relacion_aspecto', 1.5)
    if 1.35 <= ra <= 1.75:
        f_pose = 1.0
    elif ra < 1.35:
        f_pose = max(0.85, ra / 1.35)
    else:
        f_pose = max(0.85, 1.0 - (ra - 1.75) * 0.5)

    # 3. Completitud (penalización suave si el animal toca los bordes)
    prop_alto = morfo.get('prop_alto_bbox', 0.3)
    prop_ancho = morfo.get('prop_ancho_bbox', 0.4)
    bordes = sum([prop_alto > 0.97, prop_ancho > 0.97])
    f_completitud = 1.0 if bordes == 0 else max(0.85, 1.0 - bordes * 0.05)

    # 4. Tamaño relativo en la imagen
    area_rel = morfo.get('area_relativa', 0.20)
    f_tamano = 1.0 if 0.08 <= area_rel <= 0.60 else 0.90

    # 5. Calidad de la máscara de segmentación
    if tiene_mascara and morfo.get('valido', False):
        solidez = morfo.get('solidez', 0.75)
        f_mascara = 1.0 if 0.70 <= solidez <= 0.88 else 0.90
    else:
        f_mascara = 0.85

    # 6. Datos externos (edad conocida mejora la estimación)
    f_datos = 1.0 if edad_conocida else 0.95

    # Suma ponderada de factores de calidad
    f_calidad = (
        f_pose * 0.30
        + f_completitud * 0.25
        + f_tamano * 0.20
        + f_mascara * 0.15
        + f_datos * 0.10
    )

    confianza_final = yolo_norm * f_calidad
    porcentaje = int(confianza_final * 100)
    return min(98, max(50, porcentaje))


def calcular_margen_error(confianza_pct: int, morfo: dict,
                          ancho_img: int, alto_img: int,
                          peso_estimado: int,
                          edad_conocida: bool = False,
                          tiene_mascara: bool = False) -> int:
    """Calcula el margen de error en kg.

    El margen se basa en un error base que se ajusta por:
    confianza, bordes recortados, resolución de imagen y uso de máscara.
    """
    error_base = 0.10 if edad_conocida else 0.15
    factor_confianza = max(0.0, (92 - confianza_pct) / 1000.0)

    prop_alto = morfo.get('prop_alto_bbox', 0.3)
    prop_ancho = morfo.get('prop_ancho_bbox', 0.4)
    bordes = sum([prop_alto > 0.95, prop_ancho > 0.95])
    factor_bordes = bordes * 0.025

    resolucion = ancho_img * alto_img
    resolucion_ref = 1920 * 1080
    if resolucion < resolucion_ref * 0.25:
        factor_resolucion = 0.04
    elif resolucion < resolucion_ref:
        factor_resolucion = 0.02
    else:
        factor_resolucion = 0.0

    bonificacion_mascara = -0.02 if tiene_mascara else 0.0

    error_total = max(0.04, error_base + factor_confianza + factor_bordes
                      + factor_resolucion + bonificacion_mascara)
    margen_kg = int(peso_estimado * error_total)

    return min(int(peso_estimado * 0.18), max(5, margen_kg))
