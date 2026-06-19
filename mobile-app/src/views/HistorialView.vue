<template>
  <ion-page>
    <ion-header class="ion-no-border">
      <ion-toolbar>
        <ion-title class="encabezado-titulo">
          <span class="t-bov">Bov</span><span>Weight</span><span class="t-cr">CR</span>
        </ion-title>

      </ion-toolbar>
    </ion-header>
    <ion-content :fullscreen="true">
      <div class="historial-contenido">

        <!-- Selector de sección -->
        <div class="historial-segmentos animar-aparecer">
          <button 
            class="segmento-btn" 
            :class="{ 'segmento-btn--activo': seccionActiva === 'peso' }"
            @click="seccionActiva = 'peso'"
          >
            ⚖️ Evolución de Peso
          </button>
          <button 
            class="segmento-btn" 
            :class="{ 'segmento-btn--activo': seccionActiva === 'veterinario' }"
            @click="seccionActiva = 'veterinario'"
          >
            🩺 Historial Veterinario
          </button>
        </div>

        <!-- Sección de Evolución de Peso -->
        <div v-show="seccionActiva === 'peso'" class="seccion-peso-flujo">
          <!-- Encabezado -->
          <section class="animar-aparecer">
            <span class="etiqueta-seccion">PANEL DE ANALÍTICA</span>
            <h2 class="titulo-seccion">Evolución de Peso</h2>
            <div class="insignia insignia--primario" style="margin-top:8px">
              TOTAL DEL HATO: {{ almacenPesajes.pesoTotalHato.toLocaleString() }} kg
            </div>
          </section>

          <!-- Gráfico de tendencia -->
          <div class="tarjeta animar-aparecer animar-delay-1">
            <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
              <div>
                <h3 style="font-family:var(--fuente-display);font-weight:600;font-size:var(--tamano-base)">
                  Tendencia de Crecimiento Mensual
                </h3>
                <p style="font-size:var(--tamano-xs);color:var(--texto-terciario)">Incremento de peso promedio por cabeza</p>
              </div>
              <span class="insignia insignia--primario">Últimos 6 Meses</span>
            </div>
            <div class="grafico-contenedor">
              <canvas ref="canvasGrafico"></canvas>
            </div>
          </div>

          <!-- Mejor rendimiento -->
          <section class="animar-aparecer animar-delay-2">
            <span class="etiqueta-seccion">MEJOR RENDIMIENTO</span>
            <div class="historial-top">
              <div class="historial-top-item" v-for="top in topAnimales" :key="top.id">
                <div class="historial-top-avatar">{{ top.nombre.charAt(0) }}</div>
                <div>
                  <strong>{{ top.nombre }}</strong>
                  <span style="display:block;font-size:var(--tamano-xs);color:var(--texto-terciario)">{{ top.raza }}</span>
                </div>
                <span class="historial-top-peso">{{ top.pesoEstimado }} kg</span>
              </div>
            </div>
          </section>

          <!-- Timeline de pesajes -->
          <section class="animar-aparecer animar-delay-3">
            <span class="etiqueta-seccion">LÍNEA DE TIEMPO</span>
            <div class="historial-timeline">
              <div v-for="pesaje in almacenPesajes.ultimosPesajes" :key="pesaje.id" class="timeline-item">
                <div class="timeline-linea">
                  <div class="timeline-punto"></div>
                  <div class="timeline-barra"></div>
                </div>
                <div class="timeline-contenido">
                  <div class="timeline-cabecera">
                    <strong>{{ pesaje.nombreAnimal }}</strong>
                    <span class="insignia insignia--primario">{{ pesaje.raza }}</span>
                  </div>
                  <div class="timeline-detalles">
                    <span>📊 {{ pesaje.pesoEstimado }} kg</span>
                    <span>📍 {{ pesaje.finca }}</span>
                    <span>📅 {{ pesaje.fecha }}</span>
                  </div>
                  <div class="timeline-confianza">
                    <div class="timeline-confianza-barra">
                      <div class="timeline-confianza-progreso" :style="{ width: pesaje.confianza + '%' }"></div>
                    </div>
                    <span>{{ pesaje.confianza }}% confianza</span>
                  </div>
                </div>
              </div>
            </div>
          </section>

          <!-- Estadísticas de ganancia -->
          <div class="tarjeta tarjeta--metrica animar-aparecer animar-delay-4">
            <span class="etiqueta-seccion">GANANCIA DIARIA PROMEDIO</span>
            <div class="metrica-fila">
              <span class="metrica-grande" style="font-size:var(--tamano-3xl);color:var(--exito)">+1.2</span>
              <span class="metrica-unidad">kg/día</span>
            </div>
            <p style="font-size:var(--tamano-xs);color:var(--texto-terciario);margin-top:4px">
              Basado en los últimos 30 días
            </p>
          </div>
        </div>

        <!-- Sección de Historial Veterinario -->
        <div v-if="seccionActiva === 'veterinario'" class="animar-aparecer seccion-vet-flujo">
          <section>
            <span class="etiqueta-seccion">SEGUIMIENTO CLÍNICO</span>
            <h2 class="titulo-seccion">Historial Veterinario</h2>
            <p style="font-size:var(--tamano-sm);color:var(--texto-secundario);margin-top:4px">
              Consulte diagnósticos, tratamientos y reportes veterinarios de sus animales.
            </p>
          </section>

          <!-- Buscador de animales -->
          <div class="vf-buscador">
            <span>🔍</span>
            <input
              id="busqueda-animal-vet-ganadero"
              class="vf-buscador-input"
              v-model="busquedaAnimal"
              placeholder="Buscar por arete o nombre..."
              type="search"
            />
          </div>

          <!-- Cargando animales -->
          <div v-if="almacenAnimales.cargando" class="estado-vacio">
            <span class="cargando-spinner"></span>
            <p style="margin-top:12px;color:var(--texto-terciario)">Cargando animales...</p>
          </div>

          <!-- Sin animales -->
          <div v-else-if="animalesDelGanadero.length === 0" class="estado-vacio">
            <span class="estado-vacio__icono">🐄</span>
            <h3 class="estado-vacio__titulo">Sin animales</h3>
            <p class="estado-vacio__descripcion">
              No se encontraron animales. Asegúrese de registrar animales en sus fincas primero.
            </p>
          </div>

          <!-- Lista de animales para historial veterinario -->
          <div v-else class="historial-vet-lista">
            <div 
              v-for="animal in animalesDelGanadero" 
              :key="animal.id" 
              class="historial-vet-tarjeta"
              @click="irAlHistorialVet(animal.id)"
            >
              <div class="avatar-vet">
                {{ (animal.nombre || animal.arete).charAt(0).toUpperCase() }}
              </div>
              <div class="info-vet">
                <div class="nombre-vet">
                  {{ animal.nombre || '(Sin nombre)' }}
                  <span class="arete-vet">{{ animal.arete }}</span>
                </div>
                <div class="meta-vet">
                  {{ animal.raza || 'Raza no especificada' }} · {{ animal.genero || 'Bovino' }}
                </div>
              </div>
              <div class="accion-vet">
                <button class="boton boton--primario boton--pequeno" @click.stop="irAlHistorialVet(animal.id)">
                  Ver Historial
                </button>
              </div>
            </div>
          </div>
        </div>

        <div style="height:32px"></div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup>
