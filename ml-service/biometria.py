"""
biometria.py — Funciones de biometría bovina.

Contiene:
  - Interpolación de curva de crecimiento por edad.
  - Estimación de fracción de crecimiento por morfología.
  - Estimación de medidas biométricas reales (PT, LCC) desde morfología visual.
  - Corrección de perspectiva.
  - Cálculo de confianza y margen de error.
"""

import math
from config import (
    CURVA_CRECIMIENTO,
    CURVA_PESO,
    BIOMETRIA_ADULTA,
    FACTOR_GENERO,
    logger,
)


# ─────────────────────────────────────────────────────────────────────────────
# Fracción de Crecimiento
# ─────────────────────────────────────────────────────────────────────────────

def _interpolar_curva(curva: list, edad_meses: int) -> float:
    """Interpola linealmente una curva (edad→fracción)."""
    if edad_meses <= 0:
        return curva[0][1]
    if edad_meses >= curva[-1][0]:
        return 1.0

    for i in range(len(curva) - 1):
        m0, f0 = curva[i]
        m1, f1 = curva[i + 1]
        if m0 <= edad_meses < m1:
            t = (edad_meses - m0) / (m1 - m0)
            return f0 + t * (f1 - f0)

    return 1.0


def fraccion_crecimiento_por_edad(edad_meses: int) -> float:
    """Interpola la fracción de desarrollo en ALTURA (0.0–1.0) según la edad."""
    return _interpolar_curva(CURVA_CRECIMIENTO, edad_meses)


def fraccion_peso_por_edad(edad_meses: int) -> float:
    """Interpola la fracción de desarrollo en PESO (0.0–1.0) según la edad.

    El peso crece más rápido que la altura porque es proporcional al volumen
    (crece cúbicamente con las dimensiones lineales).
    """
    return _interpolar_curva(CURVA_PESO, edad_meses)


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
# Estimación de Biometría Real desde Morfología Visual
# ─────────────────────────────────────────────────────────────────────────────

