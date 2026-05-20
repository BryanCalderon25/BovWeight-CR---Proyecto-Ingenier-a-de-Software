<template>
  <ion-page>
    <ion-header class="ion-no-border">
      <ion-toolbar>
        <ion-buttons slot="start"><ion-back-button default-href="/app/inicio" text="" /></ion-buttons>
        <ion-title>Reportes</ion-title>
      </ion-toolbar>
    </ion-header>
    <ion-content :fullscreen="true">
      <div class="reportes-contenido">
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
              @click="tipoSeleccionado = tipo.id">
              <span style="font-size:24px">{{ tipo.icono }}</span>
              <strong>{{ tipo.nombre }}</strong>
              <span style="font-size:var(--tamano-xs);color:var(--texto-terciario)">{{ tipo.descripcion }}</span>
            </div>
          </div>
        </section>

        <!-- Seleccionar finca -->
        <section class="animar-aparecer animar-delay-2">
          <div class="campo-grupo">
            <label class="campo-etiqueta">Finca</label>
            <select class="campo-entrada" v-model="fincaSeleccionada">
              <option value="">Todas las fincas</option>
              <option v-for="f in fincas" :key="f.id" :value="f.id">{{ f.nombre }}</option>
            </select>
          </div>
        </section>

        <!-- Generar -->
        <button class="boton boton--primario boton--completo boton--grande animar-aparecer animar-delay-3"
          :disabled="generando" @click="generarReporte">
          <span v-if="generando" class="cargando-spinner" style="width:20px;height:20px;border-width:2px"></span>
          <span v-else>📄 Generar Reporte PDF</span>
        </button>

        <!-- Reporte generado -->
        <div v-if="reporteGenerado" class="tarjeta animar-aparecer" style="margin-top:8px">
          <h3 style="font-family:var(--fuente-display);font-weight:600;margin-bottom:8px">✅ Reporte Generado</h3>
          <p style="font-size:var(--tamano-sm);color:var(--texto-secundario);margin-bottom:16px">
            El reporte se generó exitosamente. Compártalo o descárguelo.
          </p>
          <div style="display:flex;flex-direction:column;gap:8px">
            <button class="boton boton--primario boton--completo" @click="descargar">📥 Descargar PDF</button>
            <button class="boton boton--secundario boton--completo" @click="compartirWhatsApp">💬 Compartir por WhatsApp</button>
            <button class="boton boton--secundario boton--completo" @click="compartirCorreo">📧 Enviar por Correo</button>
          </div>
        </div>

        <!-- Historial de reportes -->
        <section class="animar-aparecer animar-delay-4">
          <span class="etiqueta-seccion">REPORTES ANTERIORES</span>
          <div class="reportes-historial">
            <div v-for="r in reportesAnteriores" :key="r.id" class="reporte-historial-item">
              <div>
                <strong>{{ r.titulo }}</strong>
                <span style="display:block;font-size:var(--tamano-xs);color:var(--texto-terciario)">{{ r.fecha }}</span>
              </div>
              <button class="boton boton--secundario boton--pequeno" @click="descargarReporteAnterior(r)">📥</button>
            </div>
          </div>
        </section>
        <div style="height:32px"></div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup>
/* Vista de Reportes PDF */
import { ref } from 'vue';
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton } from '@ionic/vue';
import { useAlmacenFincas } from '@/stores/fincas.js';
import api from '@/services/api';

const almacenFincas = useAlmacenFincas();
const fincas = almacenFincas.lista;

const tipoSeleccionado = ref('general');
const fincaSeleccionada = ref('');
const generando = ref(false);
const reporteGenerado = ref(false);
const blobReporte = ref(null);
const nombreArchivoReporte = ref('');

const tiposReporte = [
  { id: 'general', icono: '📊', nombre: 'General', descripcion: 'Resumen completo del hato' },
  { id: 'pesajes', icono: '⚖️', nombre: 'Pesajes', descripcion: 'Historial de pesajes' },
  { id: 'finca', icono: '🏡', nombre: 'Por Finca', descripcion: 'Detalle por finca' }
];

const reportesAnteriores = ref([
  { id: 1, titulo: 'Reporte General - Mayo 2026', fecha: '20/05/2026' },
  { id: 2, titulo: 'Pesajes La Esperanza', fecha: '18/05/2026' },
  { id: 3, titulo: 'Reporte Hato Completo', fecha: '14/05/2026' }
]);

