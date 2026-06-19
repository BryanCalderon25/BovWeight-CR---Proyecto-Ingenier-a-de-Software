<template>
  <ion-page>
    <ion-header class="ion-no-border">
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button default-href="/app/configuracion" text="" />
        </ion-buttons>

        <ion-title>
          Política de Privacidad
        </ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true">

      <div class="contenido">

        <section class="tarjeta animar-aparecer">

          <h2>Privacidad</h2>

          <p>
            BovWeight CR protege la información almacenada por los usuarios y procura mantener la confidencialidad de los datos registrados.
          </p>

          <p>
            La información recopilada es utilizada únicamente para proporcionar las funcionalidades ofrecidas por la aplicación.
          </p>

          <p>
            No se comparte información personal con terceros sin autorización del usuario, salvo obligación legal.
          </p>

          <p>
            El usuario puede solicitar la actualización o eliminación de sus datos según corresponda.
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