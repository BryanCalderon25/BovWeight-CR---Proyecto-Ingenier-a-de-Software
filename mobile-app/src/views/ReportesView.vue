<template>
  <ion-page>
    <ion-header class="ion-no-border">
      <ion-toolbar>
        <ion-buttons slot="start"><ion-back-button default-href="/app/inicio" text="" /></ion-buttons>
        <ion-title>Reportes</ion-title>
      </ion-toolbar>
    </ion-header>

    <!-- Toast de notificación -->
    <transition name="toast-slide">
      <div v-if="toastActivo" class="toast-contenedor" :class="`toast--${toastTipo}`" role="alert" aria-live="assertive">
        <span class="toast-icono">{{ toastIcono }}</span>
        <span class="toast-mensaje">{{ toastMensaje }}</span>
      </div>
    </transition>

    <ion-content :fullscreen="true">
      <div class="reportes-contenido">

        <!-- Cabecera -->
        <section class="animar-aparecer">
          <span class="etiqueta-seccion">DOCUMENTACIÓN</span>
          <h2 class="titulo-seccion">Reportes PDF</h2>
          <p style="font-size:var(--tamano-sm);color:var(--texto-secundario);margin-top:4px">
            Genere reportes profesionales de su hato y fincas.
          </p>
        </section>

        <!-- Tipo de reporte -->
        <section class="animar-aparecer animar-delay-1">
          <span class="etiqueta-seccion">TIPO DE REPORTE</span>
          <div class="reportes-tipos">
            <div v-for="tipo in tiposReporte" :key="tipo.id"
              class="reporte-tipo" :class="{ 'reporte-tipo--activo': tipoSeleccionado === tipo.id }"
              @click="tipoSeleccionado = tipo.id; reporteGenerado = false">
              <span style="font-size:24px">{{ tipo.icono }}</span>
              <strong>{{ tipo.nombre }}</strong>
              <span style="font-size:var(--tamano-xs);color:var(--texto-terciario)">{{ tipo.descripcion }}</span>
            </div>
          </div>
        </section>

        <!-- Selector de finca o animal -->
        <section class="animar-aparecer animar-delay-2">
          <div v-if="tipoSeleccionado !== 'veterinario'" class="campo-grupo">
            <label class="campo-etiqueta">Finca</label>
            <select class="campo-entrada" v-model="fincaSeleccionada">
              <option value="">Todas las fincas</option>
              <option v-for="f in fincas" :key="f.id" :value="f.id">{{ f.nombre }}</option>
            </select>
          </div>
          <div v-else class="campo-grupo">
            <label class="campo-etiqueta">Animal *</label>
            <select id="selector-animal-veterinario" class="campo-entrada" v-model="animalSeleccionado">
              <option value="">Seleccione un animal...</option>
              <option v-for="a in animalesList" :key="a.id" :value="a.id">
                {{ a.arete }}{{ a.nombre ? ' - ' + a.nombre : '' }}
              </option>
            </select>
            <p v-if="tipoSeleccionado === 'veterinario' && !animalSeleccionado"
               style="font-size:var(--tamano-xs);color:var(--texto-terciario);margin-top:4px">
              El reporte incluirá información general, pesajes e historial veterinario del animal.
            </p>
          </div>
        </section>

        <!-- Botón Generar -->
        <button id="btn-generar-reporte"
          class="boton boton--primario boton--completo boton--grande animar-aparecer animar-delay-3"
          :disabled="generando" @click="generarReporte">
          <span v-if="generando" class="cargando-spinner" style="width:20px;height:20px;border-width:2px"></span>
          <span v-else>📄 Generar Reporte PDF</span>
        </button>

        <!-- Tarjeta: Reporte Generado -->
        <transition name="fade-up">
          <div v-if="reporteGenerado" class="tarjeta reporte-resultado animar-aparecer">

            <!-- Encabezado del resultado -->
            <div class="resultado-header">
              <div class="resultado-icono-grande">✅</div>
              <div>
                <h3 class="resultado-titulo">Reporte Generado</h3>
                <p class="resultado-subtitulo">Listo para descargar o compartir</p>
              </div>
            </div>

            <!-- Ficha de información del reporte -->
            <div class="reporte-ficha">
              <div class="ficha-item">
                <span class="ficha-etiqueta">Tipo</span>
                <span class="ficha-valor">{{ tiposReporte.find(t => t.id === tipoSeleccionado)?.icono }}
                  {{ tiposReporte.find(t => t.id === tipoSeleccionado)?.nombre }}</span>
              </div>
              <div class="ficha-item">
                <span class="ficha-etiqueta">Finca</span>
                <span class="ficha-valor">{{ nombreFincaActual }}</span>
              </div>
              <div class="ficha-item">
                <span class="ficha-etiqueta">Generado</span>
                <span class="ficha-valor">{{ fechaGeneracion }}</span>
              </div>
              <div class="ficha-item">
                <span class="ficha-etiqueta">Archivo</span>
                <span class="ficha-valor ficha-archivo">{{ nombreArchivoReporte }}</span>
              </div>
            </div>

            <!-- Acciones principales -->
            <div class="resultado-acciones">
              <button id="btn-descargar-reporte"
                class="boton-accion boton-accion--primario"
                @click="descargar">
                <span class="accion-icono">📥</span>
                <span class="accion-texto">
                  <strong>Descargar PDF</strong>
                  <small>Guardar en este dispositivo</small>
                </span>
              </button>

              <!-- Botón compartir universal -->
              <button id="btn-compartir-reporte"
                class="boton-accion boton-accion--secundario"
                @click="compartirReporte(blobReporte, nombreArchivoReporte)">
                <span class="accion-icono">📤</span>
                <span class="accion-texto">
                  <strong>Compartir</strong>
                  <small>WhatsApp, correo u otra app</small>
                </span>
              </button>
            </div>

            <!-- Nota de soporte (si el navegador no soporta compartir archivos) -->
            <p v-if="!soportaCompartirArchivos" class="nota-soporte">
              💡 En este navegador, descargue el PDF y adjúntelo manualmente al enviarlo.
            </p>
          </div>
        </transition>

        <!-- Historial de reportes -->
        <section class="animar-aparecer animar-delay-4">
          <span class="etiqueta-seccion">REPORTES ANTERIORES</span>
          <div class="reportes-historial">
            <div v-if="reportesAnteriores.length === 0" class="historial-vacio">
              <span>📋</span>
              <p>Los reportes que genere aparecerán aquí durante esta sesión.</p>
            </div>
            <div v-for="r in reportesAnteriores" :key="r.id" class="reporte-historial-item">
              <div class="historial-info">
                <strong>{{ r.titulo }}</strong>
                <span class="historial-fecha">{{ r.fecha }}</span>
                <span v-if="!r.blob" class="historial-sin-archivo">Sin archivo en memoria</span>
              </div>
              <div class="historial-acciones">
                <!-- Descargar (solo si tiene blob) -->
                <button
                  :id="`btn-descargar-historial-${r.id}`"
                  class="boton-historial boton-historial--descargar"
                  :disabled="!r.blob"
                  :title="r.blob ? 'Descargar PDF' : 'PDF no disponible — regenere el reporte'"
                  @click="descargarReporteAnterior(r)">
                  📥
                </button>
                <!-- Compartir (solo si tiene blob) -->
                <button
                  :id="`btn-compartir-historial-${r.id}`"
                  class="boton-historial boton-historial--compartir"
                  :disabled="!r.blob"
                  :title="r.blob ? 'Compartir' : 'PDF no disponible — regenere el reporte'"
                  @click="compartirReporte(r.blob, r.nombreArchivo)">
                  📤
                </button>
              </div>
            </div>
          </div>
          <!-- Nota sobre persistencia del historial -->
          <p class="nota-historial">
            ℹ️ Los reportes se conservan mientras la sesión esté activa. Para volver a compartir un reporte antiguo, genérelo de nuevo.
          </p>
        </section>

        <div style="height:32px"></div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup>