async function generarReporte() {
  generando.value = true;
  reporteGenerado.value = false;
  blobReporte.value = null;
  
  try {
    const params = {
      tipo: tipoSeleccionado.value,
      finca_id: fincaSeleccionada.value || undefined
    };
    
    const respuesta = await api.get('/reportes/generar', {
      params,
      responseType: 'blob'
    });
    
    blobReporte.value = respuesta.data;
    nombreArchivoReporte.value = `reporte_${tipoSeleccionado.value}_${new Date().toISOString().split('T')[0]}.pdf`;
    reporteGenerado.value = true;
    
    // Agregar al historial de la sesión
    reportesAnteriores.value.unshift({
      id: Date.now(),
      titulo: `Reporte ${tipoSeleccionado.value.toUpperCase()} - ${new Date().toLocaleDateString('es-CR')}`,
      fecha: new Date().toLocaleDateString('es-CR'),
      blob: respuesta.data,
      nombreArchivo: nombreArchivoReporte.value
    });
  } catch (err) {
    console.error('Error al generar reporte:', err);
    alert('No se pudo generar el reporte PDF. Asegúrese de tener animales y pesajes registrados.');
  } finally {
    generando.value = false;
  }
}

function descargar() {
  if (!blobReporte.value) return;
  const url = window.URL.createObjectURL(new Blob([blobReporte.value], { type: 'application/pdf' }));
  const link = document.createElement('a');
  link.href = url;
  link.setAttribute('download', nombreArchivoReporte.value);
  document.body.appendChild(link);
  link.click();
  link.parentNode.removeChild(link);
  window.URL.revokeObjectURL(url);
}

function descargarReporteAnterior(r) {
  if (r.blob) {
    const url = window.URL.createObjectURL(new Blob([r.blob], { type: 'application/pdf' }));
    const link = document.createElement('a');
    link.href = url;
    link.setAttribute('download', r.nombreArchivo || 'reporte.pdf');
    document.body.appendChild(link);
    link.click();
    link.parentNode.removeChild(link);
    window.URL.revokeObjectURL(url);
  } else {
    alert(`Descargando copia de respaldo del reporte: ${r.titulo}`);
  }
}

async function compartirWhatsApp() {
  if (!blobReporte.value) {
    alert('Genere primero el reporte para poder compartirlo.');
    return;
  }
  
  const archivo = new File([blobReporte.value], nombreArchivoReporte.value, { type: 'application/pdf' });
  
  if (navigator.share && navigator.canShare && navigator.canShare({ files: [archivo] })) {
    try {
      await navigator.share({
        files: [archivo],
        title: 'Reporte BovWeight CR',
        text: 'Hola, adjunto el reporte de pesajes de BovWeight CR.'
      });
    } catch (e) {
      if (e.name !== 'AbortError') {
        console.error('Error al compartir nativamente:', e);
      }
    }
  } else {
    // Fallback tradicional Web
    const texto = encodeURIComponent(`Hola, acabo de generar el reporte *${nombreArchivoReporte.value}* en la plataforma BovWeight CR. Recuerda descargar el PDF adjunto.`);
    window.open(`https://wa.me/?text=${texto}`, '_blank');
  }
}

async function compartirCorreo() {
  if (!blobReporte.value) {
    alert('Genere primero el reporte para poder compartirlo.');
    return;
  }
  
  const archivo = new File([blobReporte.value], nombreArchivoReporte.value, { type: 'application/pdf' });
  
  if (navigator.share && navigator.canShare && navigator.canShare({ files: [archivo] })) {
    try {
      await navigator.share({
        files: [archivo],
        title: 'Reporte BovWeight CR',
        text: 'Hola, adjunto mi reporte de pesajes e inventario de BovWeight CR.'
      });
    } catch (e) {
      if (e.name !== 'AbortError') {
        console.error('Error al compartir nativamente:', e);
      }
    }
  } else {
    // Fallback tradicional Web
    const subject = encodeURIComponent(`Reporte BovWeight CR - ${tipoSeleccionado.value.toUpperCase()}`);
    const body = encodeURIComponent(`Hola, adjunto el reporte de mi hato bovino.\n\nNombre del archivo: ${nombreArchivoReporte.value}\n\nGenerado por BovWeight CR.`);
    window.open(`mailto:?subject=${subject}&body=${body}`, '_blank');
  }
}
</script>

<style scoped>
.reportes-contenido{padding:0 20px;display:flex;flex-direction:column;gap:20px}
.reportes-tipos{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin-top:12px}
.reporte-tipo{display:flex;flex-direction:column;align-items:center;text-align:center;gap:6px;padding:16px 8px;background:var(--superficie-tarjeta);border:1.5px solid var(--borde-color);border-radius:var(--borde-radio-md);cursor:pointer;transition:all var(--transicion-normal);font-size:var(--tamano-sm)}
.reporte-tipo:hover{border-color:var(--primario-suave)}
.reporte-tipo--activo{border-color:var(--primario);background:var(--primario-ultra-suave)}
.reportes-historial{display:flex;flex-direction:column;gap:8px;margin-top:12px}
.reporte-historial-item{display:flex;align-items:center;justify-content:space-between;padding:14px 16px;background:var(--superficie-tarjeta);border:1px solid var(--borde-color);border-radius:var(--borde-radio-md);font-size:var(--tamano-sm)}
</style>
