"""
estimadores.py — Motor de estimación de peso bovino.

Implementa tres fórmulas veterinarias reconocidas (Schaeffer, Agarwal,
Minnesota) y las combina en un ensamblaje ponderado dinámicamente según
la condición corporal (BCS) del animal.

Flujo:
  1. Extraer biometría base (PT, LCC, BCS) desde la morfología visual.
  2. Calcular peso con cada fórmula.
  3. Ponderar según BCS y devolver resultado final.
"""

from typing import Optional
from config import (
    logger,
    PROPORCIONES_RAZA,
    ALTURAS_ADULTAS,
    FACTOR_GENERO,
)
from biometria import (
    fraccion_crecimiento_por_edad,
    estimar_fraccion_crecimiento_por_morfo,
    factor_correccion_perspectiva,
)


# ─────────────────────────────────────────────────────────────────────────────
# Biometría Base (uso interno)
# ─────────────────────────────────────────────────────────────────────────────

def _calcular_biometria(morfo: dict, raza: str, genero: str,
                        edad_meses: Optional[int]) -> dict:
    """Calcula las métricas biométricas base a partir de la morfología.

    Retorna un diccionario con:
      - pt_cm:    Perímetro Torácico estimado (cm)
      - lcc_cm:   Longitud Corporal estimada (cm)
      - bcs:      Condición Corporal estimada (1–9)
      - k_base:   Constante volumétrica ajustada por densidad
      - altura_m: Altura física estimada (m)
    """
    clave_genero = 'Macho' if genero == 'Macho' else 'Hembra'
    props = PROPORCIONES_RAZA.get(raza, PROPORCIONES_RAZA['Desconocida'])
    altura_adulto = ALTURAS_ADULTAS.get(raza, ALTURAS_ADULTAS['Desconocida'])[clave_genero]

    # Fracción de desarrollo (0.0–1.0)
    if edad_meses is not None:
        fraccion = fraccion_crecimiento_por_edad(edad_meses)
    else:
        fraccion = estimar_fraccion_crecimiento_por_morfo(morfo)

    altura_fisica_m = altura_adulto * fraccion

    # Corrección de perspectiva
    factor_persp = factor_correccion_perspectiva(morfo.get('relacion_aspecto', 1.55))
    excentricidad = morfo.get('excentricidad', 0.85)
    if excentricidad < 0.75:
        factor_persp *= (0.75 / max(0.5, excentricidad)) ** 0.5

    # Condición corporal estimada (BCS: Body Condition Score, escala 1–9)
    solidez = morfo.get('solidez', props['solidez_ref'])
    compacidad = morfo.get('compacidad', 0.30)

    delta_solidez = (solidez - props['solidez_ref']) * 10.0
    delta_compacidad = (compacidad - 0.30) * 15.0
    bcs = max(1.0, min(9.0, 5.0 + delta_solidez + delta_compacidad))

    # Perímetro Torácico y Longitud Corporal estimados
    factor_bcs = 1.0 + (bcs - 5.0) * 0.045
    pt_m = altura_fisica_m * props['ratio_pt'] * FACTOR_GENERO[clave_genero] * factor_bcs
    lcc_m = altura_fisica_m * props['ratio_lcc'] * factor_persp

    pt_cm = min(310.0, max(50.0, pt_m * 100.0))
    lcc_cm = min(320.0, max(45.0, lcc_m * 100.0))

    # Ajuste de densidad según madurez
    ajuste_densidad = 1.0 - (fraccion - 0.5) * 0.05
    k_base = props['k_schaffer'] * ajuste_densidad

    return {
        'pt_cm': pt_cm,
        'lcc_cm': lcc_cm,
        'bcs': bcs,
        'k_base': k_base,
        'altura_m': altura_fisica_m,
    }


# ─────────────────────────────────────────────────────────────────────────────
# Fórmulas Veterinarias (uso interno)
# ─────────────────────────────────────────────────────────────────────────────

def _peso_schaeffer(pt_cm: float, lcc_cm: float, k: float) -> float:
    """Fórmula de Schaeffer: (PT² × LCC) / K"""
    return (pt_cm ** 2) * lcc_cm / k


def _peso_agarwal(pt_cm: float, lcc_cm: float, k: float) -> float:
    """Fórmula de Agarwal: (PT × LCC) / K_ag — mejor para animales delgados."""
    k_agarwal = k / 65.0
    return (pt_cm * lcc_cm) / k_agarwal


def _peso_minnesota(pt_cm: float, k: float) -> float:
    """Fórmula de Minnesota: PT³ / K_mn — mejor para animales anchos/pesados."""
    k_minnesota = k * 1.6
    return (pt_cm ** 3) / k_minnesota


# ─────────────────────────────────────────────────────────────────────────────
# Estimador Principal (API pública)
# ─────────────────────────────────────────────────────────────────────────────

def estimar_peso_ensemble(morfo: dict, raza: str, genero: str,
                          edad_meses: Optional[int]) -> dict:
    """Estima el peso combinando tres fórmulas veterinarias.

    Ejecuta Schaeffer, Agarwal y Minnesota sobre la misma biometría base
    y pondera los resultados dinámicamente según la condición corporal (BCS):
      - BCS > 6.5 → prioriza Minnesota (volumen cúbico)
      - BCS < 4.0 → prioriza Agarwal (animales delgados)
      - Caso medio → prioriza Schaeffer

    Retorna:
      - peso_kg:  Peso final estimado (kg)
      - metodo:   Descripción del método usado
      - metricas: Diccionario con detalles de cada sub-estimación
    """
    # 1. Calcular biometría una sola vez
    bio = _calcular_biometria(morfo, raza, genero, edad_meses)
    pt = bio['pt_cm']
    lcc = bio['lcc_cm']
    bcs = bio['bcs']
    k = bio['k_base']

    # 2. Ejecutar las 3 fórmulas
    p_schaeffer = _peso_schaeffer(pt, lcc, k)
    p_agarwal = _peso_agarwal(pt, lcc, k)
    p_minnesota = _peso_minnesota(pt, k)

    # 3. Ponderación dinámica según BCS
    w_schaeffer = 0.50
    w_agarwal = 0.25
    w_minnesota = 0.25

    if bcs > 6.5:
        w_minnesota = 0.50
        w_schaeffer = 0.35
        w_agarwal = 0.15
    elif bcs < 4.0:
        w_agarwal = 0.50
        w_schaeffer = 0.35
        w_minnesota = 0.15

    # 4. Promedio ponderado
    peso_ensamblado = (
        p_schaeffer * w_schaeffer
        + p_agarwal * w_agarwal
        + p_minnesota * w_minnesota
    )
    peso_final = int(min(1400.0, max(20.0, peso_ensamblado)))

    return {
        'peso_kg': peso_final,
        'metodo': 'Ensamble (Schaeffer + Agarwal + Minnesota)',
        'metricas': {
            'peso_schaeffer_kg': int(p_schaeffer),
            'peso_agarwal_kg': int(p_agarwal),
            'peso_minnesota_kg': int(p_minnesota),
            'bcs_condicion_corporal': round(bcs, 1),
            'altura_estimada_m': round(bio['altura_m'], 3),
            'pt_estimado_cm': round(pt, 1),
            'lcc_estimado_cm': round(lcc, 1),
            'pesos_votacion': (
                f"S:{int(w_schaeffer * 100)}% "
                f"A:{int(w_agarwal * 100)}% "
                f"M:{int(w_minnesota * 100)}%"
            ),
        },
    }
