<template>
  <ion-page>
    <ion-header class="ion-no-border">
      <ion-toolbar>
        <ion-title class="encabezado-titulo">
          <span class="t-bov">Bov</span><span>Weight</span><span class="t-cr">CR</span>
        </ion-title>
        <ion-buttons slot="end">
          <ion-button><ion-icon :icon="searchOutline" /></ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>
    <ion-content :fullscreen="true">
      <div class="pesaje-contenido">
        <!-- Encabezado -->
        <section class="animar-aparecer">
          <span class="etiqueta-seccion">REGISTRO OPERATIVO</span>
          <h2 class="titulo-seccion">Nuevo Pesaje</h2>
          <p class="pesaje-desc">
            Usa Visión IA para estimación o registra datos manuales. Modo de alta precisión activo.
          </p>
        </section>

        <!-- Sección de captura -->
        <section class="animar-aparecer animar-delay-1">
          <span class="etiqueta-seccion">ID VISUAL Y ESTIMACIÓN IA</span>
          <div class="pesaje-opciones">
            <div class="tarjeta tarjeta--accion" @click="manejarCaptura('camara')">
              <span style="font-size:32px">📸</span>
              <strong>Tomar Foto</strong>
            </div>
            <div class="tarjeta tarjeta--accion" @click="manejarCaptura('archivo')">
              <span style="font-size:32px">📁</span>
              <strong>Subir Imagen</strong>
            </div>
          </div>
          <!-- Inputs ocultos para captura de archivos y cámara nativa -->
          <input ref="inputArchivo" type="file" accept="image/*" style="display:none" @change="manejarArchivo" />
          <input ref="inputCamara" type="file" accept="image/*" capture="environment" style="display:none" @change="manejarArchivo" />
        </section>

        <!-- Área de análisis -->
        <section class="animar-aparecer animar-delay-2">
          <div class="pesaje-analisis">
            <div class="insignia insignia--exito" style="align-self:flex-end">⚡ ANÁLISIS INTELIGENTE</div>
            <div v-if="!imagenCapturada && !almacenPesajes.procesando" class="pesaje-placeholder">
              <span style="font-size:48px;opacity:0.4">🖼️</span>
              <span class="pesaje-placeholder-texto">ESPERANDO CAPTURA</span>
            </div>
            <!-- Procesando -->
            <div v-if="almacenPesajes.procesando" class="pesaje-procesando">
              <div class="cargando-spinner" style="width:48px;height:48px"></div>
              <p>Analizando imagen...</p>
              <p class="pesaje-procesando-detalle">Detectando ganado y estimando peso</p>
            </div>
            <!-- Imagen capturada -->
            <div v-if="imagenCapturada && !almacenPesajes.procesando && !resultadoVisible" class="pesaje-preview">
              <img :src="imagenCapturada" alt="Captura" class="pesaje-imagen" />
              <p style="font-size:var(--tamano-xs);color:var(--texto-terciario)">Imagen lista para análisis</p>
            </div>
            <!-- Resultado -->
            <div v-if="resultadoVisible" class="pesaje-resultado">
              <div class="pesaje-resultado-peso">
                <span class="metrica-grande">{{ almacenPesajes.resultadoActual?.pesoEstimado }}</span>
                <span class="metrica-unidad">kg</span>
              </div>
              <div class="pesaje-resultado-detalles">
                <div class="pesaje-resultado-item">
                  <span class="campo-etiqueta">Margen de error</span>
                  <span>± {{ almacenPesajes.resultadoActual?.margenError }} kg</span>
                </div>
                <div class="pesaje-resultado-item">
                  <span class="campo-etiqueta">Confianza</span>
                  <span>{{ almacenPesajes.resultadoActual?.confianza }}%</span>
                </div>
                <div class="pesaje-resultado-item">
                  <span class="campo-etiqueta">Raza</span>
                  <span>{{ almacenPesajes.resultadoActual?.raza }}</span>
                </div>
              </div>
              <div class="pesaje-aviso">
                ⚠️ Este peso es una estimación y NO sustituye una báscula oficial.
              </div>
              <button class="boton boton--primario boton--completo" @click="nuevoPesaje">
                Realizar otro pesaje
              </button>
            </div>
          </div>
        </section>

        <!-- Identificación del animal -->
        <section class="animar-aparecer animar-delay-3">
          <span class="etiqueta-seccion">IDENTIFICACIÓN DEL ANIMAL</span>
          <div class="barra-busqueda" style="margin-top:12px">
            <span class="barra-busqueda__icono">🔍</span>
            <input class="barra-busqueda__input" v-model="busquedaAnimal"
              placeholder="Buscar por ID o Número..." />
          </div>
          <!-- Resultados de búsqueda -->
          <div v-if="animalesFiltrados.length && busquedaAnimal" class="pesaje-animales-lista">
            <div v-for="animal in animalesFiltrados" :key="animal.id"
              class="pesaje-animal-item" :class="{ 'pesaje-animal-item--seleccionado': animalSeleccionado?.id === animal.id }"
              @click="seleccionarAnimal(animal)">
              <strong>{{ animal.arete }}</strong> — {{ animal.nombre }}
              <span class="insignia insignia--primario" style="margin-left:auto">{{ animal.raza }}</span>
            </div>
          </div>
          <!-- Animal seleccionado -->
          <div v-if="animalSeleccionado" class="pesaje-animal-seleccionado">
            <span>🐄</span>
            <div>
              <strong>{{ animalSeleccionado.nombre }}</strong>
              <span style="display:block;font-size:var(--tamano-xs);color:var(--texto-terciario)">
                {{ animalSeleccionado.arete }} · {{ animalSeleccionado.raza }}
              </span>
            </div>
            <button class="boton boton--secundario boton--pequeno" @click="animalSeleccionado = null">Cambiar</button>
          </div>
        </section>

        <!-- Botón de análisis -->
        <button v-if="imagenCapturada && animalSeleccionado && !resultadoVisible"
          class="boton boton--acento boton--completo boton--grande animar-aparecer"
          :disabled="almacenPesajes.procesando" @click="ejecutarEstimacion">
          <span v-if="almacenPesajes.procesando" class="cargando-spinner" style="width:20px;height:20px;border-width:2px"></span>
          <span v-else>🔬 Iniciar Estimación</span>
        </button>

        <div style="height:32px"></div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup>