/* Vista de Reportes PDF — BWCR: Compartir y Mejorar Reportes */
import { ref, computed, onMounted } from 'vue';
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton } from '@ionic/vue';
import { useAlmacenFincas } from '@/stores/fincas.js';
import { useAlmacenAnimales } from '@/stores/animales.js';
import { useAlmacenVeterinario } from '@/stores/veterinario.js';
import api from '@/services/api';

// ── Stores ────────────────────────────────────────────────────────────────────
const almacenFincas   = useAlmacenFincas();
const almacenAnimales = useAlmacenAnimales();
const almacenVet      = useAlmacenVeterinario();
const fincas          = almacenFincas.lista;
const animalesList    = computed(() => almacenAnimales.lista);

// ── Estado de selección ───────────────────────────────────────────────────────
const tipoSeleccionado   = ref('general');
const fincaSeleccionada  = ref('');
const animalSeleccionado = ref('');

// ── Estado de generación ──────────────────────────────────────────────────────
const generando             = ref(false);
const reporteGenerado       = ref(false);
const blobReporte           = ref(null);
const nombreArchivoReporte  = ref('');
const fechaGeneracion       = ref('');

// ── Toast ─────────────────────────────────────────────────────────────────────
const toastActivo  = ref(false);
const toastMensaje = ref('');
const toastTipo    = ref('info');   // 'exito' | 'error' | 'info'
let   toastTimer   = null;

