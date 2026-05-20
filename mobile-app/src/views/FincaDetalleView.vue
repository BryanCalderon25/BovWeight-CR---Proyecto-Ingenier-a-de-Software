<template>
  <ion-page>
    <ion-header class="ion-no-border">
      <ion-toolbar>
        <ion-buttons slot="start" v-if="almacenAuth.rolUsuario !== 'invitado'">
          <ion-back-button default-href="/app/fincas" text="" />
        </ion-buttons>
        <ion-title>Detalle Finca</ion-title>
        <ion-buttons slot="end" v-if="almacenAuth.rolUsuario === 'invitado'">
          <ion-button @click="salirAccesoTemporal" color="danger" style="font-weight: 600; font-size: 14px">
            🚪 Salir
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>
    <ion-content :fullscreen="true">
      <div v-if="finca" style="padding:0 20px;display:flex;flex-direction:column;gap:20px">
        <div class="animar-aparecer" style="text-align:center">
          <span style="font-size:64px">🏡</span>
          <h2 class="titulo-seccion">{{ finca.nombre }}</h2>
          <p style="font-size:var(--tamano-sm);color:var(--texto-terciario)">📍 {{ finca.ubicacion }}, {{ finca.provincia }}</p>
        </div>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px" class="animar-aparecer animar-delay-1">
          <div class="tarjeta tarjeta--metrica"><span class="etiqueta-seccion">ANIMALES</span><span class="metrica-grande" style="font-size:var(--tamano-2xl);margin-top:8px">{{ finca.animals?.length || 0 }}</span></div>
          <div class="tarjeta tarjeta--metrica"><span class="etiqueta-seccion">HECTÁREAS</span><span class="metrica-grande" style="font-size:var(--tamano-2xl);margin-top:8px">{{ finca.area_hectareas || 0 }}</span></div>
        </div>
        <div class="tarjeta animar-aparecer animar-delay-2">
          <span class="etiqueta-seccion">DESCRIPCIÓN</span>
          <p style="margin-top:8px;font-size:var(--tamano-sm);color:var(--texto-secundario)">{{ finca.descripcion }}</p>
        </div>
        <div class="tarjeta animar-aparecer animar-delay-3">
          <span class="etiqueta-seccion">UBICACIÓN</span>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-top:8px;font-size:var(--tamano-sm)">
            <div><span class="campo-etiqueta">Provincia</span><br/>{{ finca.provincia }}</div>
            <div><span class="campo-etiqueta">Cantón</span><br/>{{ finca.canton }}</div>
          </div>
        </div>

        <!-- Listado de Ganado en la Finca -->
        <div class="tarjeta animar-aparecer animar-delay-3" style="border: 1px solid var(--borde-color)">
          <span class="etiqueta-seccion" style="color:var(--primario)">🐮 GANADO EN ESTA FINCA</span>
          <p style="margin-top:6px;font-size:var(--tamano-sm);color:var(--texto-secundario)">
            Seleccione un animal a continuación para visualizar su historial de pesajes y detalles médicos/comerciales.
          </p>

          <div v-if="finca.animals && finca.animals.length" style="display:flex;flex-direction:column;gap:10px;margin-top:16px">
            <div v-for="animal in finca.animals" :key="animal.id" 
              style="display:flex;align-items:center;gap:14px;padding:12px 16px;background:var(--superficie-elevada, var(--superficie-tarjeta));border-radius:var(--borde-radio-md);border:1px solid var(--borde-color);cursor:pointer;transition:transform 0.2s"
              @click="verDetalleAnimal(animal.id)"
              onmouseover="this.style.transform='translateY(-1px)'"
              onmouseout="this.style.transform='none'">
              <div style="width:40px;height:40px;border-radius:50%;background:var(--primario-ultra-suave);display:flex;align-items:center;justify-content:center;font-family:var(--fuente-display);font-weight:700;color:var(--primario)">
                {{ animal.nombre ? animal.nombre.charAt(0) : 'A' }}
              </div>
              <div style="flex:1;display:flex;flex-direction:column;gap:2px">
                <div style="display:flex;align-items:center;justify-content:space-between">
                  <strong style="font-size:var(--tamano-sm);color:var(--texto-principal)">{{ animal.nombre || 'Sin nombre' }}</strong>
                  <span style="font-size:var(--tamano-sm);color:var(--primario);font-weight:bold;font-family:var(--fuente-display)">{{ animal.peso_actual || 0 }} kg</span>
                </div>
                <span style="font-size:var(--tamano-xs);color:var(--texto-secundario)">{{ animal.arete }} · {{ animal.raza }} · {{ animal.genero }}</span>
              </div>
              <ion-icon :icon="chevronForwardOutline" style="color:var(--texto-secundario);font-size:18px" />
            </div>
          </div>
          <div v-else style="text-align:center;padding:24px;color:var(--texto-secundario);font-size:var(--tamano-sm)">
            🐮 No hay animales registrados en esta finca aún.
          </div>
        </div>

        <!-- Acceso Temporal para Invitados -->
        <div v-if="almacenAuth.rolUsuario !== 'invitado'" class="tarjeta animar-aparecer animar-delay-4" style="margin-bottom:30px; border: 1.5px dashed var(--primario-suave); background: var(--primario-ultra-suave)">
          <span class="etiqueta-seccion" style="color:var(--primario)">🔑 ACCESO TEMPORAL PARA INVITADOS</span>
          <p style="margin-top:8px;font-size:var(--tamano-sm);color:var(--texto-secundario);line-height:1.4">
            Genere un enlace de acceso temporal para que un veterinario o comprador externo pueda ver el peso de sus animales sin necesidad de una cuenta permanente.
          </p>
          
          <div style="margin-top:16px;display:flex;flex-direction:column;gap:12px">
            <div>
              <label class="campo-etiqueta" style="display:block;margin-bottom:6px">Rol del Invitado</label>
              <select v-model="rolInvitado" style="width:100%;padding:10px;border-radius:var(--borde-radio-md);border:1px solid var(--borde-color);background:var(--superficie-tarjeta);color:var(--texto-principal)">
                <option value="veterinario">🩺 Veterinario (Cálculo de Dosis/Salud)</option>
                <option value="comprador">💰 Comprador (Revisión de Pesos/Oferta)</option>
              </select>
            </div>

            <div>
              <label class="campo-etiqueta" style="display:block;margin-bottom:6px">Duración del Enlace</label>
              <select v-model="duracionAcceso" style="width:100%;padding:10px;border-radius:var(--borde-radio-md);border:1px solid var(--borde-color);background:var(--superficie-tarjeta);color:var(--texto-principal)">
                <option value="12">12 horas (Acceso rápido)</option>
                <option value="24">1 día (24 horas)</option>
                <option value="48">2 días (48 horas)</option>
                <option value="168">1 semana (168 horas)</option>
              </select>
            </div>

            <button class="boton boton--primario boton--completo" style="margin-top:8px" @click="generarEnlaceInvitado" :disabled="generando">
              {{ generando ? 'Generando Enlace...' : '⚡ Generar Enlace de Invitación' }}
            </button>

            <!-- Cuadro de resultado del enlace generado -->
            <div v-if="enlaceGenerado" style="margin-top:12px;padding:12px;background:var(--superficie-tarjeta);border:1px solid var(--borde-color);border-radius:var(--borde-radio-md)">
              <span class="campo-etiqueta" style="color:var(--exito);font-weight:bold">¡Enlace Creado! (Vence: {{ fechaVencimientoFormatted }}):</span>
              <div style="display:flex;align-items:center;gap:8px;margin-top:8px">
                <input type="text" readonly :value="enlaceGenerado" style="flex:1;padding:10px;font-size:var(--tamano-xs);border:1px solid var(--borde-color);border-radius:var(--borde-radio-md);background:var(--borde-color);color:var(--texto-secundario)"/>
                <button class="boton boton--secundario" style="padding:10px" @click="copiarEnlace">📋 Copiar</button>
              </div>

              <!-- Enviar de forma directa por WhatsApp o Correo -->
              <div style="margin-top:12px;display:flex;flex-direction:column;gap:8px">
                <button class="boton boton--secundario boton--pequeno" @click="compartirWhatsApp">
                  💬 Enviar por WhatsApp
                </button>
                <button class="boton boton--secundario boton--pequeno" @click="compartirCorreo">
                  📧 Enviar por Correo
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup>
/* Vista Detalle de Finca */
import { ref, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton, IonButton, IonIcon } from '@ionic/vue';
import { chevronForwardOutline } from 'ionicons/icons';
import { useAlmacenFincas } from '@/stores/fincas.js';
import { useAlmacenAuth } from '@/stores/auth.js';
import api from '@/services/api';

