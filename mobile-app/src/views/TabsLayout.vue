<template>
  <ion-page>
    <ion-tabs>
      <ion-router-outlet></ion-router-outlet>
      <!-- Tab bar visible para ganaderos (no invitados) y veterinarios (registrados o invitados) -->
      <ion-tab-bar slot="bottom" v-if="!esInvitado || (esInvitado && esVeterinario)">
        <!-- Tabs para todos los usuarios registrados (no invitados) -->
        <template v-if="!esInvitado">
          <ion-tab-button tab="inicio" href="/app/inicio">
            <ion-icon :icon="homeOutline" />
            <ion-label>Inicio</ion-label>
          </ion-tab-button>

          <ion-tab-button tab="fincas" href="/app/fincas">
            <ion-icon :icon="businessOutline" />
            <ion-label>Fincas</ion-label>
          </ion-tab-button>

          <ion-tab-button tab="animales" href="/app/animales">
            <ion-icon :icon="pawOutline" />
            <ion-label>Animales</ion-label>
          </ion-tab-button>

          <ion-tab-button tab="pesar" href="/app/pesar" class="tab-pesar">
            <ion-icon :icon="scaleOutline" />
            <ion-label>Pesar</ion-label>
          </ion-tab-button>

          <ion-tab-button tab="historial" href="/app/historial">
            <ion-icon :icon="timeOutline" />
            <ion-label>Historial</ion-label>
          </ion-tab-button>
        </template>

        <!-- Tab exclusivo de veterinarios invitados -->
        <template v-if="esInvitado && esVeterinario">
          <ion-tab-button tab="veterinario" href="/app/veterinario">
            <ion-icon :icon="medkitOutline" />
            <ion-label>Historial Vet.</ion-label>
          </ion-tab-button>
        </template>
      </ion-tab-bar>
    </ion-tabs>
  </ion-page>
</template>

<script setup>
/* Layout principal con tabs de navegación inferior */
import { computed } from 'vue';
import { IonPage, IonTabs, IonTabBar, IonTabButton, IonIcon, IonLabel, IonRouterOutlet } from '@ionic/vue';
import { homeOutline, businessOutline, pawOutline, scaleOutline, timeOutline, medkitOutline } from 'ionicons/icons';
import { useAlmacenAuth } from '@/stores/auth.js';

const almacenAuth = useAlmacenAuth();

const esVeterinario = computed(() => {
  const rol = almacenAuth.rolUsuario;
  if (rol === 'invitado') {
    return almacenAuth.usuario?.guest_role === 'veterinario';
  }
  return rol === 'veterinario';
});

const esInvitado = computed(() => {
  return almacenAuth.rolUsuario === 'invitado';
});
</script>

<style scoped>
.tab-pesar {
  --color-selected: var(--acento);
}
ion-tab-bar {
  --background: var(--superficie-tarjeta);
  --border: 1px solid var(--borde-color);
  border-radius: 24px 24px 0 0;
  padding-bottom: env(safe-area-inset-bottom, 0);
  box-shadow: 0 -4px 20px rgba(26,29,23,0.06);
}
ion-tab-button {
  --color: var(--texto-terciario);
  --color-selected: var(--primario);
  font-family: var(--fuente-cuerpo);
  font-size: 11px;
  --padding-top: 8px;
  --padding-bottom: 4px;
}
</style>
