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

        <!-- Tarjeta principal -->
        <section class="animar-aparecer">
          <div class="hero-card">

            <div class="logo-container">
              <div class="logo">
                🐄
              </div>
            </div>

            <h1>BovWeight CR</h1>

            <div class="version-badge">
              Versión 1.0.0
            </div>

            <p class="descripcion">
              Plataforma inteligente para la gestión ganadera y la estimación del peso bovino mediante tecnologías de visión artificial y herramientas de administración orientadas al sector agropecuario.
            </p>

          </div>
        </section>

        <!-- Características -->
        <section class="animar-aparecer animar-delay-1">
          <span class="etiqueta-seccion">
            CARACTERÍSTICAS PRINCIPALES
          </span>

          <div class="lista">

            <div class="item">
              <div class="icono">📸</div>

              <div>
                <h3>Pesaje Inteligente</h3>
                <p>Estimación del peso mediante imágenes y visión artificial.</p>
              </div>
            </div>

            <div class="item">
              <div class="icono">🐄</div>

              <div>
                <h3>Gestión de Animales</h3>
                <p>Administración completa del ganado registrado.</p>
              </div>
            </div>

            <div class="item">
              <div class="icono">🏡</div>

              <div>
                <h3>Administración de Fincas</h3>
                <p>Control y organización de propiedades ganaderas.</p>
              </div>
            </div>

            <div class="item">
              <div class="icono">📊</div>

              <div>
                <h3>Reportes y Estadísticas</h3>
                <p>Visualización de información y métricas del negocio.</p>
              </div>
            </div>

            <div class="item">
              <div class="icono">☁️</div>

              <div>
                <h3>Sincronización</h3>
                <p>Actualización y respaldo seguro de los datos.</p>
              </div>
            </div>

          </div>
        </section>

        <!-- Información del sistema -->
        <section class="animar-aparecer animar-delay-2">

          <span class="etiqueta-seccion">
            INFORMACIÓN DEL SISTEMA
          </span>

          <div class="info-card">

            <div class="fila">
              <span>Versión</span>
              <strong>1.0.0</strong>
            </div>

            <div class="fila">
              <span>Plataforma</span>
              <strong>Vue + Ionic</strong>
            </div>

            <div class="fila">
              <span>Backend</span>
              <strong>Laravel</strong>
            </div>

            <div class="fila">
              <span>IA</span>
              <strong>Visión Artificial</strong>
            </div>

            <div class="fila">
              <span>País</span>
              <strong>Costa Rica</strong>
            </div>

          </div>

        </section>

        <!-- Pie -->
        <section class="animar-aparecer animar-delay-3">

          <div class="footer-card">

            <h3>BovWeight CR</h3>

            <p>
              Solución tecnológica orientada a la modernización y optimización
              de los procesos de gestión ganadera.
            </p>

            <div class="copyright">
              © 2026 BovWeight CR
            </div>

          </div>

        </section>

        <div style="height:40px"></div>

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
  gap:22px;
}

/* HERO */

.hero-card{
  background:var(--superficie-tarjeta);
  border:1px solid var(--borde-color);
  border-radius:var(--borde-radio-xl);
  padding:32px;
  text-align:center;
  box-shadow:var(--sombra-sm);
}

.logo-container{
  display:flex;
  justify-content:center;
}

.logo{
  width:100px;
  height:100px;
  border-radius:50%;
  background:linear-gradient(135deg,var(--primario),#4caf50);
  display:flex;
  align-items:center;
  justify-content:center;
  font-size:3rem;
  color:white;
  box-shadow:var(--sombra-md);
}

h1{
  font-family:var(--fuente-display);
  font-size:2rem;
  margin-top:20px;
  font-weight:700;
}

.version-badge{
  display:inline-block;
  margin-top:12px;
  background:rgba(76,175,80,.15);
  color:#4caf50;
  padding:8px 18px;
  border-radius:999px;
  font-size:var(--tamano-sm);
  font-weight:600;
}

.descripcion{
  margin-top:24px;
  color:var(--texto-secundario);
  line-height:1.8;
}

/* LISTA */

.lista{
  margin-top:12px;
  display:flex;
  flex-direction:column;
  gap:14px;
}

.item{
  background:var(--superficie-tarjeta);
  border:1px solid var(--borde-color);
  border-radius:var(--borde-radio-lg);
  padding:20px;
  display:flex;
  gap:18px;
  align-items:flex-start;
}

.icono{
  font-size:2rem;
}

.item h3{
  margin:0;
  font-size:var(--tamano-base);
  font-weight:700;
}

.item p{
  margin-top:6px;
  color:var(--texto-secundario);
  line-height:1.6;
}

/* INFO */

.info-card{
  background:var(--superficie-tarjeta);
  border:1px solid var(--borde-color);
  border-radius:var(--borde-radio-xl);
  overflow:hidden;
}

.fila{
  padding:18px 20px;
  display:flex;
  justify-content:space-between;
  border-bottom:1px solid var(--borde-color);
}

.fila:last-child{
  border-bottom:none;
}

/* FOOTER */

.footer-card{
  background:var(--superficie-tarjeta);
  border:1px solid var(--borde-color);
  border-radius:var(--borde-radio-xl);
  padding:28px;
  text-align:center;
}

.footer-card h3{
  font-family:var(--fuente-display);
}

.footer-card p{
  margin-top:14px;
  color:var(--texto-secundario);
  line-height:1.7;
}

.copyright{
  margin-top:22px;
  color:var(--texto-terciario);
  font-size:var(--tamano-xs);
}

</style>