const route = useRoute();
const router = useRouter();
const almacenFincas = useAlmacenFincas();
const almacenAuth = useAlmacenAuth();
const finca = computed(() => almacenFincas.obtenerPorId(route.params.id));

async function salirAccesoTemporal() {
  await almacenAuth.cerrarSesion();
  router.replace('/login');
}

function verDetalleAnimal(animalId) {
  router.push(`/app/animales/${animalId}`);
}

onMounted(() => {
  if (almacenFincas.lista.length === 0) {
    almacenFincas.cargarFincas();
  }
});

const rolInvitado = ref('veterinario');
const duracionAcceso = ref('24');
const generando = ref(false);
const enlaceGenerado = ref('');
const fechaVencimiento = ref('');

const fechaVencimientoFormatted = computed(() => {
  if (!fechaVencimiento.value) return '';
  const d = new Date(fechaVencimiento.value);
  return d.toLocaleString('es-CR', { day: 'numeric', month: 'short', hour: '2-digit', minute: '2-digit' });
});

async function generarEnlaceInvitado() {
  generando.value = true;
  enlaceGenerado.value = '';
  try {
    const respuesta = await api.post('/invitaciones', {
      farm_id: route.params.id,
      role: rolInvitado.value,
      expires_in_hours: parseInt(duracionAcceso.value)
    });
    
    let enlace = respuesta.data.datos.enlace;
    const origin = window.location.origin;
    if (enlace.startsWith('http://localhost:8100')) {
      enlace = enlace.replace('http://localhost:8100', origin);
    }
    
    enlaceGenerado.value = enlace;
    fechaVencimiento.value = respuesta.data.datos.expires_at;
  } catch (err) {
    console.error('Error al generar enlace:', err);
    const mensajeError = err.response?.data?.mensaje || err.response?.data?.message || 'No se pudo generar el enlace de invitación. Asegúrese de que el servidor esté activo.';
    alert(mensajeError);
  } finally {
    generando.value = false;
  }
}

