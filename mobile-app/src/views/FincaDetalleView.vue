<template>
  <ion-page>
    <ion-header class="ion-no-border">
      <ion-toolbar>
        <ion-buttons slot="start"><ion-back-button default-href="/app/fincas" text="" /></ion-buttons>
        <ion-title>Detalle Finca</ion-title>
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
          <div class="tarjeta tarjeta--metrica"><span class="etiqueta-seccion">ANIMALES</span><span class="metrica-grande" style="font-size:var(--tamano-2xl);margin-top:8px">{{ finca.animales }}</span></div>
          <div class="tarjeta tarjeta--metrica"><span class="etiqueta-seccion">HECTÁREAS</span><span class="metrica-grande" style="font-size:var(--tamano-2xl);margin-top:8px">{{ finca.tamano }}</span></div>
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
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup>
/* Vista Detalle de Finca */
import { computed } from 'vue';
import { useRoute } from 'vue-router';
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton } from '@ionic/vue';
import { useAlmacenFincas } from '@/stores/fincas.js';
const route = useRoute();
const finca = computed(() => useAlmacenFincas().obtenerPorId(route.params.id));
</script>
