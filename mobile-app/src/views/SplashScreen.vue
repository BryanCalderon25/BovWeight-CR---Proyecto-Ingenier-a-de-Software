<template>
  <ion-page>
    <ion-content class="splash-contenido" :fullscreen="true">
      <div class="splash-centro">
        <!-- Icono animado -->
        <div class="splash-logo" :class="{ 'splash-logo--visible': mostrar }">
          <div class="splash-icono">
            <svg viewBox="0 0 80 80" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="40" cy="40" r="38" fill="#414833" stroke="#C2C5AA" stroke-width="2"/>
              <path d="M25 50 Q30 30 40 28 Q50 30 55 50" stroke="#C2C5AA" stroke-width="3" fill="none" stroke-linecap="round"/>
              <circle cx="33" cy="38" r="3" fill="#C2C5AA"/>
              <circle cx="47" cy="38" r="3" fill="#C2C5AA"/>
              <path d="M35 48 Q40 52 45 48" stroke="#C2C5AA" stroke-width="2" fill="none" stroke-linecap="round"/>
              <line x1="22" y1="30" x2="18" y2="22" stroke="#C2C5AA" stroke-width="2" stroke-linecap="round"/>
              <line x1="58" y1="30" x2="62" y2="22" stroke="#C2C5AA" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </div>
        </div>
        <h1 class="splash-titulo" :class="{ 'splash-titulo--visible': mostrarTexto }">
          <span class="splash-titulo__bov">Bov</span><span class="splash-titulo__weight">Weight</span>
          <span class="splash-titulo__cr">CR</span>
        </h1>
        <p class="splash-subtitulo" :class="{ 'splash-subtitulo--visible': mostrarTexto }">
          Estimación inteligente de peso bovino
        </p>
        <div class="splash-barra" :class="{ 'splash-barra--visible': mostrarBarra }">
          <div class="splash-barra__progreso"></div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup>
/* Vista de Splash Screen con animaciones de entrada */
import { ref, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { IonPage, IonContent } from '@ionic/vue';
import { useAlmacenAuth } from '@/stores/auth.js';

const router = useRouter();
const almacenAuth = useAlmacenAuth();

const mostrar = ref(false);
const mostrarTexto = ref(false);
const mostrarBarra = ref(false);

onMounted(() => {
  setTimeout(() => { mostrar.value = true; }, 200);
  setTimeout(() => { mostrarTexto.value = true; }, 600);
  setTimeout(() => { mostrarBarra.value = true; }, 1000);
  setTimeout(() => {
    if (almacenAuth.estaAutenticado) {
      router.replace('/app/inicio');
    } else {
      router.replace('/bienvenida');
    }
  }, 3000);
});
</script>

<style scoped>
.splash-contenido {
  --background: linear-gradient(160deg, #333D29 0%, #414833 40%, #1A1D17 100%);
}
.splash-centro {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  height: 100%;
  gap: 16px;
}
.splash-logo {
  opacity: 0;
  transform: scale(0.5);
  transition: all 0.8s cubic-bezier(0.16, 1, 0.3, 1);
}
.splash-logo--visible {
  opacity: 1;
  transform: scale(1);
}
.splash-icono svg {
  width: 100px;
  height: 100px;
  filter: drop-shadow(0 8px 32px rgba(194, 197, 170, 0.3));
}
.splash-titulo {
  font-family: var(--fuente-display);
  font-size: 2.5rem;
  font-weight: 800;
  color: #fff;
  opacity: 0;
  transform: translateY(20px);
  transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1);
}
.splash-titulo--visible { opacity: 1; transform: translateY(0); }
.splash-titulo__bov { color: #C2C5AA; }
.splash-titulo__weight { color: #fff; }
.splash-titulo__cr { color: #A68A64; font-size: 1.5rem; margin-left: 4px; }
.splash-subtitulo {
  font-size: 0.875rem;
  color: rgba(255,255,255,0.6);
  opacity: 0;
  transform: translateY(10px);
  transition: all 0.6s cubic-bezier(0.16, 1, 0.3, 1) 0.15s;
}
.splash-subtitulo--visible { opacity: 1; transform: translateY(0); }
.splash-barra {
  width: 160px;
  height: 3px;
  background: rgba(255,255,255,0.15);
  border-radius: 2px;
  margin-top: 24px;
  opacity: 0;
  transition: opacity 0.4s ease;
  overflow: hidden;
}
.splash-barra--visible { opacity: 1; }
.splash-barra__progreso {
  height: 100%;
  background: linear-gradient(90deg, #C2C5AA, #A68A64);
  border-radius: 2px;
  animation: cargaBarra 2s ease-in-out forwards;
}
@keyframes cargaBarra {
  from { width: 0; }
  to { width: 100%; }
}
</style>