function copiarEnlace() {
  if (!enlaceGenerado.value) return;
  navigator.clipboard.writeText(enlaceGenerado.value);
  alert('¡Enlace de invitación copiado al portapapeles!');
}

async function compartirWhatsApp() {
  if (!enlaceGenerado.value) return;
  
  const mensaje = `Hola, te comparto mi enlace de acceso temporal como *${rolInvitado.value.toUpperCase()}* para ver el inventario y peso de los animales en la finca *${finca.value.nombre}*:\n\n${enlaceGenerado.value}`;
  
  if (navigator.share) {
    try {
      await navigator.share({
        title: 'Acceso Invitado BovWeight CR',
        text: mensaje
      });
    } catch (e) {
      if (e.name !== 'AbortError') {
        window.open(`https://wa.me/?text=${encodeURIComponent(mensaje)}`, '_blank');
      }
    }
  } else {
    window.open(`https://wa.me/?text=${encodeURIComponent(mensaje)}`, '_blank');
  }
}

async function compartirCorreo() {
  if (!enlaceGenerado.value) return;
  
  const subject = `Acceso temporal a la finca ${finca.value.nombre} - BovWeight CR`;
  const body = `Hola,\n\nTe comparto el enlace de acceso temporal con rol de ${rolInvitado.value.toUpperCase()} para ingresar a mi finca y visualizar la estimación de pesos de los animales:\n\nEnlace de acceso: ${enlaceGenerado.value}\n\nNota: Este enlace vencerá el ${fechaVencimientoFormatted.value}.\n\nGenerado por BovWeight CR.`;
  
  if (navigator.share) {
    try {
      await navigator.share({
        title: subject,
        text: body
      });
    } catch (e) {
      if (e.name !== 'AbortError') {
        window.open(`mailto:?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`, '_blank');
      }
    }
  } else {
    window.open(`mailto:?subject=${encodeURIComponent(subject)}&body=${encodeURIComponent(body)}`, '_blank');
  }
}
</script>