const toastIcono = computed(() => {
  if (toastTipo.value === 'exito') return '✅';
  if (toastTipo.value === 'error') return '❌';
  return 'ℹ️';
});

function mostrarToast(mensaje, tipo = 'info', duracion = 3500) {
  if (toastTimer) clearTimeout(toastTimer);
  toastMensaje.value = mensaje;
  toastTipo.value    = tipo;
  toastActivo.value  = true;
  toastTimer = setTimeout(() => { toastActivo.value = false; }, duracion);
}

// ── Tipos de reporte ──────────────────────────────────────────────────────────
const tiposReporte = [
  { id: 'general',     icono: '📊', nombre: 'General',     descripcion: 'Resumen completo del hato' },
  { id: 'pesajes',     icono: '⚖️', nombre: 'Pesajes',     descripcion: 'Historial de pesajes' },
  { id: 'finca',       icono: '🏡', nombre: 'Por Finca',   descripcion: 'Detalle por finca' },
  { id: 'veterinario', icono: '🩺', nombre: 'Veterinario', descripcion: 'Historial médico por animal' },
];

// ── Historial de sesión ───────────────────────────────────────────────────────
// Inicia vacío — los reportes generados se agregan durante la sesión
const reportesAnteriores = ref([]);

// ── Info de la finca para la ficha ───────────────────────────────────────────
const nombreFincaActual = computed(() => {
  if (!fincaSeleccionada.value) return 'Todas las fincas';
  const f = fincas.find?.(f => f.id === fincaSeleccionada.value || f.id === Number(fincaSeleccionada.value));
  return f?.nombre ?? 'Finca seleccionada';
});

// ── Detección de soporte para compartir archivos ──────────────────────────────
const soportaCompartirArchivos = computed(() => {
  if (!navigator.share || !navigator.canShare) return false;
  try {
    const archivoPrueba = new File([''], 'prueba.pdf', { type: 'application/pdf' });
    return navigator.canShare({ files: [archivoPrueba] });
  } catch {
    return false;
  }
});

// ── Nombre de archivo amigable ────────────────────────────────────────────────
function generarNombreArchivo(tipo, extra = '') {
  const ahora    = new Date();
  const dia      = String(ahora.getDate()).padStart(2, '0');
  const meses    = ['ene','feb','mar','abr','may','jun','jul','ago','sep','oct','nov','dic'];
  const mes      = meses[ahora.getMonth()];
  const anio     = ahora.getFullYear();
  const sufijo   = extra ? `_${extra}` : '';
  const nombreTipo = {
    general:     'General',
    pesajes:     'Pesajes',
    finca:       'PorFinca',
    veterinario: 'Veterinario',
  }[tipo] ?? tipo;
  return `BovWeight_${nombreTipo}${sufijo}_${dia}${mes}${anio}.pdf`;
}

