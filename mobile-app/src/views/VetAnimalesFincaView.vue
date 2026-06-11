<template>
  <ion-page>
    <ion-header class="ion-no-border">
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button default-href="/app/veterinario" text="" />
        </ion-buttons>
        <ion-title>{{ finca?.nombre || 'Animales' }}</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true">
      <div class="va-contenido">

        <!-- Encabezado de finca -->
        <div v-if="finca" class="va-finca-banner animar-aparecer">
          <div>
            <span class="etiqueta-seccion">FINCA SELECCIONADA</span>
            <h2 class="titulo-seccion" style="margin:4px 0">{{ finca.nombre }}</h2>
            <p style="font-size:var(--tamano-xs);color:var(--texto-terciario);margin:0">
              📍 {{ finca.ubicacion || 'Ubicación no especificada' }}
            </p>
          </div>
          <div class="va-finca-stats">
            <div class="va-stat">
              <span class="va-stat-valor">{{ animalesFiltrados.length }}</span>
              <span class="va-stat-label">Animales</span>
            </div>
          </div>
        </div>

        <!-- Buscador y filtros -->
        <div class="va-controles animar-aparecer animar-delay-1">
          <div class="va-buscador">
            <span>🔍</span>
            <input
              id="busqueda-animal-vet-finca"
              class="va-buscador-input"
              v-model="busqueda"
              placeholder="Buscar por arete o nombre..."
              type="search"
            />
          </div>
        </div>

        <!-- Error de carga -->
        <div v-if="error" class="estado-vacio animar-aparecer animar-delay-2">
          <span class="estado-vacio__icono">⚠️</span>
          <h3 class="estado-vacio__titulo">Error de Acceso</h3>
          <p class="estado-vacio__descripcion" style="color:var(--peligro)">
            {{ error }}
          </p>
        </div>

        <!-- Cargando -->
        <div v-else-if="cargando" class="estado-vacio animar-aparecer animar-delay-2">
          <span class="cargando-spinner"></span>
          <p style="margin-top:12px;color:var(--texto-terciario)">Cargando animales...</p>
        </div>

        <!-- Sin animales -->
        <div v-else-if="animalesFiltrados.length === 0" class="estado-vacio animar-aparecer animar-delay-2">
          <span class="estado-vacio__icono">🐄</span>
          <h3 class="estado-vacio__titulo">Sin animales</h3>
          <p class="estado-vacio__descripcion">
            {{ busqueda ? 'No se encontraron animales con ese criterio de búsqueda.' : 'Esta finca no tiene animales registrados.' }}
          </p>
        </div>

        <!-- Lista de animales -->
        <section v-else class="animar-aparecer animar-delay-2">
          <span class="etiqueta-seccion">ANIMALES REGISTRADOS</span>
          <div class="va-lista">
            <div
              v-for="animal in animalesFiltrados"
              :key="animal.id"
              class="va-tarjeta"
              @click="irAlHistorial(animal.id)"
            >
              <!-- Fila principal -->
              <div class="va-tarjeta-principal">
                <div class="va-avatar">
                  {{ (animal.nombre || animal.arete).charAt(0).toUpperCase() }}
                </div>
                <div class="va-animal-info">
                  <div class="va-animal-nombre">
                    {{ animal.nombre || '(Sin nombre)' }}
                    <span class="va-arete">{{ animal.arete }}</span>
                  </div>
                  <div class="va-animal-meta">
                    <span>{{ animal.raza || 'Raza no especificada' }}</span>
                    <span class="va-separador">·</span>
                    <span>{{ animal.genero }}</span>
                  </div>
                </div>
                <span class="va-flecha">›</span>
              </div>

              <!-- Métricas secundarias -->
              <div class="va-metricas">
                <div class="va-metrica">
                  <span class="campo-etiqueta">Peso actual</span>
                  <strong>{{ animal.peso_actual ? Math.round(Number(animal.peso_actual)) + ' kg' : 'Sin pesar' }}</strong>
                </div>
                <div class="va-metrica">
                  <span class="campo-etiqueta">Último pesaje</span>
                  <strong>{{ ultimoPesaje(animal) }}</strong>
                </div>
                <div class="va-metrica">
                  <span class="campo-etiqueta">Propósito</span>
                  <strong>{{ animal.proposito || '—' }}</strong>
                </div>
              </div>

              <!-- Acciones rápidas -->
              <div class="va-acciones">
                <button
                  class="boton boton--secundario boton--pequeno"
                  @click.stop="irAlHistorial(animal.id)"
                >
                  📋 Ver Historial
                </button>
                <button
                  class="boton boton--primario boton--pequeno"
                  :disabled="generandoReporte === animal.id"
                  @click.stop="generarReporteRapido(animal)"
                >
                  <span v-if="generandoReporte === animal.id" class="cargando-spinner" style="width:14px;height:14px;border-width:2px"></span>
                  <span v-else>🩺 Reporte PDF</span>
                </button>
              </div>
            </div>
          </div>
        </section>

        <div style="height:32px"></div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup>
/* Vista de Animales por Finca — Módulo Veterinario */
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton } from '@ionic/vue';
import { useAlmacenVeterinario } from '@/stores/veterinario.js';
import api from '@/services/api';

const route      = useRoute();
const router     = useRouter();
const almacenVet = useAlmacenVeterinario();
const fincaId    = route.params.farmId;

const finca    = ref(null);
const animales = ref([]);
const cargando = ref(false);
const busqueda = ref('');
const generandoReporte = ref(null); // id del animal cuyo reporte se está generando
const error    = ref('');