/* Vista de Historial — Reutilizada para Analítica de Peso y acceso a Historial Veterinario del Ganadero */
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonButton, IonIcon } from '@ionic/vue';

import { useAlmacenPesajes } from '@/stores/pesajes.js';
import { useAlmacenFincas } from '@/stores/fincas.js';
import { useAlmacenAnimales } from '@/stores/animales.js';

const router = useRouter();
const almacenPesajes = useAlmacenPesajes();
const almacenFincas = useAlmacenFincas();
const almacenAnimales = useAlmacenAnimales();

const canvasGrafico = ref(null);
const seccionActiva = ref('peso'); // 'peso' | 'veterinario'
const busquedaAnimal = ref('');

const topAnimales = computed(() => {
  return [...almacenPesajes.lista]
    .map(p => ({
      id: p.id,
      nombre: p.animal?.nombre || p.nombreAnimal || 'Bovino',
      raza: p.animal?.raza || p.raza || 'Desconocida',
      pesoEstimado: Math.round(Number(p.peso_estimado || p.pesoEstimado || 0))
    }))
    .sort((a, b) => b.pesoEstimado - a.pesoEstimado)
    .slice(0, 3);
});

const animalesDelGanadero = computed(() => {
  if (!busquedaAnimal.value.trim()) return almacenAnimales.lista;
  const termino = busquedaAnimal.value.toLowerCase();
  return almacenAnimales.lista.filter(a =>
    a.arete.toLowerCase().includes(termino) ||
    (a.nombre && a.nombre.toLowerCase().includes(termino))
  );
});

