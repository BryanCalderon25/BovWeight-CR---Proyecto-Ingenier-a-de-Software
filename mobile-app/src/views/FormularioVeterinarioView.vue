<template>
  <ion-page>
    <ion-header class="ion-no-border">
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button :default-href="backHref" text="" />
        </ion-buttons>
        <ion-title>{{ modoEdicion ? 'Editar Atención' : 'Nueva Atención' }}</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true">
      <div class="fv-contenido">
        <section class="animar-aparecer">
          <span class="etiqueta-seccion">REGISTRO VETERINARIO</span>
          <h2 class="titulo-seccion">{{ modoEdicion ? 'Editar Atención Veterinaria' : 'Registrar Nueva Atención' }}</h2>
        </section>

        <form id="formulario-atencion-vet" class="fv-form animar-aparecer animar-delay-1" @submit.prevent="guardar">

          <!-- Tipo de atención -->
          <div class="campo-grupo">
            <label class="campo-etiqueta" for="tipo-atencion">Tipo de Atención *</label>
            <select id="tipo-atencion" class="campo-entrada" v-model="form.tipo" required>
              <option value="">Seleccione un tipo...</option>
              <option value="observacion">Observación</option>
              <option value="tratamiento">Tratamiento</option>
              <option value="vacuna">Vacuna</option>
              <option value="cirugia">Cirugía</option>
              <option value="revision">Revisión General</option>
            </select>
          </div>

          <!-- Fecha de atención -->
          <div class="campo-grupo">
            <label class="campo-etiqueta" for="fecha-atencion">Fecha de Atención *</label>
            <input
              id="fecha-atencion"
              class="campo-entrada"
              type="date"
              v-model="form.fecha_atencion"
              :max="hoy"
              required
            />
          </div>

          <!-- Diagnóstico -->
          <div class="campo-grupo">
            <label class="campo-etiqueta" for="diagnostico">Diagnóstico</label>
            <textarea
              id="diagnostico"
              class="campo-entrada fv-textarea"
              v-model="form.diagnostico"
              placeholder="Descripción del diagnóstico o hallazgos clínicos..."
              rows="3"
            ></textarea>
          </div>

          <!-- Tratamiento (solo si aplica) -->
          <div v-if="['tratamiento','cirugia','vacuna'].includes(form.tipo)" class="campo-grupo">
            <label class="campo-etiqueta" for="tratamiento">Tratamiento</label>
            <textarea
              id="tratamiento"
              class="campo-entrada fv-textarea"
              v-model="form.tratamiento"
              placeholder="Descripción del tratamiento aplicado..."
              rows="2"
            ></textarea>
          </div>

          <!-- Medicamentos -->
          <div class="campo-grupo">
            <label class="campo-etiqueta" for="medicamentos">Medicamentos Administrados</label>
            <textarea
              id="medicamentos"
              class="campo-entrada fv-textarea"
              v-model="form.medicamentos"
              placeholder="Ej: Amoxicilina 500mg, Vitamina B12..."
              rows="2"
            ></textarea>
          </div>

          <!-- Dosis -->
          <div class="campo-grupo">
            <label class="campo-etiqueta" for="dosis">Dosis / Cantidad</label>
            <input
              id="dosis"
              class="campo-entrada"
              type="text"
              v-model="form.dosis"
              placeholder="Ej: 5ml cada 12h por 5 días"
            />
          </div>

          <!-- Peso al momento -->
          <div class="campo-grupo">
            <label class="campo-etiqueta" for="peso-momento">Peso al Momento de la Atención (kg)</label>
            <input
              id="peso-momento"
              class="campo-entrada"
              type="number"
              v-model="form.peso_al_momento"
              placeholder="Ej: 380"
              min="0"
              max="3000"
              step="0.1"
            />
          </div>

          <!-- Próxima cita -->
          <div class="campo-grupo">
            <label class="campo-etiqueta" for="proxima-cita">Próxima Cita Programada</label>
            <input
              id="proxima-cita"
              class="campo-entrada"
              type="date"
              v-model="form.proxima_cita"
              :min="form.fecha_atencion || hoy"
            />
          </div>

          <!-- Observaciones -->
          <div class="campo-grupo">
            <label class="campo-etiqueta" for="observaciones">Observaciones de Seguimiento</label>
            <textarea
              id="observaciones"
              class="campo-entrada fv-textarea"
              v-model="form.observaciones"
              placeholder="Observaciones adicionales, evolución esperada..."
              rows="3"
            ></textarea>
          </div>

          <!-- Recomendaciones -->
          <div class="campo-grupo">
            <label class="campo-etiqueta" for="recomendaciones">Recomendaciones</label>
            <textarea
              id="recomendaciones"
              class="campo-entrada fv-textarea"
              v-model="form.recomendaciones"
              placeholder="Recomendaciones de manejo, dieta, cuidados..."
              rows="2"
            ></textarea>
          </div>

          <!-- Error -->
          <div v-if="error" class="fv-error">
            ⚠️ {{ error }}
          </div>

          <!-- Botones -->
          <div style="display:flex;gap:12px;margin-top:8px">
            <button
              type="button"
              class="boton boton--secundario"
              style="flex:1"
              @click="cancelar"
            >
              Cancelar
            </button>
            <button
              id="btn-guardar-atencion"
              type="submit"
              class="boton boton--primario"
              style="flex:2"
              :disabled="almacenVet.cargando"
            >
              <span v-if="almacenVet.cargando" class="cargando-spinner" style="width:18px;height:18px;border-width:2px"></span>
              <span v-else>{{ modoEdicion ? '✅ Guardar Cambios' : '💾 Guardar Atención' }}</span>
            </button>
          </div>
        </form>

        <div style="height:40px"></div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup>
