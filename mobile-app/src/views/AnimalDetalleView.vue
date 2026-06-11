<template>
  <ion-page>
    <ion-header class="ion-no-border">
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button :default-href="almacenAuth.rolUsuario === 'invitado' ? `/app/fincas/${almacenAuth.usuario?.invited_farm_id}` : '/app/animales'" text="" />
        </ion-buttons>
        <ion-title>Detalle Animal</ion-title>
        <ion-buttons slot="end" v-if="almacenAuth.rolUsuario !== 'invitado'">
          <ion-button @click="editando = !editando">
            <ion-icon :icon="editando ? closeOutline : createOutline" />
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>
    <ion-content :fullscreen="true">
      <div v-if="animal" class="detalle-contenido">
        <!-- Cabecera del animal -->
        <div class="detalle-cabecera animar-aparecer">
          <div class="detalle-avatar">{{ (animal.nombre || 'A').charAt(0) }}</div>
          <h2 class="titulo-seccion">{{ animal.nombre || 'Sin nombre' }}</h2>
          <span class="insignia" :class="animal.estado === 'inactivo' ? 'insignia--peligro' : 'insignia--exito'">
            {{ animal.estado === 'inactivo' ? 'Inactivo' : 'Activo' }}
          </span>
          <p style="font-size:var(--tamano-sm);color:var(--texto-terciario)">{{ animal.arete }} · {{ animal.raza }}</p>
        </div>

        <!-- Info rápida -->
        <div class="detalle-info animar-aparecer animar-delay-1">
          <div class="detalle-info-item">
            <span class="campo-etiqueta">Sexo</span>
            <span>{{ animalSexo }}</span>
          </div>
          <div class="detalle-info-item">
            <span class="campo-etiqueta">Edad</span>
            <span>{{ animalEdad }}</span>
          </div>
          <div class="detalle-info-item">
            <span class="campo-etiqueta">Finca</span>
            <span>{{ animalFinca }}</span>
          </div>
          <div class="detalle-info-item">
            <span class="campo-etiqueta">Último Pesaje</span>
            <span>{{ animalUltimoPesaje }}</span>
          </div>
        </div>

        <!-- Peso actual -->
        <div class="tarjeta tarjeta--metrica animar-aparecer animar-delay-2">
          <span class="etiqueta-seccion">PESO ACTUAL</span>
          <div style="display:flex;align-items:baseline;margin-top:8px">
            <span class="metrica-grande">{{ animalPesoActual }}</span>
            <span class="metrica-unidad">kg</span>
          </div>
        </div>

        <!-- Historial de peso -->
        <section class="animar-aparecer animar-delay-3">
          <span class="etiqueta-seccion">HISTORIAL DE PESO</span>
          <div v-if="historial.length" class="detalle-historial">
            <div v-for="p in historial" :key="p.id" class="detalle-historial-item">
              <div>
                <strong>{{ p.pesoEstimado }} kg</strong>
                <span style="display:block;font-size:var(--tamano-xs);color:var(--texto-terciario)">
                  ± {{ p.margenError }} kg · {{ p.confianza }}% confianza
                </span>
              </div>
              <span style="font-size:var(--tamano-xs);color:var(--texto-terciario)">{{ p.fecha }}</span>
            </div>
          </div>
          <div v-else class="estado-vacio" style="padding:24px">
            <span style="font-size:32px">📊</span>
            <p class="estado-vacio__descripcion">Sin registros de peso</p>
          </div>
        </section>

        <!-- Acciones -->
        <div class="detalle-acciones animar-aparecer animar-delay-4" v-if="almacenAuth.rolUsuario !== 'invitado'">
          <button class="boton boton--primario boton--completo" @click="irAPesar">📷 Nuevo Pesaje</button>
          <button class="boton boton--secundario boton--completo" @click="cambiarEstado">🔄 Cambiar Estado Animal</button>
          <button class="boton boton--secundario boton--completo" @click="confirmarEliminar" style="color:var(--peligro)">🗑️ Eliminar Animal</button>
        </div>

        <!-- Modal edición -->
        <div v-if="editando" class="modal-fondo" @click.self="editando = false">
          <div class="modal-contenido vidrio">
            <h3 style="font-family:var(--fuente-display);font-weight:700;font-size:var(--tamano-xl)">Editar Animal</h3>
            <div class="campo-grupo">
              <label class="campo-etiqueta">Nombre</label>
              <input class="campo-entrada" v-model="formularioEdicion.nombre" />
            </div>
            <div class="campo-grupo">
              <label class="campo-etiqueta">Estado</label>
              <select class="campo-entrada" v-model="formularioEdicion.estado">
                <option value="activo">Activo</option>
                <option value="inactivo">Inactivo</option>
              </select>
            </div>
            <div class="campo-grupo">
              <label class="campo-etiqueta">Género</label>
              <select class="campo-entrada" v-model="formularioEdicion.genero">
                <option value="Macho">Macho</option>
                <option value="Hembra">Hembra</option>
              </select>
            </div>
            <div class="campo-grupo">
              <label class="campo-etiqueta">Fecha de Nacimiento</label>
              <input type="date" class="campo-entrada" v-model="formularioEdicion.fecha_nacimiento" />
            </div>
            <div class="campo-grupo">
              <label class="campo-etiqueta">Propósito</label>
              <select class="campo-entrada" v-model="formularioEdicion.proposito">
                <option value="Carne">Carne</option>
                <option value="Leche">Leche</option>
                <option value="Doble propósito">Doble propósito</option>
              </select>
            </div>
            <div class="campo-grupo">
              <label class="campo-etiqueta">Notas</label>
              <textarea class="campo-entrada" v-model="formularioEdicion.notas" rows="3" style="resize:vertical"></textarea>
            </div>
            <div style="display:flex;gap:12px">
              <button class="boton boton--secundario" style="flex:1" @click="editando = false">Cancelar</button>
              <button class="boton boton--primario" style="flex:1" @click="guardarEdicion">Guardar</button>
            </div>
          </div>
        </div>

        <div style="height:32px"></div>
      </div>
      <div v-else class="estado-vacio">
        <span class="estado-vacio__icono">🐮</span>
        <h3 class="estado-vacio__titulo">Animal no encontrado</h3>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup>