function irAlHistorialVet(animalId) {
  router.push(`/app/veterinario/animal/${animalId}`);
}

onMounted(async () => {
  // Cargar pesajes reales para el gráfico e historial
  await almacenPesajes.cargarTodosLosPesajes();

  // Cargar fincas y animales reales del ganadero
  try {
    await almacenFincas.cargarFincas();
    const promesas = almacenFincas.lista.map(f => almacenAnimales.cargarAnimalesPorFinca(f.id));
    await Promise.all(promesas);
  } catch (err) {
    console.error('Error al cargar fincas o animales en HistorialView:', err);
  }

  // Dibujar gráfico simple con Canvas
  if (!canvasGrafico.value) return;
  const ctx = canvasGrafico.value.getContext('2d');
  const ancho = canvasGrafico.value.parentElement.clientWidth;
  const alto = 180;
  canvasGrafico.value.width = ancho;
  canvasGrafico.value.height = alto;

  const datos = almacenPesajes.datosTendencia;
  const valores = datos.valores;
  const etiquetas = datos.etiquetas;
  const maximo = Math.max(...valores) * 1.1;
  const minimo = Math.min(...valores) * 0.9;
  const rango = maximo - minimo;
  const margen = { arriba: 20, abajo: 30, izq: 10, der: 10 };
  const anchoUtil = ancho - margen.izq - margen.der;
  const altoUtil = alto - margen.arriba - margen.abajo;

  /* Fondo gradiente */
  const gradFondo = ctx.createLinearGradient(0, margen.arriba, 0, alto - margen.abajo);
  gradFondo.addColorStop(0, 'rgba(101, 109, 74, 0.15)');
  gradFondo.addColorStop(1, 'rgba(101, 109, 74, 0.01)');

  /* Dibujar área */
  ctx.beginPath();
  valores.forEach((v, i) => {
    const x = margen.izq + (i / (valores.length - 1)) * anchoUtil;
    const y = margen.arriba + (1 - (v - minimo) / rango) * altoUtil;
    if (i === 0) ctx.moveTo(x, y);
    else ctx.lineTo(x, y);
  });
  ctx.lineTo(margen.izq + anchoUtil, alto - margen.abajo);
  ctx.lineTo(margen.izq, alto - margen.abajo);
  ctx.closePath();
  ctx.fillStyle = gradFondo;
  ctx.fill();

  /* Línea */
  ctx.beginPath();
  valores.forEach((v, i) => {
    const x = margen.izq + (i / (valores.length - 1)) * anchoUtil;
    const y = margen.arriba + (1 - (v - minimo) / rango) * altoUtil;
    if (i === 0) ctx.moveTo(x, y);
    else ctx.lineTo(x, y);
  });
  ctx.strokeStyle = '#656D4A';
  ctx.lineWidth = 2.5;
  ctx.lineJoin = 'round';
  ctx.stroke();

  /* Puntos */
  valores.forEach((v, i) => {
    const x = margen.izq + (i / (valores.length - 1)) * anchoUtil;
    const y = margen.arriba + (1 - (v - minimo) / rango) * altoUtil;
    ctx.beginPath();
    ctx.arc(x, y, 4, 0, Math.PI * 2);
    ctx.fillStyle = '#414833';
    ctx.fill();
    ctx.strokeStyle = '#fff';
    ctx.lineWidth = 2;
    ctx.stroke();
  });

  /* Etiquetas */
  ctx.fillStyle = '#8B8E83';
  ctx.font = '11px Inter, sans-serif';
  ctx.textAlign = 'center';
  etiquetas.forEach((label, i) => {
    const x = margen.izq + (i / (etiquetas.length - 1)) * anchoUtil;
    ctx.fillText(label, x, alto - 8);
  });
});
</script>

<style scoped>
.encabezado-titulo{font-family:var(--fuente-display);font-weight:800;font-size:1.2rem}
.t-bov{color:var(--primario-medio)}.t-cr{color:var(--acento);font-size:0.9rem}
.historial-contenido{padding:0 20px;display:flex;flex-direction:column;gap:20px}
.grafico-contenedor{width:100%;overflow:hidden;border-radius:var(--borde-radio-sm)}
.seccion-peso-flujo { display: flex; flex-direction: column; gap: 20px; }
.seccion-vet-flujo { display: flex; flex-direction: column; gap: 20px; }