// ── Generar reporte ───────────────────────────────────────────────────────────
async function generarReporte() {
  generando.value      = true;
  reporteGenerado.value = false;
  blobReporte.value    = null;

  try {
    let blob, nombreArchivo;

    if (tipoSeleccionado.value === 'veterinario') {
      if (!animalSeleccionado.value) {
        mostrarToast('Seleccione un animal para generar el Reporte Veterinario.', 'error');
        return;
      }
      const resultado = await almacenVet.generarReportePDF(animalSeleccionado.value);
      if (!resultado.exito) {
        mostrarToast(resultado.error || 'No se pudo generar el reporte.', 'error');
        return;
      }
      blob = resultado.blob;
      const animal = animalesList.value.find(a => a.id === Number(animalSeleccionado.value));
      const arete  = animal?.arete ? `_${animal.arete}` : '';
      nombreArchivo = generarNombreArchivo('veterinario', animal?.arete ?? '');
    } else {
      const params = {
        tipo:     tipoSeleccionado.value,
        finca_id: fincaSeleccionada.value || undefined,
      };
      const respuesta = await api.get('/reportes/generar', { params, responseType: 'blob' });
      blob = respuesta.data;
      nombreArchivo = generarNombreArchivo(tipoSeleccionado.value);
    }

    blobReporte.value          = blob;
    nombreArchivoReporte.value = nombreArchivo;
    fechaGeneracion.value      = new Date().toLocaleString('es-CR');
    reporteGenerado.value      = true;

    // Agregar al historial de sesión
    reportesAnteriores.value.unshift({
      id:           Date.now(),
      titulo:       `Reporte ${tiposReporte.find(t => t.id === tipoSeleccionado.value)?.nombre ?? tipoSeleccionado.value.toUpperCase()} — ${new Date().toLocaleDateString('es-CR')}`,
      fecha:        new Date().toLocaleDateString('es-CR'),
      blob,
      nombreArchivo,
    });

    mostrarToast('Reporte generado exitosamente.', 'exito');
  } catch (err) {
    console.error('Error al generar reporte:', err);
    mostrarToast('No se pudo generar el reporte PDF. Verifique que tenga animales y pesajes registrados.', 'error', 5000);
  } finally {
    generando.value = false;
  }
}

// ── Descargar ─────────────────────────────────────────────────────────────────
function descargarBlob(blob, nombre) {
  const url  = window.URL.createObjectURL(new Blob([blob], { type: 'application/pdf' }));
  const link = document.createElement('a');
  link.href  = url;
  link.setAttribute('download', nombre);
  document.body.appendChild(link);
  link.click();
  link.parentNode.removeChild(link);
  window.URL.revokeObjectURL(url);
}

function descargar() {
  if (!blobReporte.value) return;
  descargarBlob(blobReporte.value, nombreArchivoReporte.value);
  mostrarToast('Descarga iniciada.', 'exito');
}

function descargarReporteAnterior(r) {
  if (!r.blob) {
    mostrarToast('El PDF de este reporte ya no está en memoria. Genérelo nuevamente para descargarlo.', 'info', 5000);
    return;
  }
  descargarBlob(r.blob, r.nombreArchivo || 'reporte.pdf');
  mostrarToast('Descarga iniciada.', 'exito');
}

// ── Compartir universal (con fallback) ───────────────────────────────────────
async function compartirReporte(blob, nombre) {
  // Guardia: verificar que tengamos el blob real
  if (!blob) {
    mostrarToast('El PDF de este reporte ya no está en memoria. Genérelo nuevamente para compartirlo.', 'info', 5000);
    return;
  }

  const archivo = new File([blob], nombre || 'reporte_BovWeight.pdf', { type: 'application/pdf' });

  // ── Camino 1: Web Share API con archivos adjuntos (móvil nativo) ──────────
  if (navigator.share && navigator.canShare && navigator.canShare({ files: [archivo] })) {
    try {
      await navigator.share({
        files: [archivo],
        title: 'Reporte BovWeight CR',
        text:  'Adjunto el reporte de mi hato bovino generado en BovWeight CR.',
      });
      // No mostramos toast si el usuario canceló (AbortError)
    } catch (e) {
      if (e.name !== 'AbortError') {
        console.error('Error al compartir nativamente:', e);
        // Fallback: ofrecer descarga
        mostrarToast('No se pudo compartir. Se descargó el PDF para que lo envíe manualmente.', 'info', 5000);
        descargarBlob(blob, nombre);
      }
    }
    return;
  }

  // ── Camino 2: Web Share API sin archivos (solo texto/URL) ─────────────────
  if (navigator.share) {
    try {
      await navigator.share({
        title: 'Reporte BovWeight CR',
        text:  `Reporte "${nombre}" generado en BovWeight CR. Descárguelo desde la plataforma.`,
      });
      // Después de compartir el texto, descargar el PDF para que lo adjunte manualmente
      descargarBlob(blob, nombre);
      mostrarToast('El PDF se descargó. Adjúntelo manualmente al mensaje enviado.', 'info', 5000);
    } catch (e) {
      if (e.name !== 'AbortError') {
        descargarBlob(blob, nombre);
        mostrarToast('No se pudo compartir. El PDF fue descargado para envío manual.', 'info', 5000);
      }
    }
    return;
  }

  // ── Camino 3: Fallback completo (escritorio / navegador sin Web Share API) ──
  // Descargamos el PDF y abrimos WhatsApp Web con un mensaje indicando que adjunte el archivo
  descargarBlob(blob, nombre);
  const texto = encodeURIComponent(
    `Hola, te comparto el reporte *${nombre}* de BovWeight CR.\n` +
    `(Por favor adjunta el archivo PDF que se descargó en tu dispositivo.)`
  );
  window.open(`https://wa.me/?text=${texto}`, '_blank');
  mostrarToast('El PDF fue descargado. Adjúntelo al mensaje de WhatsApp que se abrió.', 'info', 6000);
}
</script>

