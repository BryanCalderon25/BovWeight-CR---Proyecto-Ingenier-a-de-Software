"""
estimadores.py — Motor de estimación de peso bovino.

Implementa dos métodos validados científicamente y los combina:
  1. Schaeffer Estándar: PT² × LCC / 10400  (fórmula universal, ±5%)
  2. Regresión Cuadrática por Raza: a×PT² + b×PT + c  (específica, R²>0.95)

El ensamble valida ambos resultados contra rangos biológicos y
selecciona la mejor estimación.

Flujo:
  1. Estimar biometría (PT, LCC, BCS) desde la morfología visual.
  2. Calcular peso con Schaeffer y con regresión cuadrática.
  3. Validar contra rangos biológicos de la raza.
  4. Combinar resultados en ensamble final.
"""

from typing import Optional
from config import (
    logger,
    SCHAEFFER_K,
    BIOMETRIA_ADULTA,
    REGRESION_POR_TIPO,
    TIPO_REGRESION,
)
from biometria import estimar_biometria, fraccion_peso_por_edad


# ─────────────────────────────────────────────────────────────────────────────
# Fórmulas Veterinarias (uso interno)
# ─────────────────────────────────────────────────────────────────────────────

def _peso_schaeffer(pt_cm: float, lcc_cm: float) -> float:
    """Fórmula de Schaeffer estándar: (PT² × LCC) / 10400.

    Fuente: Literatura veterinaria internacional.
    Precisión documentada: ±5% del peso real con medidas correctas.
    El divisor 10400 es la constante estándar para sistema métrico (cm/kg).
    """
    return (pt_cm ** 2) * lcc_cm / SCHAEFFER_K


def _peso_regresion(pt_cm: float, raza: str) -> float:
    """Regresión cuadrática: a×PT² + b×PT + c.

    Usa coeficientes específicos por tipo de raza (lechera, cebú, carne, etc.)
    basados en estudios publicados con R² > 0.95.
    """
    tipo = TIPO_REGRESION.get(raza, 'generica')
    coefs = REGRESION_POR_TIPO.get(tipo, REGRESION_POR_TIPO['generica'])

    a, b, c = coefs['a'], coefs['b'], coefs['c']
    return a * (pt_cm ** 2) + b * pt_cm + c


# ─────────────────────────────────────────────────────────────────────────────
# Validación de Rangos Biológicos
# ─────────────────────────────────────────────────────────────────────────────

def _rango_peso_biologico(raza: str, genero: str,
                          fraccion_peso: float) -> tuple:
    """Calcula el rango de peso biológicamente razonable.

    Escala los rangos adultos por la fracción de desarrollo en peso.
    Retorna (peso_min, peso_max, peso_ref).
    """
    clave_genero = 'Macho' if genero == 'Macho' else 'Hembra'
    ref = BIOMETRIA_ADULTA.get(raza, BIOMETRIA_ADULTA['Desconocida'])[clave_genero]

    peso_min = ref['peso_min'] * fraccion_peso
    peso_max = ref['peso_max'] * fraccion_peso
    peso_ref = ref['peso_ref'] * fraccion_peso

    # Mínimo absoluto: 25 kg (becerro recién nacido)
    peso_min = max(25.0, peso_min)

    return peso_min, peso_max, peso_ref


def _clamp_a_rango(peso: float, peso_min: float, peso_max: float) -> float:
    """Ajusta el peso si está fuera del rango biológico.

    En vez de un clamp duro, usa un ajuste suave que atrae el peso
    hacia los límites sin distorsionar completamente.
    """
    if peso < peso_min:
        # Ajuste suave: 70% hacia el límite inferior
        return peso_min * 0.70 + peso * 0.30
    elif peso > peso_max:
        # Ajuste suave: 70% hacia el límite superior
        return peso_max * 0.70 + peso * 0.30
    return peso


# ─────────────────────────────────────────────────────────────────────────────
# Estimador Principal (API pública)
# ─────────────────────────────────────────────────────────────────────────────

