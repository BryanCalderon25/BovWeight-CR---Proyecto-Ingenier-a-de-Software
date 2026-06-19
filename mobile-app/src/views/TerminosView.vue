<template>
  <ion-page>
    <ion-header class="ion-no-border">
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button default-href="/app/configuracion" text="" />
        </ion-buttons>

        <ion-title>Términos y Condiciones</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true">

      <div class="contenido">

        <!-- Encabezado -->
        <section class="animar-aparecer">
          <div class="hero">
            <div class="hero-icono">
              📋
            </div>

            <h1>Términos y Condiciones</h1>

            <p class="hero-subtitulo">
              El uso de BovWeight CR implica la aceptación de las condiciones establecidas para el manejo responsable de la plataforma y la información registrada.
            </p>

            <div class="estado">
              <span>✓ Uso responsable de la plataforma</span>
            </div>
          </div>
        </section>

        <!-- Uso de la aplicación -->
        <section class="animar-aparecer animar-delay-1">
          <span class="etiqueta-seccion">
            USO DE LA APLICACIÓN
          </span>

          <div class="tarjeta">

            <div class="fila">
              <div class="icono">🐄</div>

              <div>
                <h3>Propósito de BovWeight CR</h3>

                <p>
                  La aplicación está destinada a la administración, seguimiento y control de información relacionada con la actividad ganadera.
                </p>
              </div>
            </div>

            <div class="fila">
              <div class="icono">✅</div>

              <div>
                <h3>Aceptación de los términos</h3>

                <p>
                  El uso de la plataforma implica la aceptación de las condiciones y responsabilidades establecidas para sus usuarios.
                </p>
              </div>
            </div>

          </div>
        </section>

        <!-- Responsabilidad del usuario -->
        <section class="animar-aparecer animar-delay-2">
          <span class="etiqueta-seccion">
            RESPONSABILIDAD DEL USUARIO
          </span>

          <div class="tarjeta">

            <div class="fila">
              <div class="icono">👤</div>

              <div>
                <h3>Información registrada</h3>

                <p>
                  El usuario es responsable de la exactitud y actualización de la información almacenada dentro de la plataforma.
                </p>
              </div>
            </div>

            <div class="fila">
              <div class="icono">📝</div>

              <div>
                <h3>Uso adecuado</h3>

                <p>
                  Los datos gestionados deberán utilizarse únicamente con fines lícitos y relacionados con las actividades agropecuarias.
                </p>
              </div>
            </div>

          </div>
        </section>

        <!-- Buenas prácticas -->
        <section class="animar-aparecer animar-delay-3">
          <span class="etiqueta-seccion">
            BUENAS PRÁCTICAS
          </span>

          <div class="tarjeta">

            <div class="fila">
              <div class="icono">🔐</div>

              <div>
                <h3>Seguridad de la cuenta</h3>

                <p>
                  El usuario debe mantener la confidencialidad de sus credenciales y evitar compartirlas con terceros.
                </p>
              </div>
            </div>

            <div class="fila">
              <div class="icono">⚖️</div>

              <div>
                <h3>Cumplimiento normativo</h3>

                <p>
                  La utilización de la plataforma deberá respetar las disposiciones legales aplicables y las buenas prácticas del sector ganadero.
                </p>
              </div>
            </div>

          </div>
        </section>

        <!-- Tarjeta final -->
        <section class="animar-aparecer animar-delay-4">
          <div class="tarjeta-final">

            <div class="pie-icono">
              🤝
            </div>

            <h3>Compromiso de uso responsable</h3>

            <p>
              BovWeight CR busca proporcionar una herramienta segura y confiable para apoyar la gestión ganadera, promoviendo un uso adecuado y responsable de la información.
            </p>

            <div class="version">
              Términos y Condiciones · Versión 1.0.0
            </div>

          </div>
        </section>

        <div style="height:30px"></div>

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

/* HERO */

.hero{
  background:linear-gradient(135deg,#92400e,#d97706);
  color:white;
  padding:32px;
  border-radius:var(--borde-radio-xl);
  text-align:center;
}

.hero-icono{
  font-size:4rem;
}

.hero h1{
  margin-top:14px;
  font-family:var(--fuente-display);
  font-size:1.9rem;
  font-weight:800;
}

.hero-subtitulo{
  margin-top:18px;
  line-height:1.8;
  color:rgba(255,255,255,.9);
}

.estado{
  margin-top:20px;
  display:inline-flex;
  padding:10px 18px;
  border-radius:999px;
  background:rgba(255,255,255,.15);
}

/* TARJETAS */

.tarjeta{
  margin-top:12px;
  background:var(--superficie-tarjeta);
  border:1px solid var(--borde-color);
  border-radius:var(--borde-radio-xl);
  overflow:hidden;
}

.fila{
  display:flex;
  gap:18px;
  padding:22px;
  border-bottom:1px solid var(--borde-color);
}

.fila:last-child{
  border-bottom:none;
}

.icono{
  width:54px;
  height:54px;
  border-radius:50%;
  background:rgba(var(--ion-color-warning-rgb),0.12);
  display:flex;
  align-items:center;
  justify-content:center;
  font-size:1.4rem;
  flex-shrink:0;
}

.fila h3{
  margin-bottom:8px;
  font-family:var(--fuente-display);
  font-weight:700;
  font-size:1rem;
}

.fila p{
  color:var(--texto-secundario);
  line-height:1.8;
}

/* TARJETA FINAL */

.tarjeta-final{
  background:var(--superficie-tarjeta);
  border:1px solid var(--borde-color);
  border-radius:var(--borde-radio-xl);
  text-align:center;
  padding:32px;
}

.pie-icono{
  font-size:3rem;
}

.tarjeta-final h3{
  margin-top:14px;
  font-family:var(--fuente-display);
  font-weight:800;
}

.tarjeta-final p{
  margin-top:16px;
  color:var(--texto-secundario);
  line-height:1.8;
}

.version{
  margin-top:22px;
  font-size:var(--tamano-xs);
  color:var(--texto-terciario);
}

</style>