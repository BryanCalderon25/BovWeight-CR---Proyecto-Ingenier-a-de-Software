<template>
  <ion-page>
    <ion-header class="ion-no-border">
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button default-href="/app/configuracion" text="" />
        </ion-buttons>
        <ion-title>Acerca de</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true">
      <div class="contenido">

        <section class="animar-aparecer">
          <div class="tarjeta-principal">
            <div class="logo">🐄</div>

            <h1>BovWeight CR</h1>

            <p class="version">
              Versión 1.0.0
            </p>

            <p class="descripcion">
              BovWeight CR es una aplicación diseñada para facilitar la gestión ganadera y la estimación del peso bovino mediante tecnologías de visión artificial y herramientas de administración.
            </p>
          </div>
        </section>

        <section class="animar-aparecer animar-delay-1">
          <span class="etiqueta-seccion">
            CARACTERÍSTICAS
          </span>

          <div class="lista">
            <div class="item">
              📸 Pesaje mediante imágenes
            </div>

            <div class="item">
              🐄 Gestión de animales
            </div>

            <div class="item">
              🏡 Administración de fincas
            </div>

            <div class="item">
              📊 Reportes y estadísticas
            </div>

            <div class="item">
              ☁️ Sincronización de información
            </div>
          </div>
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
  display:flex;
  flex-direction:column;
  gap:20px;
}

.tarjeta-principal{
  background:var(--superficie-tarjeta);
  border:1px solid var(--borde-color);
  border-radius:var(--borde-radio-xl);
  padding:28px;
  text-align:center;
}

.logo{
  font-size:4rem;
}

h1{
  font-family:var(--fuente-display);
  margin-top:12px;
}

.version{
  color:var(--texto-terciario);
  font-size:var(--tamano-sm);
}

.descripcion{
  margin-top:20px;
  color:var(--texto-secundario);
  line-height:1.7;
}

.lista{
  margin-top:12px;
  background:var(--superficie-tarjeta);
  border:1px solid var(--borde-color);
  border-radius:var(--borde-radio-lg);
  overflow:hidden;
}

.item{
  padding:18px;
  border-bottom:1px solid var(--borde-color);
}

.item:last-child{
  border-bottom:none;
}
</style>