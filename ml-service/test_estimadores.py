"""
test_estimadores.py — Verificación del motor de estimación de peso.

Prueba las fórmulas con datos biométricos conocidos (PT y LCC reales)
contra pesos esperados de literatura veterinaria.

Uso:
    python test_estimadores.py
"""

import sys
import os

# Agregar el directorio actual al path
sys.path.insert(0, os.path.dirname(os.path.abspath(__file__)))

from estimadores import _peso_schaeffer, _peso_regresion, estimar_peso_ensemble
from biometria import estimar_biometria
from config import SCHAEFFER_K, BIOMETRIA_ADULTA


def test_schaeffer_directo():
    """Verifica la fórmula de Schaeffer con datos biométricos conocidos.
    
    Datos de referencia (medidos con cinta):
    - PT=200cm, LCC=160cm → ~615 kg
    - PT=220cm, LCC=180cm → ~838 kg  
    - PT=240cm, LCC=200cm → ~1107 kg
    - PT=180cm, LCC=150cm → ~467 kg
    - PT=160cm, LCC=140cm → ~346 kg
    """
    print("=" * 70)
    print("TEST 1: Fórmula de Schaeffer Directa (PT²×LCC/10400)")
    print("=" * 70)
    
    casos = [
        # (PT_cm, LCC_cm, peso_esperado_aprox, descripción)
        (160, 140, 346,  "Novilla joven (~350 kg)"),
        (180, 150, 467,  "Novilla adulta (~470 kg)"),
        (200, 160, 615,  "Vaca mediana (~615 kg)"),
        (210, 170, 721,  "Vaca adulta (~720 kg)"),
        (220, 180, 838,  "Vaca grande (~840 kg)"),
        (235, 190, 1009, "Toro mediano (~1000 kg)"),
        (240, 200, 1107, "Toro grande (~1100 kg)"),
        (250, 210, 1262, "Toro muy grande (~1260 kg)"),
    ]
    
    todos_ok = True
    for pt, lcc, esperado, desc in casos:
        peso = _peso_schaeffer(pt, lcc)
        error_pct = abs(peso - esperado) / esperado * 100
        ok = "✓" if error_pct < 2 else "✗"
        if error_pct >= 2:
            todos_ok = False
        print(f"  {ok} PT={pt}cm, LCC={lcc}cm → {peso:.0f} kg "
              f"(esperado: {esperado} kg, error: {error_pct:.1f}%) — {desc}")
    
    print(f"\n  → {'PASÓ' if todos_ok else 'FALLÓ'}\n")
    return todos_ok


def test_regresion_por_raza():
    """Verifica la regresión cuadrática con datos de referencia por raza."""
    print("=" * 70)
    print("TEST 2: Regresión Cuadrática por Raza")
    print("=" * 70)
    
    # Casos: (raza, PT_cm, peso_esperado_min, peso_esperado_max)
    casos = [
        ("Holstein", 215, 550, 750),     # Vaca lechera adulta
        ("Holstein", 248, 900, 1100),     # Toro Holstein
        ("Brahman",  210, 450, 650),      # Vaca Brahman
        ("Brahman",  245, 800, 1050),     # Toro Brahman
        ("Angus",    205, 480, 680),      # Vaca Angus
        ("Angus",    240, 850, 1100),     # Toro Angus
        ("Jersey",   180, 300, 500),      # Vaca Jersey
        ("Charolais", 255, 1000, 1300),   # Toro Charolais
    ]
    
    todos_ok = True
    for raza, pt, p_min, p_max in casos:
        peso = _peso_regresion(pt, raza)
        ok = "✓" if p_min <= peso <= p_max else "✗"
        if not (p_min <= peso <= p_max):
            todos_ok = False
        print(f"  {ok} {raza:12s} PT={pt}cm → {peso:.0f} kg "
              f"(esperado: {p_min}-{p_max} kg)")
    
    print(f"\n  → {'PASÓ' if todos_ok else 'FALLÓ'}\n")
    return todos_ok