/* Vista de Pesaje — diseño basado en Stitch "Nuevo Pesaje Inteligente" */
import { ref, computed } from 'vue';
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonButton, IonIcon } from '@ionic/vue';
import { searchOutline } from 'ionicons/icons';
import { useAlmacenAnimales } from '@/stores/animales.js';
import { useAlmacenPesajes } from '@/stores/pesajes.js';

const almacenAnimales = useAlmacenAnimales();
const almacenPesajes = useAlmacenPesajes();

const imagenCapturada = ref(null);
const busquedaAnimal = ref('');
const animalSeleccionado = ref(null);
const resultadoVisible = ref(false);
const inputArchivo = ref(null);
const inputCamara = ref(null);

const animalesFiltrados = computed(() => {
  if (!busquedaAnimal.value) return [];
  const t = busquedaAnimal.value.toLowerCase();
  return almacenAnimales.lista.filter(a =>
    a.nombre.toLowerCase().includes(t) || a.arete.toLowerCase().includes(t)
  ).slice(0, 5);
});

function manejarCaptura(tipo) {
  if (tipo === 'archivo') {
    inputArchivo.value?.click();
  } else {
    inputCamara.value?.click();
  }
}

function manejarArchivo(evento) {
  const archivo = evento.target.files[0];
  if (!archivo) return;
  const lector = new FileReader();
  lector.onload = (e) => { imagenCapturada.value = e.target.result; };
  lector.readAsDataURL(archivo);
}

function seleccionarAnimal(animal) {
  animalSeleccionado.value = animal;
  busquedaAnimal.value = '';
}