def estimar_peso_ensemble(morfo: dict, raza: str, genero: str,
                          edad_meses: Optional[int]) -> dict:
    """Estima el peso combinando Schaeffer y regresión cuadrática.

    Ejecuta ambas fórmulas sobre la biometría estimada desde la morfología
    visual, valida contra rangos biológicos, y combina los resultados.

    Reglas del ensamble:
      - Si ambos están dentro del rango biológico y difieren <20%:
        promedio ponderado 55% Schaeffer + 45% regresión.
      - Si difieren >20%: usa el más cercano al peso de referencia.
      - Si uno está fuera de rango: usa el que está dentro.
      - Si ambos están fuera: usa el más cercano al rango y advierte.

    Retorna:
      - peso_kg:  Peso final estimado (kg)
      - metodo:   Descripción del método usado
      - metricas: Diccionario con detalles de cada sub-estimación
    """
    # 1. Calcular biometría desde la morfología visual
    bio = estimar_biometria(morfo, raza, genero, edad_meses)
    pt = bio['pt_cm']
    lcc = bio['lcc_cm']
    bcs = bio['bcs']
    fraccion_peso = bio['fraccion_peso']

    # 2. Ejecutar las 2 fórmulas
    p_schaeffer = _peso_schaeffer(pt, lcc)
    p_regresion = _peso_regresion(pt, raza)

    # 3. Obtener rango biológico
    peso_min, peso_max, peso_ref = _rango_peso_biologico(
        raza, genero, fraccion_peso
    )

    # 4. Evaluar si cada estimación está dentro del rango
    schaeffer_ok = peso_min * 0.80 <= p_schaeffer <= peso_max * 1.20
    regresion_ok = peso_min * 0.80 <= p_regresion <= peso_max * 1.20

    # 5. Calcular diferencia relativa entre fórmulas
    media = (p_schaeffer + p_regresion) / 2.0
    diff_rel = abs(p_schaeffer - p_regresion) / media if media > 0 else 0

    # 6. Seleccionar método de ensamble
    if schaeffer_ok and regresion_ok and diff_rel < 0.20:
        # Caso ideal: ambos concuerdan
        peso_ensamblado = p_schaeffer * 0.55 + p_regresion * 0.45
        metodo_ganador = 'Ensamble (Schaeffer 55% + Regresión 45%)'
    elif schaeffer_ok and regresion_ok:
        # Ambos en rango pero difieren mucho: usar el más cercano al ref
        d_sch = abs(p_schaeffer - peso_ref)
        d_reg = abs(p_regresion - peso_ref)
        if d_sch <= d_reg:
            peso_ensamblado = p_schaeffer * 0.70 + p_regresion * 0.30
            metodo_ganador = 'Schaeffer priorizado (70/30)'
        else:
            peso_ensamblado = p_regresion * 0.70 + p_schaeffer * 0.30
            metodo_ganador = 'Regresión priorizada (70/30)'
    elif schaeffer_ok:
        peso_ensamblado = p_schaeffer
        metodo_ganador = 'Schaeffer (regresión fuera de rango)'
    elif regresion_ok:
        peso_ensamblado = p_regresion
        metodo_ganador = 'Regresión (Schaeffer fuera de rango)'
    else:
        # Ambos fuera de rango: usar el más cercano y ajustar suavemente
        d_sch = min(abs(p_schaeffer - peso_min), abs(p_schaeffer - peso_max))
        d_reg = min(abs(p_regresion - peso_min), abs(p_regresion - peso_max))
        mejor = p_schaeffer if d_sch <= d_reg else p_regresion
        peso_ensamblado = _clamp_a_rango(mejor, peso_min, peso_max)
        metodo_ganador = 'Ajuste a rango biológico (ambos fuera de rango)'
        logger.warning(
            f"Ambas fórmulas fuera de rango biológico: "
            f"Schaeffer={p_schaeffer:.0f}, Regresión={p_regresion:.0f}, "
            f"Rango=[{peso_min:.0f}, {peso_max:.0f}]"
        )

    # 7. Ajuste final por BCS
    # Un animal con BCS alto (>6) tiene más peso del que indican las medidas
    # lineales. Un animal flaco (BCS<4) pesa menos.
    factor_bcs_peso = 1.0 + (bcs - 5.0) * 0.025
    peso_ensamblado *= factor_bcs_peso

    # 8. Clamp final (absoluto: no menos de 25kg, no más de 1500kg)
    peso_final = int(max(25, min(1500, peso_ensamblado)))

    logger.info(
        f"Estimación: Schaeffer={p_schaeffer:.0f}kg, "
        f"Regresión={p_regresion:.0f}kg → Final={peso_final}kg | "
        f"PT={pt}cm, LCC={lcc}cm, BCS={bcs}, "
        f"Rango=[{peso_min:.0f}, {peso_max:.0f}] | {metodo_ganador}"
    )

    return {
        'peso_kg': peso_final,
        'metodo': metodo_ganador,
        'metricas': {
            'peso_schaeffer_kg': int(p_schaeffer),
            'peso_regresion_kg': int(p_regresion),
            'bcs_condicion_corporal': round(bcs, 1),
            'altura_estimada_m': bio['altura_m'],
            'pt_estimado_cm': pt,
            'lcc_estimado_cm': lcc,
            'fraccion_desarrollo': bio['fraccion_altura'],
            'escala_cm_por_px': bio['escala_cm_por_px'],
            'rango_biologico': f"[{peso_min:.0f}, {peso_max:.0f}] kg",
            'peso_referencia_raza': f"{peso_ref:.0f} kg",
            'metodo_seleccion': metodo_ganador,
            'diferencia_formulas_pct': f"{diff_rel * 100:.1f}%",
        },
    }