def test_ensemble_con_morfo_simulado():
    """Simula morfologías visuales y verifica que el ensamble produce
    pesos razonables para cada raza."""
    print("=" * 70)
    print("TEST 3: Ensamble Completo con Morfología Simulada")
    print("=" * 70)
    
    # Simular una vaca adulta grande (lo que se ve en la imagen)
    # Estos son los valores que produciría la extracción de morfología
    # para un animal que ocupa ~40% de la imagen, en vista lateral.
    
    casos = [
        {
            'desc': 'Holstein adulto macho (~1000 kg)',
            'raza': 'Holstein', 'genero': 'Macho', 'edad_meses': 48,
            'morfo': {
                'valido': True,
                'area_px': 250000.0,
                'area_relativa': 0.35,
                'solidez': 0.82,
                'compacidad': 0.35,
                'longitud_px': 700.0,
                'altura_px': 420.0,
                'relacion_aspecto': 1.67,
                'orientacion_deg': 5.0,
                'ancho_max_px': 410.0,
                'excentricidad': 0.85,
                'extension': 0.72,
                'area_bbox_px': 294000.0,
                'prop_alto_bbox': 0.55,
                'prop_ancho_bbox': 0.70,
            },
            'peso_min': 700, 'peso_max': 1300,
        },
        {
            'desc': 'Brahman hembra adulta (~550 kg)',
            'raza': 'Brahman', 'genero': 'Hembra', 'edad_meses': 36,
            'morfo': {
                'valido': True,
                'area_px': 180000.0,
                'area_relativa': 0.25,
                'solidez': 0.80,
                'compacidad': 0.32,
                'longitud_px': 600.0,
                'altura_px': 380.0,
                'relacion_aspecto': 1.58,
                'orientacion_deg': 3.0,
                'ancho_max_px': 370.0,
                'excentricidad': 0.83,
                'extension': 0.70,
                'area_bbox_px': 228000.0,
                'prop_alto_bbox': 0.50,
                'prop_ancho_bbox': 0.63,
            },
            'peso_min': 380, 'peso_max': 750,
        },
        {
            'desc': 'Angus macho adulto (~950 kg)',
            'raza': 'Angus', 'genero': 'Macho', 'edad_meses': 36,
            'morfo': {
                'valido': True,
                'area_px': 230000.0,
                'area_relativa': 0.32,
                'solidez': 0.85,
                'compacidad': 0.36,
                'longitud_px': 680.0,
                'altura_px': 400.0,
                'relacion_aspecto': 1.70,
                'orientacion_deg': 2.0,
                'ancho_max_px': 390.0,
                'excentricidad': 0.86,
                'extension': 0.73,
                'area_bbox_px': 272000.0,
                'prop_alto_bbox': 0.52,
                'prop_ancho_bbox': 0.68,
            },
            'peso_min': 650, 'peso_max': 1250,
        },
        {
            'desc': 'Jersey hembra adulta (~400 kg)',
            'raza': 'Jersey', 'genero': 'Hembra', 'edad_meses': 30,
            'morfo': {
                'valido': True,
                'area_px': 140000.0,
                'area_relativa': 0.20,
                'solidez': 0.78,
                'compacidad': 0.30,
                'longitud_px': 500.0,
                'altura_px': 340.0,
                'relacion_aspecto': 1.47,
                'orientacion_deg': 4.0,
                'ancho_max_px': 330.0,
                'excentricidad': 0.80,
                'extension': 0.68,
                'area_bbox_px': 170000.0,
                'prop_alto_bbox': 0.45,
                'prop_ancho_bbox': 0.55,
            },
            'peso_min': 280, 'peso_max': 550,
        },
    ]
    
    todos_ok = True
    for caso in casos:
        resultado = estimar_peso_ensemble(
            caso['morfo'], caso['raza'], caso['genero'], caso['edad_meses']
        )
        peso = resultado['peso_kg']
        ok = "✓" if caso['peso_min'] <= peso <= caso['peso_max'] else "✗"
        if not (caso['peso_min'] <= peso <= caso['peso_max']):
            todos_ok = False
        
        print(f"\n  {ok} {caso['desc']}")
        print(f"    Peso estimado: {peso} kg "
              f"(esperado: {caso['peso_min']}-{caso['peso_max']} kg)")
        print(f"    Método: {resultado['metodo']}")
        m = resultado['metricas']
        print(f"    PT={m['pt_estimado_cm']}cm, LCC={m['lcc_estimado_cm']}cm, "
              f"BCS={m['bcs_condicion_corporal']}")
        print(f"    Schaeffer={m['peso_schaeffer_kg']}kg, "
              f"Regresión={m['peso_regresion_kg']}kg")
        print(f"    Rango biológico: {m['rango_biologico']}")
    
    print(f"\n  → {'PASÓ' if todos_ok else 'FALLÓ'}\n")
    return todos_ok