<style scoped>
/* ── Layout ── */
.reportes-contenido {
  padding: 0 20px;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

/* ── Tipos de reporte ── */
.reportes-tipos {
  display: grid;
  grid-template-columns: repeat(2, 1fr);
  gap: 10px;
  margin-top: 12px;
}
.reporte-tipo {
  display: flex;
  flex-direction: column;
  align-items: center;
  text-align: center;
  gap: 6px;
  padding: 16px 8px;
  background: var(--superficie-tarjeta);
  border: 1.5px solid var(--borde-color);
  border-radius: var(--borde-radio-md);
  cursor: pointer;
  transition: all var(--transicion-normal);
  font-size: var(--tamano-sm);
}
.reporte-tipo:hover { border-color: var(--primario-suave); }
.reporte-tipo--activo { border-color: var(--primario); background: var(--primario-ultra-suave); }

/* ── Toast de notificación ── */
.toast-contenedor {
  position: fixed;
  top: 60px;
  left: 50%;
  transform: translateX(-50%);
  z-index: 9999;
  display: flex;
  align-items: center;
  gap: 10px;
  padding: 12px 20px;
  border-radius: var(--borde-radio-lg);
  box-shadow: var(--sombra-lg);
  font-size: var(--tamano-sm);
  font-family: var(--fuente-cuerpo);
  max-width: 340px;
  width: calc(100vw - 40px);
  pointer-events: none;
}
.toast--exito { background: var(--exito-suave); color: var(--exito-texto); border: 1px solid var(--exito); }
.toast--error { background: var(--peligro-suave); color: var(--peligro-texto); border: 1px solid var(--peligro); }
.toast--info  { background: var(--info-suave); color: var(--info-texto); border: 1px solid var(--info); }
.toast-icono  { font-size: 18px; flex-shrink: 0; }
.toast-mensaje { line-height: 1.4; font-weight: 500; }

/* Transición del toast */
.toast-slide-enter-active { transition: all 300ms cubic-bezier(0.16,1,0.3,1); }
.toast-slide-leave-active  { transition: all 250ms cubic-bezier(0.4,0,1,1); }
.toast-slide-enter-from, .toast-slide-leave-to { opacity: 0; transform: translateX(-50%) translateY(-12px); }

/* ── Tarjeta de resultado ── */
.reporte-resultado {
  padding: 20px;
  border: 1.5px solid var(--primario-suave);
  background: var(--superficie-tarjeta);
}

.resultado-header {
  display: flex;
  align-items: center;
  gap: 14px;
  margin-bottom: 16px;
}
.resultado-icono-grande { font-size: 32px; }
.resultado-titulo {
  font-family: var(--fuente-display);
  font-size: var(--tamano-lg);
  font-weight: 700;
  color: var(--texto-primario);
  margin: 0 0 2px 0;
}
.resultado-subtitulo {
  font-size: var(--tamano-sm);
  color: var(--texto-terciario);
  margin: 0;
}

/* Ficha de información */
.reporte-ficha {
  background: var(--superficie-hundida);
  border-radius: var(--borde-radio-sm);
  padding: 12px 14px;
  margin-bottom: 16px;
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 8px 16px;
}
.ficha-item {
  display: flex;
  flex-direction: column;
  gap: 2px;
}
.ficha-etiqueta {
  font-size: var(--tamano-xs);
  color: var(--texto-terciario);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  font-weight: 600;
}
.ficha-valor {
  font-size: var(--tamano-sm);
  color: var(--texto-primario);
  font-weight: 500;
}
.ficha-archivo {
  font-size: 11px;
  word-break: break-all;
  color: var(--primario-medio);
}

/* Acciones del resultado */
.resultado-acciones {
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.boton-accion {
  display: flex;
  align-items: center;
  gap: 14px;
  padding: 14px 16px;
  border-radius: var(--borde-radio-md);
  border: none;
  cursor: pointer;
  text-align: left;
  transition: all var(--transicion-normal);
  width: 100%;
}
.boton-accion--primario {
  background: var(--primario);
  color: var(--texto-inverso);
}
.boton-accion--primario:hover { background: var(--primario-medio); }
.boton-accion--secundario {
  background: var(--superficie-hundida);
  color: var(--texto-primario);
  border: 1.5px solid var(--borde-color);
}
.boton-accion--secundario:hover { border-color: var(--primario-suave); background: var(--primario-ultra-suave); }

.accion-icono { font-size: 22px; flex-shrink: 0; }
.accion-texto {
  display: flex;
  flex-direction: column;
  gap: 1px;
}
.accion-texto strong { font-size: var(--tamano-sm); font-weight: 600; }
.accion-texto small  { font-size: var(--tamano-xs); opacity: 0.75; }

/* Nota de soporte */
.nota-soporte {
  margin-top: 12px;
  font-size: var(--tamano-xs);
  color: var(--texto-terciario);
  line-height: 1.5;
  background: var(--advertencia-suave);
  border-radius: var(--borde-radio-sm);
  padding: 8px 12px;
  border-left: 3px solid var(--advertencia);
}

/* Transición de la tarjeta */
.fade-up-enter-active { transition: all 350ms cubic-bezier(0.16,1,0.3,1); }
.fade-up-leave-active  { transition: all 200ms ease; }
.fade-up-enter-from    { opacity: 0; transform: translateY(12px); }
.fade-up-leave-to      { opacity: 0; }

/* ── Historial de reportes ── */
.reportes-historial {
  display: flex;
  flex-direction: column;
  gap: 8px;
  margin-top: 12px;
}
.historial-vacio {
  text-align: center;
  padding: 20px;
  color: var(--texto-terciario);
  font-size: var(--tamano-sm);
}
.historial-vacio span { font-size: 28px; display: block; margin-bottom: 8px; }

.reporte-historial-item {
  display: flex;
  align-items: center;
  justify-content: space-between;
  gap: 12px;
  padding: 12px 14px;
  background: var(--superficie-tarjeta);
  border: 1px solid var(--borde-color);
  border-radius: var(--borde-radio-md);
  font-size: var(--tamano-sm);
}
.historial-info {
  display: flex;
  flex-direction: column;
  gap: 2px;
  min-width: 0;
}
.historial-fecha {
  font-size: var(--tamano-xs);
  color: var(--texto-terciario);
}
.historial-sin-archivo {
  font-size: var(--tamano-xs);
  color: var(--advertencia-texto);
  font-style: italic;
}
.historial-acciones {
  display: flex;
  gap: 6px;
  flex-shrink: 0;
}
.boton-historial {
  width: 36px;
  height: 36px;
  border: 1.5px solid var(--borde-color);
  border-radius: var(--borde-radio-sm);
  background: var(--superficie-hundida);
  cursor: pointer;
  font-size: 16px;
  display: flex;
  align-items: center;
  justify-content: center;
  transition: all var(--transicion-normal);
}
.boton-historial:not(:disabled):hover {
  background: var(--primario-ultra-suave);
  border-color: var(--primario-suave);
}
.boton-historial:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}
.boton-historial--compartir:not(:disabled):hover {
  background: var(--info-suave);
  border-color: var(--info);
}

/* Nota de historial */
.nota-historial {
  margin-top: 10px;
  font-size: var(--tamano-xs);
  color: var(--texto-terciario);
  line-height: 1.5;
  padding: 0 4px;
}
</style>
