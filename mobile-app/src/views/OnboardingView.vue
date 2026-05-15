<template>
  <ion-page>
    <ion-content :fullscreen="true" class="onboarding-contenido">
      <div class="onboarding-envoltorio">
        <!-- Indicadores de paso -->
        <div class="onboarding-indicadores">
          <span v-for="i in pasos.length" :key="i" class="onboarding-punto"
            :class="{ 'onboarding-punto--activo': pasoActual === i - 1 }"></span>
        </div>

        <!-- Contenido del paso -->
        <div class="onboarding-paso animar-aparecer" :key="pasoActual">
          <div class="onboarding-icono">{{ pasos[pasoActual].icono }}</div>
          <h2 class="onboarding-titulo">{{ pasos[pasoActual].titulo }}</h2>
          <p class="onboarding-descripcion">{{ pasos[pasoActual].descripcion }}</p>
        </div>

        <!-- Botones de navegación -->
        <div class="onboarding-acciones">
          <button v-if="pasoActual < pasos.length - 1" class="boton boton--primario boton--completo boton--grande"
            @click="siguientePaso">
            Continuar
          </button>
          <button v-else class="boton boton--primario boton--completo boton--grande" @click="irAlLogin">
            Comenzar
          </button>
          <button v-if="pasoActual < pasos.length - 1" class="boton boton--fantasma boton--completo"
            @click="irAlLogin" style="margin-top:8px">
            Omitir
          </button>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup>
/* Vista de Onboarding con pasos informativos */
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { IonPage, IonContent } from '@ionic/vue';

const router = useRouter();
const pasoActual = ref(0);

const pasos = [
  {
    icono: '📸',
    titulo: 'Captura Inteligente',
    descripcion: 'Toma una foto de tu ganado y nuestra IA estimará su peso en segundos con alta precisión.'
  },
  {
    icono: '📊',
    titulo: 'Seguimiento Completo',
    descripcion: 'Lleva un historial detallado del peso de cada animal con gráficos de evolución y tendencias.'
  },
  {
    icono: '🏡',
    titulo: 'Gestión de Fincas',
    descripcion: 'Administra múltiples fincas, organiza tu hato y genera reportes profesionales en PDF.'
  }
];

function siguientePaso() {
  if (pasoActual.value < pasos.length - 1) pasoActual.value++;
}

function irAlLogin() {
  router.replace('/login');
}
</script>

<style scoped>
.onboarding-contenido { --background: var(--superficie); }
.onboarding-envoltorio {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  padding: 48px 32px;
  gap: 32px;
}
.onboarding-indicadores { display: flex; gap: 8px; }
.onboarding-punto {
  width: 8px; height: 8px; border-radius: 50%;
  background: var(--primario-suave); transition: all 0.3s ease;
}
.onboarding-punto--activo {
  background: var(--primario);
  width: 24px; border-radius: 4px;
}
.onboarding-paso {
  text-align: center; flex: 1;
  display: flex; flex-direction: column;
  align-items: center; justify-content: center; gap: 16px;
}
.onboarding-icono { font-size: 80px; }
.onboarding-titulo {
  font-family: var(--fuente-display);
  font-size: var(--tamano-2xl); font-weight: 700;
  color: var(--texto-primario);
}
.onboarding-descripcion {
  font-size: var(--tamano-base);
  color: var(--texto-secundario);
  max-width: 320px; line-height: 1.6;
}
.onboarding-acciones { width: 100%; max-width: 320px; }
</style>
