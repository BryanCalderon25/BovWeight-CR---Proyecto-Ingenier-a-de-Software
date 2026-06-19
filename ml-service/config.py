import logging

# ─────────────────────────────────────────────────────────────────────────────
# Configuración de Logging
# ─────────────────────────────────────────────────────────────────────────────
logging.basicConfig(
    level=logging.INFO,
    format='%(asctime)s [%(levelname)s] %(name)s: %(message)s',
    datefmt='%Y-%m-%d %H:%M:%S'
)
logger = logging.getLogger('BovWeightCR')

# ─────────────────────────────────────────────────────────────────────────────
# Constante de Schaeffer (divisor estándar métrico)
# ─────────────────────────────────────────────────────────────────────────────
# Fuente: Literatura veterinaria internacional.
# PT² × LCC / 10400 = Peso (kg), con PT y LCC en cm.
# Este valor es fijo y NO varía por raza — la raza afecta las medidas, no K.
SCHAEFFER_K = 10400

# ─────────────────────────────────────────────────────────────────────────────
# Biometría adulta de referencia por raza y género
# ─────────────────────────────────────────────────────────────────────────────
# Valores basados en literatura veterinaria y estudios de campo.
#
# pt_cm:     Perímetro torácico adulto típico (cm)
# lcc_cm:    Longitud corporal adulta típica (cm)
# altura_m:  Altura a la cruz adulta (m)
# peso_ref:  Peso adulto de referencia (kg) — para validación
# peso_min:  Peso adulto mínimo razonable (kg) — para clamp
# peso_max:  Peso adulto máximo razonable (kg) — para clamp

BIOMETRIA_ADULTA = {
    # ── Razas Cárnicas (Bos Indicus) ──
    'Brahman': {
        'Hembra': {'pt_cm': 210, 'lcc_cm': 175, 'altura_m': 1.36, 'peso_ref': 550, 'peso_min': 350, 'peso_max': 800},
        'Macho':  {'pt_cm': 245, 'lcc_cm': 200, 'altura_m': 1.48, 'peso_ref': 900, 'peso_min': 550, 'peso_max': 1200},
    },
    'Nelore': {
        'Hembra': {'pt_cm': 200, 'lcc_cm': 170, 'altura_m': 1.35, 'peso_ref': 500, 'peso_min': 320, 'peso_max': 700},
        'Macho':  {'pt_cm': 235, 'lcc_cm': 195, 'altura_m': 1.45, 'peso_ref': 800, 'peso_min': 500, 'peso_max': 1100},
    },
    'Gyr': {
        'Hembra': {'pt_cm': 190, 'lcc_cm': 160, 'altura_m': 1.30, 'peso_ref': 420, 'peso_min': 280, 'peso_max': 600},
        'Macho':  {'pt_cm': 225, 'lcc_cm': 185, 'altura_m': 1.40, 'peso_ref': 700, 'peso_min': 450, 'peso_max': 950},
    },

    # ── Razas Cárnicas (Bos Taurus) ──
    'Angus': {
        'Hembra': {'pt_cm': 205, 'lcc_cm': 165, 'altura_m': 1.32, 'peso_ref': 580, 'peso_min': 380, 'peso_max': 800},
        'Macho':  {'pt_cm': 240, 'lcc_cm': 190, 'altura_m': 1.42, 'peso_ref': 950, 'peso_min': 600, 'peso_max': 1250},
    },
    'Hereford': {
        'Hembra': {'pt_cm': 205, 'lcc_cm': 168, 'altura_m': 1.34, 'peso_ref': 570, 'peso_min': 370, 'peso_max': 780},
        'Macho':  {'pt_cm': 238, 'lcc_cm': 192, 'altura_m': 1.45, 'peso_ref': 920, 'peso_min': 580, 'peso_max': 1200},
    },
    'Charolais': {
        'Hembra': {'pt_cm': 215, 'lcc_cm': 178, 'altura_m': 1.42, 'peso_ref': 700, 'peso_min': 450, 'peso_max': 950},
        'Macho':  {'pt_cm': 255, 'lcc_cm': 210, 'altura_m': 1.55, 'peso_ref': 1100, 'peso_min': 700, 'peso_max': 1400},
    },
    'Simmental': {
        'Hembra': {'pt_cm': 215, 'lcc_cm': 178, 'altura_m': 1.45, 'peso_ref': 680, 'peso_min': 440, 'peso_max': 900},
        'Macho':  {'pt_cm': 250, 'lcc_cm': 205, 'altura_m': 1.55, 'peso_ref': 1050, 'peso_min': 650, 'peso_max': 1350},
    },

    # ── Razas Lecheras ──
    'Holstein': {
        'Hembra': {'pt_cm': 215, 'lcc_cm': 180, 'altura_m': 1.45, 'peso_ref': 650, 'peso_min': 420, 'peso_max': 900},
        'Macho':  {'pt_cm': 248, 'lcc_cm': 205, 'altura_m': 1.58, 'peso_ref': 1000, 'peso_min': 650, 'peso_max': 1300},
    },
    'Jersey': {
        'Hembra': {'pt_cm': 180, 'lcc_cm': 150, 'altura_m': 1.20, 'peso_ref': 400, 'peso_min': 280, 'peso_max': 550},
        'Macho':  {'pt_cm': 210, 'lcc_cm': 175, 'altura_m': 1.32, 'peso_ref': 650, 'peso_min': 420, 'peso_max': 850},
    },
    'Pardo Suizo': {
        'Hembra': {'pt_cm': 210, 'lcc_cm': 175, 'altura_m': 1.40, 'peso_ref': 600, 'peso_min': 400, 'peso_max': 820},
        'Macho':  {'pt_cm': 242, 'lcc_cm': 200, 'altura_m': 1.50, 'peso_ref': 950, 'peso_min': 600, 'peso_max': 1200},
    },

    # ── Razas Sintéticas / Cruces ──
    'Brangus': {
        'Hembra': {'pt_cm': 205, 'lcc_cm': 170, 'altura_m': 1.35, 'peso_ref': 560, 'peso_min': 360, 'peso_max': 780},
        'Macho':  {'pt_cm': 242, 'lcc_cm': 195, 'altura_m': 1.45, 'peso_ref': 920, 'peso_min': 580, 'peso_max': 1200},
    },
    'Senepol': {
        'Hembra': {'pt_cm': 200, 'lcc_cm': 165, 'altura_m': 1.30, 'peso_ref': 500, 'peso_min': 330, 'peso_max': 700},
        'Macho':  {'pt_cm': 235, 'lcc_cm': 190, 'altura_m': 1.42, 'peso_ref': 800, 'peso_min': 520, 'peso_max': 1050},
    },

    # ── Genérica (fallback) ──
    'Desconocida': {
        'Hembra': {'pt_cm': 205, 'lcc_cm': 170, 'altura_m': 1.35, 'peso_ref': 550, 'peso_min': 300, 'peso_max': 850},
        'Macho':  {'pt_cm': 240, 'lcc_cm': 195, 'altura_m': 1.45, 'peso_ref': 850, 'peso_min': 450, 'peso_max': 1250},
    },
}