def estimar_biometria(morfo: dict, raza: str, genero: str,
                      edad_meses: int = None) -> dict:
    """Estima las medidas biométricas reales (PT, LCC) desde la morfología.

    Estrategia:
      1. Obtener medidas de referencia adultas de la raza/género.
      2. Calcular la fracción de desarrollo (por edad o por morfología).
      3. Usar la relación entre píxeles del animal y su altura conocida
         para establecer una escala px→cm.
      4. Con esa escala, convertir las dimensiones medidas en píxeles
         a centímetros reales.
      5. Aplicar correcciones por perspectiva y condición corporal.

    Returns:
        Diccionario con PT (cm), LCC (cm), BCS, fracción de desarrollo,
        y altura estimada (m).
    """
    clave_genero = 'Macho' if genero == 'Macho' else 'Hembra'
    ref = BIOMETRIA_ADULTA.get(raza, BIOMETRIA_ADULTA['Desconocida'])[clave_genero]

    pt_adulto = ref['pt_cm']
    lcc_adulto = ref['lcc_cm']
    altura_adulto = ref['altura_m']

    # ── 1. Fracción de desarrollo ──
    if edad_meses is not None:
        fraccion_altura = fraccion_crecimiento_por_edad(edad_meses)
        fraccion_peso = fraccion_peso_por_edad(edad_meses)
    else:
        fraccion_altura = estimar_fraccion_crecimiento_por_morfo(morfo)
        # Sin edad, estimar fracción de peso como fracción_altura^2.5
        # (el peso escala ~cúbicamente con dimensiones lineales)
        fraccion_peso = min(1.0, fraccion_altura ** 2.5)

    # Altura física actual del animal
    altura_fisica_m = altura_adulto * fraccion_altura

    # ── 2. Escala píxeles → centímetros ──
    # La altura del animal en píxeles corresponde a su altura real.
    # "altura_px" en la morfología es el eje menor de la elipse ajustada
    # (o la altura del bbox), que corresponde a la altura real del animal
    # en vista lateral.
    altura_px = morfo.get('altura_px', 0.0)
    longitud_px = morfo.get('longitud_px', 0.0)
    ancho_max_px = morfo.get('ancho_max_px', 0.0)

    if altura_px > 10:
        # Escala: cuántos cm reales mide cada píxel
        escala_cm_por_px = (altura_fisica_m * 100.0) / altura_px
    else:
        # Fallback: usar proporciones de referencia directamente
        escala_cm_por_px = 0.0

    # ── 3. Corrección de perspectiva ──
    relacion_aspecto = morfo.get('relacion_aspecto', 1.55)
    factor_persp = factor_correccion_perspectiva(relacion_aspecto)

    # ── 4. Estimar LCC (Longitud Corporal) ──
    if escala_cm_por_px > 0 and longitud_px > 10:
        # La longitud en píxeles × escala = longitud real en cm
        lcc_medido = longitud_px * escala_cm_por_px * factor_persp
        # La longitud medida incluye cabeza y cola parcialmente;
        # la LCC real (hombro a nalga) es ~85% de la longitud total visible
        lcc_cm = lcc_medido * 0.85
    else:
        # Fallback: usar proporción de referencia escalada por desarrollo
        lcc_cm = lcc_adulto * fraccion_altura

    # ── 5. Estimar PT (Perímetro Torácico) ──
    if escala_cm_por_px > 0 and ancho_max_px > 10:
        # El "ancho máximo perpendicular" en la silueta lateral corresponde
        # aproximadamente a la altura del torso. El PT es la circunferencia,
        # que se relaciona con el diámetro visible por un factor geométrico.
        #
        # En vista lateral, el ancho_max_px captura el diámetro vertical
        # del torso. El PT ≈ π × diámetro_promedio × factor_forma.
        # Para bovinos, el torso es ovalado (más ancho que alto), así que:
        # PT ≈ ancho_max_cm × π × 1.05 (factor para sección ovalada)
        ancho_torso_cm = ancho_max_px * escala_cm_por_px
        pt_cm = ancho_torso_cm * math.pi * 1.05
    else:
        # Fallback: usar proporción de referencia escalada
        pt_cm = pt_adulto * fraccion_altura

    # ── 6. Condición Corporal Estimada (BCS: 1–9) ──
    solidez = morfo.get('solidez', 0.78)
    compacidad = morfo.get('compacidad', 0.30)
    extension = morfo.get('extension', 0.70)

    # BCS basado en forma de la silueta:
    # - Solidez alta → animal gordo (más relleno)
    # - Compacidad alta → animal compacto (menos angular)
    # - Extensión alta → animal rellena el bbox (menos espacio vacío)
    bcs_solidez = (solidez - 0.70) / 0.20 * 4.0      # 0.70→0, 0.90→4
    bcs_compacidad = (compacidad - 0.20) / 0.20 * 2.0  # 0.20→0, 0.40→2
    bcs_extension = (extension - 0.60) / 0.15 * 2.0    # 0.60→0, 0.75→2

    bcs = max(1.0, min(9.0, 3.0 + bcs_solidez + bcs_compacidad + bcs_extension))

    # ── 7. Ajuste del PT por condición corporal ──
    # Un animal gordo tiene un PT mayor (más grasa subcutánea).
    # El ajuste es sutil: ±3% por cada punto de BCS desde la media (5).
    factor_bcs = 1.0 + (bcs - 5.0) * 0.030
    pt_cm *= factor_bcs

    # ── 8. Ajuste por género ──
    factor_gen = FACTOR_GENERO.get(clave_genero, 1.0)
    pt_cm *= factor_gen

    # ── 9. Clamp a rangos realistas ──
    # PT: 60cm (becerro) a 310cm (toro muy grande)
    pt_cm = max(60.0, min(310.0, pt_cm))
    # LCC: 50cm (becerro) a 300cm (animal muy largo)
    lcc_cm = max(50.0, min(300.0, lcc_cm))

    logger.debug(
        f"Biometría: PT={pt_cm:.1f}cm, LCC={lcc_cm:.1f}cm, "
        f"BCS={bcs:.1f}, fracción={fraccion_altura:.3f}, "
        f"escala={escala_cm_por_px:.3f}cm/px"
    )

    return {
        'pt_cm': round(pt_cm, 1),
        'lcc_cm': round(lcc_cm, 1),
        'bcs': round(bcs, 1),
        'altura_m': round(altura_fisica_m, 3),
        'fraccion_altura': round(fraccion_altura, 3),
        'fraccion_peso': round(fraccion_peso, 3),
        'escala_cm_por_px': round(escala_cm_por_px, 4),
    }


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
    error_base = 0.08 if edad_conocida else 0.12
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

    return min(int(peso_estimado * 0.15), max(5, margen_kg))