/* Vista de Detalle de Animal */
import { ref, computed, reactive, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonButton, IonIcon, IonBackButton, alertController } from '@ionic/vue';
import { createOutline, closeOutline } from 'ionicons/icons';
import { useAlmacenAnimales } from '@/stores/animales.js';
import { useAlmacenPesajes } from '@/stores/pesajes.js';
import { useAlmacenAuth } from '@/stores/auth.js';
import { useAlmacenFincas } from '@/stores/fincas.js';

const route = useRoute();
const router = useRouter();
const almacenAnimales = useAlmacenAnimales();
const almacenPesajes = useAlmacenPesajes();
const almacenAuth = useAlmacenAuth();
const almacenFincas = useAlmacenFincas();

const animal = computed(() => almacenAnimales.obtenerPorId(route.params.id));
const historial = computed(() => almacenPesajes.obtenerHistorialAnimal(route.params.id));
const editando = ref(false);
const formularioEdicion = reactive({
  nombre: '',
  genero: 'Macho',
  fecha_nacimiento: '',
  proposito: 'Carne',
  estado: 'activo',
  notas: ''
});

// Computed properties mapping to database values
const animalSexo = computed(() => {
  return animal.value?.genero || 'No especificado';
});

const animalEdad = computed(() => {
  if (!animal.value?.fecha_nacimiento) return 'No especificada';
  const nac = new Date(animal.value.fecha_nacimiento);
  const hoy = new Date();
  let anos = hoy.getFullYear() - nac.getFullYear();
  let meses = hoy.getMonth() - nac.getMonth();
  if (meses < 0 || (meses === 0 && hoy.getDate() < nac.getDate())) {
    anos--;
    meses += 12;
  }
  if (anos > 0) {
    return `${anos} año${anos > 1 ? 's' : ''} ${meses > 0 ? `y ${meses} mes${meses > 1 ? 'es' : ''}` : ''}`;
  }
  return `${meses} mes${meses > 1 ? 'es' : ''}`;
});

const animalFinca = computed(() => {
  if (animal.value?.farm?.nombre) {
    return animal.value.farm.nombre;
  }
  if (animal.value?.farm_id) {
    const f = almacenFincas.lista.find(f => Number(f.id) === Number(animal.value.farm_id));
    if (f) return f.nombre;
  }
  return 'No especificada';
});