async function ejecutarEstimacion() {
  if (!animalSeleccionado.value || !imagenCapturada.value) return;
  const resultado = await almacenPesajes.estimarPeso(imagenCapturada.value, animalSeleccionado.value);
  if (resultado.exito) {
    resultadoVisible.value = true;
    // Recargar datos en el almacén de animales para actualizar el peso de forma automática en todo el sistema
    if (animalSeleccionado.value.farm_id) {
      almacenAnimales.cargarAnimalesPorFinca(animalSeleccionado.value.farm_id);
    }
  } else {
    alert(resultado.error || 'No se pudo completar el análisis');
  }
}

function nuevoPesaje() {
  imagenCapturada.value = null;
  animalSeleccionado.value = null;
  resultadoVisible.value = false;
  almacenPesajes.resultadoActual = null;
  
  // Limpiar el valor del input tipo archivo para permitir seleccionar el mismo archivo consecutivamente
  if (inputArchivo.value) {
    inputArchivo.value.value = '';
  }
}
</script>

<style scoped>
.encabezado-titulo{font-family:var(--fuente-display);font-weight:800;font-size:1.2rem}
.t-bov{color:var(--primario-medio)}.t-cr{color:var(--acento);font-size:0.9rem}
.pesaje-contenido{padding:0 20px;display:flex;flex-direction:column;gap:20px}
.pesaje-desc{font-size:var(--tamano-sm);color:var(--texto-secundario);margin-top:4px}
.pesaje-opciones{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:12px}
.pesaje-analisis{background:var(--superficie-tarjeta);border-radius:var(--borde-radio-lg);padding:20px;border:1px solid var(--borde-color);display:flex;flex-direction:column;gap:16px;margin-top:12px}
.pesaje-placeholder{display:flex;flex-direction:column;align-items:center;justify-content:center;height:160px;gap:12px;border:2px dashed var(--borde-color);border-radius:var(--borde-radio-md)}
.pesaje-placeholder-texto{font-size:var(--tamano-xs);color:var(--texto-terciario);letter-spacing:1px}
.pesaje-procesando{display:flex;flex-direction:column;align-items:center;justify-content:center;height:160px;gap:12px}
.pesaje-procesando p{font-weight:600;color:var(--texto-primario)}
.pesaje-procesando-detalle{font-size:var(--tamano-xs)!important;color:var(--texto-terciario)!important;font-weight:400!important}
.pesaje-preview{text-align:center}
.pesaje-imagen{width:100%;height:220px;object-fit:contain;background-color:var(--primario-ultra-suave, #f4f6f0);border:1px solid var(--borde-color);border-radius:var(--borde-radio-md);margin-bottom:8px}
.pesaje-resultado{display:flex;flex-direction:column;gap:16px}
.pesaje-resultado-peso{display:flex;align-items:baseline;justify-content:center;gap:4px;padding:16px 0}
.pesaje-resultado-detalles{display:grid;grid-template-columns:1fr 1fr 1fr;gap:8px}
.pesaje-resultado-item{display:flex;flex-direction:column;gap:4px;text-align:center;font-size:var(--tamano-sm);font-weight:600}
.pesaje-aviso{background:var(--advertencia-suave);color:#E65100;padding:12px;border-radius:var(--borde-radio-md);font-size:var(--tamano-xs);text-align:center;font-weight:500}
.pesaje-animales-lista{display:flex;flex-direction:column;gap:4px;margin-top:8px;max-height:200px;overflow-y:auto}
.pesaje-animal-item{display:flex;align-items:center;gap:8px;padding:12px 16px;background:var(--superficie-tarjeta);border:1px solid var(--borde-color);border-radius:var(--borde-radio-md);cursor:pointer;font-size:var(--tamano-sm);transition:background var(--transicion-rapida)}
.pesaje-animal-item:hover{background:var(--primario-ultra-suave)}
.pesaje-animal-item--seleccionado{background:var(--primario-ultra-suave);border-color:var(--primario-claro)}
.pesaje-animal-seleccionado{display:flex;align-items:center;gap:12px;padding:14px 16px;background:var(--primario-ultra-suave);border:1px solid var(--primario-suave);border-radius:var(--borde-radio-lg);margin-top:8px;font-size:var(--tamano-sm)}
</style>
