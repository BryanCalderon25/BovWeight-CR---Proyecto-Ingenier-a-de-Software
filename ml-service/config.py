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
# Tablas alométricas por raza — fuentes documentadas
# ─────────────────────────────────────────────────────────────────────────────

# Altura adulta a la cruz (metros) por raza y género.
ALTURAS_ADULTAS = {
    # Razas Cárnicas (Bos Indicus)
    'Brahman':      {'Hembra': 1.36, 'Macho': 1.48},
    'Nelore':       {'Hembra': 1.35, 'Macho': 1.45},
    'Gyr':          {'Hembra': 1.30, 'Macho': 1.40},
    # Razas Cárnicas (Bos Taurus)
    'Angus':        {'Hembra': 1.32, 'Macho': 1.42},
    'Hereford':     {'Hembra': 1.34, 'Macho': 1.45},
    'Charolais':    {'Hembra': 1.42, 'Macho': 1.55},
    'Simmental':    {'Hembra': 1.45, 'Macho': 1.55},
    # Razas Lecheras
    'Holstein':     {'Hembra': 1.45, 'Macho': 1.58},
    'Jersey':       {'Hembra': 1.20, 'Macho': 1.32},
    'Pardo Suizo':  {'Hembra': 1.40, 'Macho': 1.50},
    # Razas Sintéticas / Cruces Comunes
    'Brangus':      {'Hembra': 1.35, 'Macho': 1.45},
    'Senepol':      {'Hembra': 1.30, 'Macho': 1.42},
    # Genérica
    'Desconocida':  {'Hembra': 1.35, 'Macho': 1.45},
}

# Proporciones alométricas adultas relativas a la altura a la cruz.
# - ratio_pt: Relación del Perímetro Torácico respecto a la altura.
# - ratio_lcc: Relación de la Longitud Corporal respecto a la altura.
# - k_schaffer: Constante volumétrica (menor = más pesado/denso).
# - solidez_ref: Compactación esperada de la silueta (cárnicas > lecheras).
PROPORCIONES_RAZA = {
    'Brahman':     {'ratio_pt': 1.28, 'ratio_lcc': 1.38, 'k_schaffer': 10_550, 'solidez_ref': 0.83},
    'Nelore':      {'ratio_pt': 1.27, 'ratio_lcc': 1.39, 'k_schaffer': 10_600, 'solidez_ref': 0.82},
    'Gyr':         {'ratio_pt': 1.24, 'ratio_lcc': 1.42, 'k_schaffer': 10_800, 'solidez_ref': 0.79},
    'Angus':       {'ratio_pt': 1.35, 'ratio_lcc': 1.35, 'k_schaffer': 10_300, 'solidez_ref': 0.86},
    'Hereford':    {'ratio_pt': 1.33, 'ratio_lcc': 1.36, 'k_schaffer': 10_350, 'solidez_ref': 0.85},
    'Charolais':   {'ratio_pt': 1.36, 'ratio_lcc': 1.37, 'k_schaffer': 10_200, 'solidez_ref': 0.87},
    'Simmental':   {'ratio_pt': 1.34, 'ratio_lcc': 1.38, 'k_schaffer': 10_350, 'solidez_ref': 0.85},
    'Holstein':    {'ratio_pt': 1.22, 'ratio_lcc': 1.45, 'k_schaffer': 11_000, 'solidez_ref': 0.77},
    'Jersey':      {'ratio_pt': 1.18, 'ratio_lcc': 1.40, 'k_schaffer': 11_200, 'solidez_ref': 0.76},
    'Pardo Suizo': {'ratio_pt': 1.26, 'ratio_lcc': 1.42, 'k_schaffer': 10_800, 'solidez_ref': 0.80},
    'Brangus':     {'ratio_pt': 1.31, 'ratio_lcc': 1.37, 'k_schaffer': 10_450, 'solidez_ref': 0.84},
    'Senepol':     {'ratio_pt': 1.30, 'ratio_lcc': 1.36, 'k_schaffer': 10_500, 'solidez_ref': 0.84},
    'Desconocida': {'ratio_pt': 1.28, 'ratio_lcc': 1.40, 'k_schaffer': 10_650, 'solidez_ref': 0.81},
}

# Factor de ajuste por género (Dimorfismo Sexual).
# Los machos enteros suelen tener mayor perímetro torácico (músculo en el cuello/espalda).
FACTOR_GENERO = {'Macho': 1.08, 'Hembra': 1.00}

# Curva de crecimiento bovina — fracción del tamaño adulto a la cruz.
# Altamente granularizada por meses para evitar saltos bruscos en las estimaciones.
CURVA_CRECIMIENTO = [
    (0,  0.420), (1,  0.480), (2,  0.540), (3,  0.590),
    (4,  0.630), (5,  0.670), (6,  0.710), (7,  0.740),
    (8,  0.770), (9,  0.800), (10, 0.825), (11, 0.850),
    (12, 0.870), (15, 0.910), (18, 0.940), (21, 0.960),
    (24, 0.975), (30, 0.985), (36, 0.995), (48, 1.000),
]
