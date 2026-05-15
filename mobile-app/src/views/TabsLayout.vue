<template>
  <ion-page>
    <ion-tabs>
      <ion-router-outlet></ion-router-outlet>
      <ion-tab-bar slot="bottom">
        <ion-tab-button tab="inicio" href="/app/inicio">
          <ion-icon :icon="homeOutline" />
          <ion-label>Inicio</ion-label>
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
      </ion-tab-bar>
    </ion-tabs>
  </ion-page>
</template>

<script setup>
/* Layout principal con tabs de navegación inferior */
import { IonPage, IonTabs, IonTabBar, IonTabButton, IonIcon, IonLabel, IonRouterOutlet } from '@ionic/vue';
import { homeOutline, pawOutline, scaleOutline, timeOutline } from 'ionicons/icons';
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