/* Vista de formulario para crear/editar una atención veterinaria */
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonButton, IonBackButton } from '@ionic/vue';
import { useAlmacenVeterinario } from '@/stores/veterinario.js';

const route      = useRoute();
const router     = useRouter();
const almacenVet = useAlmacenVeterinario();

// Determinar modo: edición (tiene regId) o creación (tiene animalId)
const modoEdicion = computed(() => !!route.params.regId);
const animalId    = computed(() => route.params.id || null);
const registroId  = computed(() => route.params.regId || null);

const backHref = computed(() =>
  animalId.value ? `/app/veterinario/animal/${animalId.value}` : '/app/veterinario'
);

const hoy = new Date().toISOString().split('T')[0];
const error = ref('');

const form = ref({
  tipo:            '',
  fecha_atencion:  hoy,
  diagnostico:     '',
  tratamiento:     '',
  medicamentos:    '',
  dosis:           '',
  proxima_cita:    '',
  observaciones:   '',
  recomendaciones: '',
  peso_al_momento: '',
});

// En modo edición, precargar los datos del registro existente
onMounted(() => {
  if (modoEdicion.value && registroId.value) {
    const registro = almacenVet.registros.find(r => r.id === Number(registroId.value));
    if (registro) {
      form.value = {
        tipo:            registro.tipo || '',
        fecha_atencion:  registro.fecha_atencion?.split('T')[0] || hoy,
        diagnostico:     registro.diagnostico || '',
        tratamiento:     registro.tratamiento || '',
        medicamentos:    registro.medicamentos || '',
        dosis:           registro.dosis || '',
        proxima_cita:    registro.proxima_cita?.split('T')[0] || '',
        observaciones:   registro.observaciones || '',
        recomendaciones: registro.recomendaciones || '',
        peso_al_momento: registro.peso_al_momento || '',
      };
    }
  }
});

async function guardar() {
  error.value = '';
  if (!form.value.tipo) {
    error.value = 'Debe seleccionar un tipo de atención.';
    return;
  }

  // Limpiar campos vacíos para no enviar strings vacíos
  const datos = Object.fromEntries(
    Object.entries(form.value).filter(([, v]) => v !== '' && v !== null)
  );

  let resultado;
  if (modoEdicion.value) {
    resultado = await almacenVet.actualizarRegistro(registroId.value, datos);
  } else {
    resultado = await almacenVet.crearRegistro(animalId.value, datos);
  }

  if (resultado.exito) {
    router.back();
  } else {
    error.value = resultado.error;
  }
}

function cancelar() {
  router.back();
}
</script>

<style scoped>
.fv-contenido { padding: 0 20px; display: flex; flex-direction: column; gap: 20px; }
.fv-form      { display: flex; flex-direction: column; gap: 16px; }
.fv-textarea  { resize: vertical; min-height: 72px; }
.fv-error {
  background: #F8D7DA; border: 1px solid #dc3545; color: #842029;
  border-radius: var(--borde-radio-md); padding: 12px 14px;
  font-size: var(--tamano-sm);
}
</style>