const animalesFiltrados = computed(() => {
  if (!busqueda.value.trim()) return animales.value;
  const termino = busqueda.value.toLowerCase();
  return animales.value.filter(a =>
    a.arete.toLowerCase().includes(termino) ||
    (a.nombre && a.nombre.toLowerCase().includes(termino))
  );
});

function ultimoPesaje(animal) {
  // El endpoint /fincas/{id}/animales no devuelve el historial de pesajes
  // Mostramos la fecha de creación del último pesaje si viene, o indicamos que hay peso actual
  if (animal.updated_at) {
    return new Date(animal.updated_at).toLocaleDateString('es-CR', {
      day: '2-digit', month: '2-digit', year: 'numeric'
    });
  }
  return '—';
}

async function cargarDatos() {
  cargando.value = true;
  error.value = '';
  try {
    const [respFinca, respAnimales] = await Promise.all([
      api.get(`/fincas/${fincaId}`),
      api.get(`/fincas/${fincaId}/animales`),
    ]);
    finca.value   = respFinca.data.datos;
    animales.value = respAnimales.data.datos;
  } catch (err) {
    console.error('Error al cargar datos de la finca:', err);
    error.value = err.response?.data?.mensaje || 'Error al cargar los animales de la finca.';
  } finally {
    cargando.value = false;
  }
}

function irAlHistorial(animalId) {
  router.push(`/app/veterinario/animal/${animalId}`);
}

async function generarReporteRapido(animal) {
  generandoReporte.value = animal.id;
  try {
    const resultado = await almacenVet.generarReportePDF(animal.id);
    if (resultado.exito) {
      const nombre = `reporte_veterinario_${animal.arete}_${new Date().toISOString().split('T')[0]}.pdf`;
      almacenVet.descargarBlob(resultado.blob, nombre);
    } else {
      alert(resultado.error);
    }
  } finally {
    generandoReporte.value = null;
  }
}

onMounted(cargarDatos);
</script>

<style scoped>
.va-contenido { padding: 0 20px; display: flex; flex-direction: column; gap: 20px; }

.va-finca-banner {
  display: flex; justify-content: space-between; align-items: flex-start;
  background: var(--primario-ultra-suave); border: 1.5px solid var(--primario-suave);
  border-radius: var(--borde-radio-lg); padding: 16px 18px;
}
.va-finca-stats { display: flex; gap: 16px; }
.va-stat {
  display: flex; flex-direction: column; align-items: center; text-align: center;
  background: var(--superficie-tarjeta); border-radius: var(--borde-radio-md);
  padding: 10px 16px; min-width: 64px;
}
.va-stat-valor {
  font-family: var(--fuente-display); font-weight: 800;
  font-size: var(--tamano-2xl); color: var(--primario);
}
.va-stat-label { font-size: 10px; color: var(--texto-terciario); text-transform: uppercase; }

.va-controles { display: flex; flex-direction: column; gap: 10px; }
.va-buscador {
  display: flex; align-items: center; gap: 10px;
  background: var(--superficie-tarjeta); border: 1.5px solid var(--borde-color);
  border-radius: var(--borde-radio-md); padding: 10px 14px;
}
.va-buscador-input {
  flex: 1; background: transparent; border: none; outline: none;
  font-family: var(--fuente-cuerpo); font-size: var(--tamano-sm); color: var(--texto-primario);
}

.va-lista { display: flex; flex-direction: column; gap: 12px; margin-top: 10px; }
.va-tarjeta {
  background: var(--superficie-tarjeta); border: 1.5px solid var(--borde-color);
  border-radius: var(--borde-radio-lg); padding: 16px;
  display: flex; flex-direction: column; gap: 12px;
  cursor: pointer; transition: all var(--transicion-normal);
}
.va-tarjeta:hover {
  border-color: var(--primario-suave);
  box-shadow: 0 4px 14px rgba(101,109,74,0.1);
  transform: translateY(-1px);
}

.va-tarjeta-principal { display: flex; align-items: center; gap: 12px; }
.va-avatar {
  width: 44px; height: 44px; border-radius: 50%;
  background: var(--primario-ultra-suave);
  display: flex; align-items: center; justify-content: center;
  font-family: var(--fuente-display); font-weight: 800;
  font-size: 1.1rem; color: var(--primario); flex-shrink: 0;
}
.va-animal-info { flex: 1; display: flex; flex-direction: column; gap: 3px; }
.va-animal-nombre {
  font-weight: 700; font-size: var(--tamano-base); display: flex; align-items: center; gap: 8px;
}
.va-arete {
  font-family: monospace; font-size: var(--tamano-xs);
  color: var(--texto-terciario); font-weight: 400;
}
.va-animal-meta { font-size: var(--tamano-xs); color: var(--texto-secundario); }
.va-separador { margin: 0 4px; color: var(--borde-color); }
.va-flecha { font-size: 1.4rem; color: var(--texto-terciario); }

.va-metricas {
  display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px;
  background: var(--superficie-hundida); border-radius: var(--borde-radio-md); padding: 10px 12px;
}
.va-metrica { display: flex; flex-direction: column; gap: 3px; }
.va-metrica strong { font-size: var(--tamano-sm); color: var(--texto-primario); }

.va-acciones {
  display: flex; gap: 10px;
  padding-top: 10px; border-top: 1px solid var(--borde-color);
}
.va-acciones .boton { flex: 1; justify-content: center; }
</style>