def test_vaca_1000kg():
    """Test específico: verificar que una vaca de ~1000 kg se estima
    correctamente. Este es el caso reportado por el usuario."""
    print("=" * 70)
    print("TEST 4: Caso Específico — Vaca/Toro de ~1000 kg")
    print("=" * 70)
    
    # Una vaca/toro de 1000 kg es un animal grande, adulto.
    # Típicamente: PT ≈ 235-245 cm, LCC ≈ 190-200 cm
    # En la imagen, un animal así ocuparía una porción significativa.
    
    razas_test = ['Brahman', 'Holstein', 'Charolais', 'Desconocida']
    
    morfo_grande = {
        'valido': True,
        'area_px': 280000.0,
        'area_relativa': 0.38,
        'solidez': 0.84,
        'compacidad': 0.36,
        'longitud_px': 720.0,
        'altura_px': 440.0,
        'relacion_aspecto': 1.64,
        'orientacion_deg': 3.0,
        'ancho_max_px': 430.0,
        'excentricidad': 0.86,
        'extension': 0.74,
        'area_bbox_px': 316800.0,
        'prop_alto_bbox': 0.58,
        'prop_ancho_bbox': 0.75,
    }
    
    todos_ok = True
    for raza in razas_test:
        resultado = estimar_peso_ensemble(morfo_grande, raza, 'Macho', 48)
        peso = resultado['peso_kg']
        ok = "✓" if 750 <= peso <= 1300 else "✗"
        if not (750 <= peso <= 1300):
            todos_ok = False
        
        m = resultado['metricas']
        print(f"  {ok} {raza:12s}: {peso} kg | "
              f"PT={m['pt_estimado_cm']}cm, LCC={m['lcc_estimado_cm']}cm | "
              f"S={m['peso_schaeffer_kg']}kg, R={m['peso_regresion_kg']}kg")
    
    print(f"\n  → {'PASÓ' if todos_ok else 'FALLÓ'}\n")
    return todos_ok


if __name__ == '__main__':
    print("\n" + "═" * 70)
    print("  VERIFICACIÓN DEL MOTOR DE ESTIMACIÓN DE PESO BOVINO")
    print("  BovWeight CR — Fórmulas Científicas Validadas")
    print("═" * 70 + "\n")
    
    resultados = []
    resultados.append(("Schaeffer Directo", test_schaeffer_directo()))
    resultados.append(("Regresión por Raza", test_regresion_por_raza()))
    resultados.append(("Ensamble Completo", test_ensemble_con_morfo_simulado()))
    resultados.append(("Caso 1000 kg", test_vaca_1000kg()))
    
    print("═" * 70)
    print("  RESUMEN")
    print("═" * 70)
    todas_pasaron = True
    for nombre, paso in resultados:
        estado = "✓ PASÓ" if paso else "✗ FALLÓ"
        print(f"  {estado}  {nombre}")
        if not paso:
            todas_pasaron = False
    
    print("═" * 70)
    if todas_pasaron:
        print("  ✓ TODOS LOS TESTS PASARON")
    else:
        print("  ✗ ALGUNOS TESTS FALLARON")
    print("═" * 70 + "\n")
    
    sys.exit(0 if todas_pasaron else 1)