/* Segmentos */
.historial-segmentos {
  display: flex;
  background: var(--superficie-hundida);
  border-radius: 30px;
  padding: 4px;
  border: 1.5px solid var(--borde-color);
}
.segmento-btn {
  flex: 1;
  border: none;
  background: transparent;
  padding: 10px 16px;
  font-family: var(--fuente-cuerpo);
  font-size: var(--tamano-xs);
  font-weight: 600;
  color: var(--texto-secundario);
  border-radius: 26px;
  cursor: pointer;
  transition: all var(--transicion-normal);
}
.segmento-btn--activo {
  background: var(--superficie-tarjeta);
  color: var(--primario);
  box-shadow: 0 2px 10px rgba(0,0,0,0.06);
}

/* Buscador */
.vf-buscador {
  display: flex; align-items: center; gap: 10px;
  background: var(--superficie-tarjeta);
  border: 1.5px solid var(--borde-color);
  border-radius: var(--borde-radio-md);
  padding: 10px 14px;
}
.vf-buscador-input {
  flex: 1; background: transparent; border: none; outline: none;
  font-family: var(--fuente-cuerpo); font-size: var(--tamano-sm); color: var(--texto-primario);
}

/* Timeline */
.historial-top{display:flex;flex-direction:column;gap:8px;margin-top:12px}
.historial-top-item{display:flex;align-items:center;gap:12px;padding:14px 16px;background:var(--superficie-tarjeta);border:1px solid var(--borde-color);border-radius:var(--borde-radio-md)}
.historial-top-avatar{width:40px;height:40px;border-radius:50%;background:var(--acento-ultra-suave);display:flex;align-items:center;justify-content:center;font-family:var(--fuente-display);font-weight:700;color:var(--acento)}
.historial-top-peso{margin-left:auto;font-family:var(--fuente-display);font-weight:700;font-size:var(--tamano-lg);color:var(--primario)}
.historial-timeline{display:flex;flex-direction:column;gap:0;margin-top:12px}
.timeline-item{display:flex;gap:16px}
.timeline-linea{display:flex;flex-direction:column;align-items:center;width:16px}
.timeline-punto{width:12px;height:12px;border-radius:50%;background:var(--primario);border:3px solid var(--primario-suave);flex-shrink:0;margin-top:4px}
.timeline-barra{width:2px;flex:1;background:var(--primario-suave);min-height:20px}
.timeline-item:last-child .timeline-barra{display:none}
.timeline-contenido{flex:1;padding-bottom:20px}
.timeline-cabecera{display:flex;align-items:center;gap:8px;margin-bottom:6px}
.timeline-detalles{display:flex;flex-wrap:wrap;gap:12px;font-size:var(--tamano-xs);color:var(--texto-secundario)}
.timeline-confianza{display:flex;align-items:center;gap:8px;margin-top:8px}
.timeline-confianza-barra{flex:1;height:4px;background:var(--superficie-hundida);border-radius:2px;overflow:hidden}
.timeline-confianza-progreso{height:100%;background:var(--primario);border-radius:2px;transition:width 0.6s ease}
.timeline-confianza span{font-size:var(--tamano-xs);color:var(--texto-terciario);white-space:nowrap}
.metrica-fila{display:flex;align-items:baseline;margin-top:8px}

/* Listado Veterinario para Ganadero */
.historial-vet-lista {
  display: flex;
  flex-direction: column;
  gap: 12px;
}
.historial-vet-tarjeta {
  background: var(--superficie-tarjeta);
  border: 1.5px solid var(--borde-color);
  border-radius: var(--borde-radio-lg);
  padding: 14px 16px;
  display: flex;
  align-items: center;
  gap: 12px;
  cursor: pointer;
  transition: all var(--transicion-normal);
}
.historial-vet-tarjeta:hover {
  border-color: var(--primario-suave);
  transform: translateY(-2px);
  box-shadow: 0 4px 14px rgba(101,109,74,0.1);
}
.avatar-vet {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: var(--primario-ultra-suave);
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--fuente-display);
  font-weight: 800;
  font-size: 1.1rem;
  color: var(--primario);
  flex-shrink: 0;
}
.info-vet {
  flex: 1;
}
.nombre-vet {
  font-weight: 700;
  font-size: var(--tamano-base);
  color: var(--texto-primario);
  display: flex;
  align-items: center;
  gap: 8px;
}
.arete-vet {
  font-family: monospace;
  font-size: var(--tamano-xs);
  color: var(--texto-terciario);
  font-weight: 400;
}
.meta-vet {
  font-size: var(--tamano-xs);
  color: var(--texto-secundario);
  margin-top: 2px;
}
.accion-vet {
  flex-shrink: 0;
}
</style>