# ─────────────────────────────────────────────────────────────────────────────
# Coeficientes de regresión cuadrática por tipo de raza
# ─────────────────────────────────────────────────────────────────────────────
# Modelo: BW = a × PT² + b × PT + c
# Fuentes: Estudios publicados en ResearchGate, LRRD, y universidades.
#
# - 'lechera':     Basado en Holstein-Friesian (R² ≈ 0.99)
# - 'cebu':        Basado en Brahman/Nelore (R² ≈ 0.95)
# - 'carne_taurus': Basado en Angus/Hereford (R² ≈ 0.96)
# - 'pesada':      Basado en Charolais/Simmental (R² ≈ 0.95)
# - 'generica':    Promedio general

REGRESION_POR_TIPO = {
    'lechera':       {'a': 0.0322, 'b': -3.89, 'c': 154.4},
    'cebu':          {'a': 0.0280, 'b': -2.15, 'c': 82.3},
    'carne_taurus':  {'a': 0.0305, 'b': -3.20, 'c': 120.0},
    'pesada':        {'a': 0.0310, 'b': -3.05, 'c': 105.0},
    'generica':      {'a': 0.0300, 'b': -3.00, 'c': 110.0},
}

# Mapeo de raza a tipo de regresión
TIPO_REGRESION = {
    'Brahman':     'cebu',
    'Nelore':      'cebu',
    'Gyr':         'cebu',
    'Angus':       'carne_taurus',
    'Hereford':    'carne_taurus',
    'Charolais':   'pesada',
    'Simmental':   'pesada',
    'Holstein':    'lechera',
    'Jersey':      'lechera',
    'Pardo Suizo': 'lechera',
    'Brangus':     'carne_taurus',
    'Senepol':     'carne_taurus',
    'Desconocida': 'generica',
}

# ─────────────────────────────────────────────────────────────────────────────
# Factor de ajuste por género (Dimorfismo Sexual)
# ─────────────────────────────────────────────────────────────────────────────
# Los machos enteros suelen tener mayor perímetro torácico (músculo en el
# cuello/espalda) y mayor peso a igual estatura.
FACTOR_GENERO = {'Macho': 1.08, 'Hembra': 1.00}

# ─────────────────────────────────────────────────────────────────────────────
# Curva de crecimiento bovina — fracción del tamaño adulto
# ─────────────────────────────────────────────────────────────────────────────
# Fracción de la altura adulta alcanzada en cada mes.
# Altamente granularizada para evitar saltos bruscos en las estimaciones.
CURVA_CRECIMIENTO = [
    (0,  0.420), (1,  0.480), (2,  0.540), (3,  0.590),
    (4,  0.630), (5,  0.670), (6,  0.710), (7,  0.740),
    (8,  0.770), (9,  0.800), (10, 0.825), (11, 0.850),
    (12, 0.870), (15, 0.910), (18, 0.940), (21, 0.960),
    (24, 0.975), (30, 0.985), (36, 0.995), (48, 1.000),
]

# Curva de crecimiento en PESO (diferente de altura).
# El peso crece más rápido que la altura porque es proporcional al volumen.
# Fracción del peso adulto por edad en meses.
CURVA_PESO = [
    (0,  0.045), (1,  0.065), (2,  0.090), (3,  0.120),
    (4,  0.155), (5,  0.195), (6,  0.240), (7,  0.280),
    (8,  0.325), (9,  0.370), (10, 0.410), (11, 0.450),
    (12, 0.490), (15, 0.590), (18, 0.680), (21, 0.760),
    (24, 0.830), (30, 0.910), (36, 0.960), (48, 1.000),
]
