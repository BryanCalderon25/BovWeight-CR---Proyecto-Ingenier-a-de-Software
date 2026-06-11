<template>
  <ion-page>
    <ion-header class="ion-no-border">
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button :default-href="esVeterinario ? `/app/veterinario/finca/${animal?.farm_id || ''}` : `/app/animales/${animal?.id || ''}`" text="" />
        </ion-buttons>
        <ion-title>Historial Veterinario</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true">
      <div v-if="animal" class="dv-contenido">

        <!-- ══ CABECERA DEL ANIMAL ══ -->
        <div class="dv-cabecera animar-aparecer">
          <div class="dv-avatar">{{ (animal.nombre || animal.arete).charAt(0).toUpperCase() }}</div>
          <div>
            <h2 class="titulo-seccion" style="margin:0">{{ animal.nombre || '(Sin nombre)' }}</h2>
            <p style="font-size:var(--tamano-sm);color:var(--texto-terciario);margin:4px 0 0 0">
              Arete {{ animal.arete }} · {{ animal.raza || 'Raza no especificada' }}
            </p>
          </div>
        </div>

        <!-- ══ SECCIÓN 1: INFORMACIÓN GENERAL ══ -->
        <section class="animar-aparecer animar-delay-1">
          <span class="etiqueta-seccion">INFORMACIÓN GENERAL</span>
          <div class="dv-info-grid">
            <div class="dv-info-item">
              <span class="campo-etiqueta">Género</span>
              <span>{{ animal.genero }}</span>
            </div>
            <div class="dv-info-item">
              <span class="campo-etiqueta">Propósito</span>
              <span>{{ animal.proposito || '—' }}</span>
            </div>
            <div class="dv-info-item">
              <span class="campo-etiqueta">Nacimiento</span>
              <span>{{ animal.fecha_nacimiento ? formatearFecha(animal.fecha_nacimiento) : '—' }}</span>
            </div>
            <div class="dv-info-item">
              <span class="campo-etiqueta">Registro</span>
              <span>{{ formatearFecha(animal.created_at) }}</span>
            </div>
          </div>
          <!-- Finca -->
          <div class="dv-finca-card">
            <span style="font-size:20px">🏡</span>
            <div>
              <strong>{{ animal.farm?.nombre || 'Finca no disponible' }}</strong>
              <span style="display:block;font-size:var(--tamano-xs);color:var(--texto-terciario)">
                {{ animal.farm?.ubicacion || 'Ubicación no especificada' }}
              </span>
            </div>
          </div>
        </section>

        <!-- ══ SECCIÓN 2: PESO Y CRECIMIENTO ══ -->
        <section class="animar-aparecer animar-delay-2">
          <span class="etiqueta-seccion">CRECIMIENTO Y PESAJES</span>

          <!-- Indicadores de peso -->
          <div class="dv-metricas">
            <div class="tarjeta tarjeta--metrica" style="flex:1">
              <span class="etiqueta-seccion" style="font-size:9px">ÚLTIMO PESO</span>
              <div style="display:flex;align-items:baseline;gap:4px;margin-top:4px">
                <span class="metrica-grande" style="font-size:var(--tamano-2xl)">
                  {{ animal.peso_actual ? Math.round(Number(animal.peso_actual)) : '—' }}
                </span>
                <span class="metrica-unidad">kg</span>
              </div>
            </div>
            <div class="tarjeta tarjeta--metrica" style="flex:1" v-if="indicadoresPeso">
              <span class="etiqueta-seccion" style="font-size:9px">VARIACIÓN</span>
              <div style="display:flex;align-items:center;gap:6px;margin-top:4px">
                <span class="metrica-grande" style="font-size:var(--tamano-2xl)"
                  :style="{ color: indicadoresPeso.diferencia >= 0 ? 'var(--exito)' : 'var(--peligro)' }">
                  {{ indicadoresPeso.diferencia >= 0 ? '+' : '' }}{{ indicadoresPeso.diferencia }} kg
                </span>
              </div>
              <span style="font-size:var(--tamano-xs);color:var(--texto-terciario)">vs. primer pesaje</span>
            </div>
          </div>

          <!-- Historial de pesajes -->
          <div v-if="historialPesajes.length" class="dv-historial-lista" style="margin-top:14px">
            <div v-for="p in historialPesajes" :key="p.id" class="dv-historial-item">
              <div>
                <strong>{{ Math.round(Number(p.peso_estimado)) }} kg</strong>
                <span style="display:block;font-size:var(--tamano-xs);color:var(--texto-terciario)">
                  {{ formatearFecha(p.fecha_pesaje) }}
                </span>
              </div>
              <span class="insignia insignia--primario" style="font-size:10px">
                {{ p.notas && p.notas.includes('YOLOv8') ? 'IA' : 'Manual' }}
              </span>
            </div>
          </div>
          <div v-else class="estado-vacio" style="padding:16px">
            <span style="font-size:28px">⚖️</span>
            <p class="estado-vacio__descripcion">Sin registros de pesaje</p>
          </div>
        </section>

        <!-- ══ SECCIÓN 3: ATENCIONES VETERINARIAS ══ -->
        <section class="animar-aparecer animar-delay-3">
          <div style="display:flex;justify-content:space-between;align-items:center">
            <span class="etiqueta-seccion">ATENCIONES VETERINARIAS</span>
            <button
              v-if="esVeterinario"
              id="btn-nueva-atencion"
              class="boton boton--primario boton--pequeno"
              @click="irAFormulario()"
            >
              ➕ Registrar
            </button>
          </div>

          <!-- Con registros -->
          <div v-if="almacenVet.registros.length" class="dv-vet-lista" style="margin-top:12px">
            <div
              v-for="r in almacenVet.registros"
              :key="r.id"
              class="dv-vet-item"
              :class="{ 'dv-vet-item--ganadero': !esVeterinario }"
              :style="!esVeterinario ? { borderLeftColor: tipoColores[r.tipo] || 'var(--borde-color)' } : {}"
            >
              <div class="dv-vet-tipo" :class="`dv-tipo--${r.tipo}`">
                {{ tiposLabel[r.tipo] || r.tipo }}
              </div>
              <div class="dv-vet-info">
                <span class="dv-vet-fecha">{{ formatearFecha(r.fecha_atencion) }}</span>
                
                <p v-if="r.diagnostico" class="dv-vet-texto">
                  <strong>Diagnóstico:</strong> {{ r.diagnostico }}
                </p>
                
                <p v-if="r.tratamiento" class="dv-vet-texto">
                  <strong>Tratamiento:</strong> {{ r.tratamiento }}
                </p>
                
                <p v-if="r.medicamentos" class="dv-vet-texto">
                  <strong>Medicamentos:</strong> {{ r.medicamentos }}
                </p>
                
                <p v-if="r.dosis" class="dv-vet-texto">
                  <strong>Dosis:</strong> {{ r.dosis }}
                </p>
                
                <p v-if="r.peso_al_momento" class="dv-vet-texto">
                  <strong>Peso al momento:</strong> {{ Math.round(Number(r.peso_al_momento)) }} kg
                </p>
                
                <p v-if="r.proxima_cita" class="dv-vet-texto" style="color:var(--primario)">
                  <strong>Próxima cita:</strong> {{ formatearFecha(r.proxima_cita) }}
                </p>
                
                <p v-if="r.observaciones" class="dv-vet-texto">
                  <strong>Observaciones:</strong> {{ r.observaciones }}
                </p>
                
                <p v-if="r.recomendaciones" class="dv-vet-texto">
                  <strong>Recomendaciones:</strong> {{ r.recomendaciones }}
                </p>
                
                <p v-if="r.veterinario" class="dv-vet-texto" style="font-size:var(--tamano-xs);color:var(--texto-terciario)">
                  <strong>Atendido por:</strong> {{ r.veterinario.name }}
                </p>
              </div>
              <div v-if="esVeterinario" style="display:flex;gap:6px;flex-shrink:0;align-self:flex-start">
                <button
                  class="boton boton--secundario boton--pequeno"
                  title="Editar atención"
                  @click.stop="irAFormulario(r.id)"
                >
                  ✏️
                </button>
                <button
                  class="boton boton--secundario boton--pequeno"
                  style="color:var(--peligro)"
                  title="Eliminar atención"
                  @click.stop="eliminarAtencion(r.id)"
                >
                  🗑️
                </button>
              </div>
            </div>
          </div>

          <div v-else class="dv-sin-vet">
            <span style="font-size:32px">📋</span>
            <p style="font-size:var(--tamano-sm);color:var(--texto-secundario);text-align:center;margin:8px 0 0 0">
              Sin atenciones veterinarias registradas.
            </p>
          </div>
        </section>

        <!-- ══ BOTÓN PRINCIPAL: GENERAR REPORTE ══ -->
        <div class="animar-aparecer animar-delay-4">
          <button
            id="btn-generar-reporte-vet"
            class="boton boton--primario boton--completo boton--grande dv-btn-reporte"
            :disabled="generandoReporte"
            @click="generarReporte"
          >
            <span v-if="generandoReporte" class="cargando-spinner" style="width:20px;height:20px;border-width:2px"></span>
            <span v-else>🩺 Generar Reporte Veterinario PDF</span>
          </button>
          <p style="font-size:10px;color:var(--texto-terciario);text-align:center;margin-top:6px">
            El reporte incluye toda la información del animal disponible en el sistema
          </p>
        </div>

        <div style="height:32px"></div>
      </div>

      <!-- Animal no encontrado -->
      <div v-else class="estado-vacio">
        <span class="cargando-spinner" v-if="cargandoAnimal"></span>
        <div v-else>
          <span class="estado-vacio__icono">🐮</span>
          <h3 class="estado-vacio__titulo">Animal no encontrado</h3>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup>
