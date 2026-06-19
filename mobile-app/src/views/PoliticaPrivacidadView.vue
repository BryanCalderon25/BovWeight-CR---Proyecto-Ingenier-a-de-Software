<template>
  <ion-page>
    <ion-header class="ion-no-border">
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button default-href="/app/configuracion" text="" />
        </ion-buttons>

        <ion-title>Política de Privacidad</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true">

      <div class="contenido">

        <!-- Encabezado -->
        <section class="animar-aparecer">
          <div class="hero">
            <div class="hero-icono">
              🔒
            </div>

            <h1>Privacidad y Protección de Datos</h1>

            <p class="hero-subtitulo">
              En BovWeight CR nos comprometemos a proteger la información de nuestros usuarios y garantizar la confidencialidad de los datos almacenados.
            </p>

            <div class="estado-seguridad">
              <span>✓ Información protegida</span>
            </div>
          </div>
        </section>

        <!-- Información recopilada -->
        <section class="animar-aparecer animar-delay-1">
          <span class="etiqueta-seccion">
            INFORMACIÓN RECOPILADA
          </span>

          <div class="tarjeta">
            <div class="fila">
              <div class="icono">👤</div>

              <div>
                <h3>Datos del usuario</h3>
                <p>
                  Se almacenan únicamente los datos necesarios para el funcionamiento de la plataforma y la gestión ganadera.
                </p>
              </div>
            </div>

            <div class="fila">
              <div class="icono">🐄</div>

              <div>
                <h3>Información del hato</h3>
                <p>
                  Los registros de animales, fincas y pesajes son utilizados exclusivamente para brindar las funcionalidades de BovWeight CR.
                </p>
              </div>
            </div>
          </div>
        </section>

        <!-- Uso de la información -->
        <section class="animar-aparecer animar-delay-2">
          <span class="etiqueta-seccion">
            USO DE LA INFORMACIÓN
          </span>

          <div class="tarjeta">

            <div class="fila">
              <div class="icono">📊</div>

              <div>
                <h3>Procesamiento interno</h3>
                <p>
                  La información recopilada es utilizada para generar reportes, administrar registros y proporcionar las herramientas de la aplicación.
                </p>
              </div>
            </div>

            <div class="fila">
              <div class="icono">☁️</div>

              <div>
                <h3>Sincronización de datos</h3>
                <p>
                  Los datos pueden sincronizarse con los servicios asociados para mantener la información actualizada.
                </p>
              </div>
            </div>

          </div>
        </section>

        <!-- Confidencialidad -->
        <section class="animar-aparecer animar-delay-3">
          <span class="etiqueta-seccion">
            CONFIDENCIALIDAD
          </span>

          <div class="tarjeta">

            <div class="fila">
              <div class="icono">🛡️</div>

              <div>
                <h3>Protección de datos</h3>
                <p>
                  No se comparte información personal con terceros sin autorización del usuario, salvo cuando exista una obligación legal.
                </p>
              </div>
            </div>

            <div class="fila">
              <div class="icono">✏️</div>

              <div>
                <h3>Actualización y eliminación</h3>
                <p>
                  El usuario puede solicitar la actualización o eliminación de sus datos cuando corresponda.
                </p>
              </div>
            </div>

          </div>
        </section>

        <!-- Pie -->
        <section class="animar-aparecer animar-delay-4">
          <div class="tarjeta-final">
            <div class="pie-icono">
              🔐
            </div>

            <h3>Compromiso con la privacidad</h3>

            <p>
              BovWeight CR trabaja continuamente para mantener la seguridad y confidencialidad de la información registrada por sus usuarios.
            </p>

            <div class="version">
              Política de privacidad · Versión 1.0.0
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
  background:linear-gradient(135deg,#1e293b,#334155);
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
  color:rgba(255,255,255,.85);
}

.estado-seguridad{
  margin-top:20px;
  display:inline-flex;
  padding:10px 18px;
  border-radius:999px;
  background:rgba(255,255,255,.15);
}

/* SECCIONES */

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
  background:rgba(var(--ion-color-primary-rgb),0.12);
  display:flex;
  align-items:center;
  justify-content:center;
  font-size:1.4rem;
  flex-shrink:0;
}

.fila h3{
  font-family:var(--fuente-display);
  font-size:1rem;
  font-weight:700;
  margin-bottom:8px;
}

.fila p{
  color:var(--texto-secundario);
  line-height:1.8;
}

/* TARJETA FINAL */

.tarjeta-final{
  text-align:center;
  padding:32px;
  background:var(--superficie-tarjeta);
  border:1px solid var(--borde-color);
  border-radius:var(--borde-radio-xl);
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
  color:var(--texto-terciario);
  font-size:var(--tamano-xs);
}

</style>