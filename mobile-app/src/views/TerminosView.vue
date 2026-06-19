<template>
  <ion-page>
    <ion-header class="ion-no-border">
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button default-href="/app/configuracion" text="" />
        </ion-buttons>

        <ion-title>
          Términos y Condiciones
        </ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true">

      <div class="contenido">

        <section class="tarjeta animar-aparecer">

          <h2>Uso de la aplicación</h2>

          <p>
            El uso de BovWeight CR implica la aceptación de los presentes términos y condiciones.
          </p>

          <p>
            La aplicación está destinada a la administración y control de información ganadera.
          </p>

          <p>
            El usuario es responsable de la información registrada dentro de la plataforma.
          </p>

          <p>
            Los datos almacenados deberán utilizarse únicamente con fines lícitos y relacionados con la actividad ganadera.
          </p>

        </section>

      </div>

    </ion-content>
  </ion-page>
</template>

<script setup>
import {
  IonPage,
  IonHeader,
  IonToolbar,
  IonTitle,
  IonContent,
  IonButtons,
  IonBackButton
} from '@ionic/vue';
</script>

<style scoped>
.contenido{
  padding:0 20px;
}

.tarjeta{
  background:var(--superficie-tarjeta);
  border:1px solid var(--borde-color);
  border-radius:var(--borde-radio-xl);
  padding:28px;
  line-height:1.9;
}
</style>