/* Vista de Detalle Veterinario — muestra toda la info del animal aunque no haya registros médicos */
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonButton, IonBackButton } from '@ionic/vue';
import { useAlmacenVeterinario } from '@/stores/veterinario.js';
import { useAlmacenAuth } from '@/stores/auth.js';
import api from '@/services/api';

const route        = useRoute();
const router       = useRouter();
const almacenVet   = useAlmacenVeterinario();
const almacenAuth  = useAlmacenAuth();
const animalId     = route.params.id;

const esVeterinario = computed(() => {
  const rol = almacenAuth.rolUsuario;
  if (rol === 'ganadero') return false;
  if (rol === 'invitado') {
    return almacenAuth.usuario?.guest_role === 'veterinario';
  }
  return rol === 'veterinario';
});

const tipoColores = {
  observacion: '#3b82f6', // Azul
  tratamiento: '#f97316', // Naranja
  vacuna:      '#22c55e', // Verde
  cirugia:     '#ec4899', // Rosa
  revision:    '#a855f7', // Púrpura
};

const animal          = ref(null);
const historialPesajes = ref([]);
const cargandoAnimal  = ref(false);
const generandoReporte = ref(false);

const tiposLabel = {
  observacion: 'Observación',
  tratamiento: 'Tratamiento',
  vacuna:      'Vacuna',
  cirugia:     'Cirugía',
  revision:    'Revisión',
};