const animalUltimoPesaje = computed(() => {
  if (historial.value && historial.value.length > 0) {
    const ultimo = historial.value[0];
    return `${ultimo.pesoEstimado} kg (${ultimo.fecha})`;
  }
  return 'Sin registros';
});

const animalPesoActual = computed(() => {
  if (animal.value?.peso_actual) {
    return Math.round(Number(animal.value.peso_actual));
  }
  if (historial.value && historial.value.length > 0) {
    return historial.value[0].pesoEstimado;
  }
  return '—';
});

onMounted(async () => {
  // Cargar datos del animal desde el servidor si no están cargados localmente
  if (!animal.value) {
    await almacenAnimales.cargarAnimal(route.params.id);
  }

  // Cargar historial de pesajes real para este animal
  await almacenPesajes.cargarHistorialAnimal(route.params.id);

  // Asegurar que las fincas estén cargadas para mapear el nombre de la finca
  if (almacenFincas.lista.length === 0) {
    await almacenFincas.cargarFincas();
  }

  if (animal.value) {
    formularioEdicion.nombre = animal.value.nombre || '';
    formularioEdicion.genero = animal.value.genero || 'Macho';
    formularioEdicion.fecha_nacimiento = animal.value.fecha_nacimiento || '';
    formularioEdicion.proposito = animal.value.proposito || 'Carne';
    formularioEdicion.estado = animal.value.estado || 'activo';
    formularioEdicion.notas = animal.value.notas || '';
  }
});

function guardarEdicion() {
  almacenAnimales.actualizarAnimal(route.params.id, formularioEdicion);
  editando.value = false;
}

function irAPesar() { router.push('/app/pesar'); }

async function cambiarEstado() {
  const alertSheet = await alertController.create({
    header: 'Cambiar Estado del Animal',
    inputs: [
      {
        type: 'radio',
        label: 'Activo',
        value: 'activo',
        checked: animal.value.estado !== 'inactivo'
      },
      {
        type: 'radio',
        label: 'Inactivo',
        value: 'inactivo',
        checked: animal.value.estado === 'inactivo'
      }
    ],
    buttons: [
      {
        text: 'Cancelar',
        role: 'cancel'
      },
      {
        text: 'Confirmar',
        handler: async (nuevoEstado) => {
          if (!nuevoEstado) return;
          const resultado = await almacenAnimales.actualizarAnimal(route.params.id, {
            estado: nuevoEstado
          });
          if (resultado.exito) {
            await almacenAnimales.cargarAnimal(route.params.id);
          } else {
            alert(resultado.error || 'Error al actualizar el estado.');
          }
        }
      }
    ]
  });

  await alertSheet.present();
}

function confirmarEliminar() {
  if (confirm('¿Está seguro de eliminar este animal?')) {
    almacenAnimales.eliminarAnimal(route.params.id);
    router.replace('/app/animales');
  }
}
</script>

<style scoped>
.detalle-contenido{padding:0 20px;display:flex;flex-direction:column;gap:20px}
.detalle-cabecera{text-align:center;display:flex;flex-direction:column;align-items:center;gap:8px}
.detalle-avatar{width:80px;height:80px;border-radius:50%;background:var(--primario-ultra-suave);display:flex;align-items:center;justify-content:center;font-family:var(--fuente-display);font-weight:800;font-size:2rem;color:var(--primario)}
.detalle-info{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.detalle-info-item{background:var(--superficie-tarjeta);padding:14px;border-radius:var(--borde-radio-md);border:1px solid var(--borde-color);display:flex;flex-direction:column;gap:4px;font-size:var(--tamano-sm);font-weight:600}
.detalle-historial{display:flex;flex-direction:column;gap:8px;margin-top:12px}
.detalle-historial-item{display:flex;justify-content:space-between;align-items:center;padding:14px 16px;background:var(--superficie-tarjeta);border:1px solid var(--borde-color);border-radius:var(--borde-radio-md)}
.detalle-acciones{display:flex;flex-direction:column;gap:12px}
.modal-fondo{position:fixed;inset:0;background:rgba(0,0,0,0.4);display:flex;align-items:flex-end;justify-content:center;z-index:1000}
.modal-contenido{width:100%;max-width:480px;padding:32px 24px;border-radius:var(--borde-radio-xl) var(--borde-radio-xl) 0 0;display:flex;flex-direction:column;gap:14px}
</style>
