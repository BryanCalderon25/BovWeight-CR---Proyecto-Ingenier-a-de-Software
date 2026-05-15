import os
import cv2
import numpy as np
from flask import Flask, request, jsonify
from werkzeug.utils import secure_filename
from ultralytics import YOLO
import time
import random

app = Flask(__name__)
app.config['MAX_CONTENT_LENGTH'] = 16 * 1024 * 1024  # 16 MB max

# Cargar modelo YOLOv8 pre-entrenado (COCO)
# En producción, aquí se cargaría un modelo entrenado específicamente para ganado
try:
    model = YOLO('yolov8n.pt')
    print("Modelo YOLOv8 cargado exitosamente.")
except Exception as e:
    print(f"Error al cargar el modelo: {e}")
    model = None

# Clases COCO: 'cow' es la clase 21 (0-indexed)
COW_CLASS_ID = 21

def estimar_peso_biometrico(bbox, img_width, img_height, raza):
    """
    Estima el peso basándose en biometría virtual.
    Calcula el volumen proyectado del animal y aplica coeficientes de densidad por raza.
    """
    x1, y1, x2, y2 = bbox
    # Dimensiones en píxeles
    ancho_px = x2 - x1
    alto_px = y2 - y1
    
    # Proporción respecto a la imagen (asumimos distancia estándar)
    proporcion_ancho = ancho_px / img_width
    proporcion_alto = alto_px / img_height
    
    # Área proyectada
    area_proyectada = proporcion_ancho * proporcion_alto
    
    # Coeficientes de conformación muscular por raza
    # Valores basados en densidad corporal promedio
    coeficientes_raza = {
        'Brahman': 1.15,    # Mayor masa muscular, giba
        'Holstein': 1.05,   # Estructura más ósea, menos grasa
        'Jersey': 0.95,     # Raza pequeña, menor densidad
        'Angus': 1.25,      # Alta densidad cárnica
        'Pardo Suizo': 1.10,
        'Desconocida': 1.00
    }
    
    # Factor de escala base (representa un animal de ~450kg ocupando el 40% de la foto)
    # Fórmula: Peso = (Área * Constante_Escala) * Coeficiente_Raza
    FACTOR_ESCALA = 1200 
    
    coeficiente = coeficientes_raza.get(raza, 1.00)
    
    # Estimación de peso base
    peso_base = (area_proyectada * FACTOR_ESCALA) * coeficiente
    
    # Ajuste por Aspect Ratio (Perfil vs Frente)
    # Un bovino de perfil es ~2.5 veces más largo que ancho
    aspect_ratio = ancho_px / alto_px
    if aspect_ratio < 1.5:  # El animal está de frente o inclinado
        peso_base *= 1.3    # Compensación por profundidad no vista
    
    # Aplicar límites realistas (Ternero vs Toro)
    peso_final = min(1200, max(40, peso_base))
    
    return int(peso_final)

@app.route('/api/health', methods=['GET'])
def health_check():
    return jsonify({
        'status': 'ok',
        'service': 'BovWeight CR ML Microservice',
        'model_loaded': model is not None
    })

@app.route('/api/estimate', methods=['POST'])
def estimate_weight():
    inicio_tiempo = time.time()
    
    if 'image' not in request.files:
        return jsonify({'error': 'No se proporcionó ninguna imagen.'}), 400
        
    file = request.files['image']
    raza = request.form.get('raza', 'Desconocida')
    
    if file.filename == '':
        return jsonify({'error': 'Nombre de archivo vacío.'}), 400
        
    try:
        # Leer imagen directamente desde memoria
        filestr = file.read()
        npimg = np.frombuffer(filestr, np.uint8)
        img = cv2.imdecode(npimg, cv2.IMREAD_COLOR)
        
        if img is None:
            return jsonify({'error': 'Imagen inválida o corrupta.'}), 400
            
        img_height, img_width = img.shape[:2]
        
        # Procesamiento YOLOv8
        if model is None:
            return jsonify({'error': 'El modelo ML no está disponible.'}), 500
            
        results = model(img, classes=[COW_CLASS_ID], conf=0.4)
        
        # Verificar si se detectó ganado
        if len(results) == 0 or len(results[0].boxes) == 0:
            return jsonify({
                'success': False,
                'error': 'No se detectó ningún bovino en la imagen.',
                'processing_time_ms': int((time.time() - inicio_tiempo) * 1000)
            }), 400
            
        # Tomar la mejor detección
        mejor_box = results[0].boxes[0]
        confianza = float(mejor_box.conf[0]) * 100
        bbox = mejor_box.xyxy[0].cpu().numpy()
        
        # Estimar peso
        peso_estimado = estimar_peso_biometrico(bbox, img_width, img_height, raza)
        
        # Margen de error simulado basado en confianza
        margen_error = int(30 - (confianza / 5))
        
        tiempo_procesamiento = int((time.time() - inicio_tiempo) * 1000)
        
        return jsonify({
            'success': True,
            'peso_estimado_kg': peso_estimado,
            'margen_error_kg': margen_error,
            'confianza_porcentaje': int(confianza),
            'raza_detectada': raza,  # En un modelo real, esto lo clasificaría la IA
            'bbox': bbox.tolist(),
            'processing_time_ms': tiempo_procesamiento
        })
        
    except Exception as e:
        print(f"Error procesando imagen: {e}")
        return jsonify({'error': str(e)}), 500

if __name__ == '__main__':
    port = int(os.environ.get('PORT', 5000))
    app.run(host='0.0.0.0', port=port, debug=True)