const indicadoresPeso = computed(() => {
  if (historialPesajes.value.length < 2) return null;
  const sorted   = [...historialPesajes.value].sort((a, b) => new Date(a.fecha_pesaje) - new Date(b.fecha_pesaje));
  const inicial  = Number(sorted[0].peso_estimado);
  const ultimo   = Number(sorted[sorted.length - 1].peso_estimado);
  return { diferencia: Math.round((ultimo - inicial) * 10) / 10 };
});

function formatearFecha(fecha) {
  if (!fecha) return '—';
  return new Date(fecha).toLocaleDateString('es-CR', { day: '2-digit', month: '2-digit', year: 'numeric' });
}

async function cargarAnimal() {
  cargandoAnimal.value = true;
  try {
    const [respAnimal, respPesajes] = await Promise.all([
      api.get(`/animales/${animalId}`),
      api.get(`/animales/${animalId}/pesajes`),
    ]);
    animal.value          = respAnimal.data.datos;
    historialPesajes.value = respPesajes.data.datos || [];
  } catch (err) {
    console.error('Error al cargar animal:', err);
  } finally {
    cargandoAnimal.value = false;
  }
}

function irAFormulario(registroId = null) {
  if (registroId) {
    router.push(`/app/veterinario/registro/${registroId}/editar`);
  } else {
    router.push(`/app/veterinario/animal/${animalId}/nuevo`);
  }
}

async function eliminarAtencion(registroId) {
  if (!confirm('¿Está seguro de que desea eliminar esta atención veterinaria?')) return;
  try {
    const resultado = await almacenVet.eliminarRegistro(registroId);
    if (resultado.exito) {
      alert('Registro veterinario eliminado exitosamente.');
    } else {
      alert(resultado.error || 'Error al eliminar el registro.');
    }
  } catch (err) {
    console.error('Error al eliminar registro:', err);
    alert('Ocurrió un error inesperado al intentar eliminar el registro.');
  }
}

async function generarReporte() {
  generandoReporte.value = true;
  try {
    const resultado = await almacenVet.generarReportePDF(animalId);
    if (resultado.exito) {
      const nombreArchivo = `reporte_veterinario_${animal.value?.arete || animalId}_${new Date().toISOString().split('T')[0]}.pdf`;
      almacenVet.descargarBlob(resultado.blob, nombreArchivo);
    } else {
      alert(resultado.error);
    }
  } finally {
    generandoReporte.value = false;
  }
}

onMounted(async () => {
  await cargarAnimal();
  await almacenVet.cargarRegistros(animalId);
});
</script>

<style scoped>
.dv-contenido { padding: 0 20px; display: flex; flex-direction: column; gap: 20px; }
.dv-cabecera  { display: flex; align-items: center; gap: 14px; }
.dv-avatar {
  width: 56px; height: 56px; border-radius: 50%;
  background: var(--primario-ultra-suave);
  display: flex; align-items: center; justify-content: center;
  font-family: var(--fuente-display); font-weight: 800;
  font-size: 1.5rem; color: var(--primario); flex-shrink: 0;
}
.dv-info-grid  { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-top: 12px; }
.dv-info-item  {
  background: var(--superficie-tarjeta); border: 1px solid var(--borde-color);
  border-radius: var(--borde-radio-md); padding: 12px;
  display: flex; flex-direction: column; gap: 4px; font-size: var(--tamano-sm); font-weight: 600;
}
.dv-finca-card {
  display: flex; align-items: center; gap: 12px;
  background: var(--primario-ultra-suave); border: 1px solid var(--primario-suave);
  border-radius: var(--borde-radio-md); padding: 14px; margin-top: 12px;
}
.dv-metricas  { display: flex; gap: 12px; margin-top: 12px; }
.dv-historial-lista { display: flex; flex-direction: column; gap: 8px; }
.dv-historial-item  {
  display: flex; justify-content: space-between; align-items: center;
  padding: 12px 14px; background: var(--superficie-tarjeta);
  border: 1px solid var(--borde-color); border-radius: var(--borde-radio-md);
  font-size: var(--tamano-sm);
}
.dv-vet-lista { display: flex; flex-direction: column; gap: 10px; }
.dv-vet-item  {
  display: flex; gap: 12px; align-items: flex-start;
  padding: 14px; background: var(--superficie-tarjeta);
  border: 1px solid var(--borde-color); border-radius: var(--borde-radio-md);
}
.dv-vet-tipo {
  padding: 3px 10px; border-radius: 20px; font-size: 10px; font-weight: 700;
  text-transform: uppercase; letter-spacing: 0.5px; white-space: nowrap; flex-shrink: 0;
}
.dv-tipo--observacion { background: #E3F2FD; color: #0d47a1; }
.dv-tipo--tratamiento { background: #FFF3E0; color: #e65100; }
.dv-tipo--vacuna      { background: #E8F5E9; color: #1b5e20; }
.dv-tipo--cirugia     { background: #FCE4EC; color: #880E4F; }
.dv-tipo--revision    { background: #F3E5F5; color: #4A148C; }
.dv-vet-info  { flex: 1; display: flex; flex-direction: column; gap: 4px; }
.dv-vet-fecha { font-size: var(--tamano-xs); color: var(--texto-terciario); font-weight: 600; }
.dv-vet-texto { font-size: var(--tamano-sm); color: var(--texto-secundario); margin: 0; }
.dv-sin-vet   {
  display: flex; flex-direction: column; align-items: center; gap: 8px;
  padding: 24px; background: var(--superficie-tarjeta);
  border: 1.5px dashed var(--borde-color); border-radius: var(--borde-radio-md);
  margin-top: 12px;
}
.dv-btn-reporte {
  background: linear-gradient(135deg, var(--primario), var(--primario-medio));
  box-shadow: 0 4px 14px rgba(101,109,74,0.35);
}
.dv-vet-item--ganadero {
  border-left: 4px solid;
  border-radius: var(--borde-radio-md);
  padding: 16px;
  background: var(--superficie-tarjeta);
  box-shadow: 0 2px 10px rgba(0, 0, 0, 0.02);
  transition: all var(--transicion-normal);
}
.dv-vet-item--ganadero:hover {
  transform: translateX(3px);
  box-shadow: 0 4px 14px rgba(0, 0, 0, 0.05);
}
</style>
