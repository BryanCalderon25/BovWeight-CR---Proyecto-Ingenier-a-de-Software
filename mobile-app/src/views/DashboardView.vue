<template>
  <ion-page>
    <ion-header class="ion-no-border">
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-menu-button color="dark">
            <ion-icon :icon="menuOutline" />
          </ion-menu-button>
        </ion-buttons>
        <ion-title class="encabezado-titulo">
          <span class="encabezado-titulo__bov">Bov</span><span class="encabezado-titulo__weight">Weight</span>
          <span class="encabezado-titulo__cr">CR</span>
        </ion-title>
        <ion-buttons slot="end">
          <ion-button @click="irAConfiguracion">
            <ion-icon :icon="settingsOutline" />
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true">
      <div class="dashboard-contenido">
        <!-- Resumen del Panel -->
        <section class="dashboard-resumen animar-aparecer">
          <h2 class="titulo-seccion">Resumen del Panel</h2>
          <p class="dashboard-descripcion">
            Gestión de precisión para su hato. Realizando seguimiento en
            <strong>{{ almacenFincas.totalFincas }} fincas activas</strong>.
          </p>
        </section>

        <!-- Tarjeta Hato Total -->
        <div class="tarjeta tarjeta--metrica animar-aparecer animar-delay-1">
          <span class="etiqueta-seccion">HATO TOTAL</span>
          <div class="metrica-fila">
            <span class="metrica-grande">{{ almacenAnimales.totalAnimales }}</span>
          </div>
          <div class="insignia insignia--exito" style="margin-top:8px">
            <span>📈</span> +12 este mes
          </div>
        </div>

        <!-- Tarjeta Peso Promedio -->
        <div class="tarjeta tarjeta--metrica animar-aparecer animar-delay-2">
          <span class="etiqueta-seccion">PESO PROMEDIO</span>
          <div class="metrica-fila">
            <span class="metrica-grande">{{ almacenAnimales.pesoPromedio }}</span>
            <span class="metrica-unidad">kg</span>
          </div>
          <p class="dashboard-meta-info">Última actualización: 24 oct, 2023</p>
        </div>

        <!-- Tarjeta Último Registro -->
        <div class="tarjeta tarjeta--acento animar-aparecer animar-delay-3" @click="irAPesar">
          <span class="etiqueta-seccion" style="color:var(--acento-oscuro)">ÚLTIMO REGISTRO DE PESAJE</span>
          <div style="display:flex;align-items:center;gap:8px;margin-top:8px">
            <span style="font-size:20px">🐄</span>
            <div>
              <h3 style="font-family:var(--fuente-display);font-size:var(--tamano-xl);font-weight:700">
                Lote #082-A
              </h3>
              <p style="font-size:var(--tamano-xs);color:var(--acento-medio)">
                {{ ultimoPesaje?.pesoEstimado || 542 }} kg — {{ ultimoPesaje?.nombreAnimal || 'Luna' }}
              </p>
            </div>
          </div>
        </div>

        <!-- Animales Activos/Inactivos -->
        <div class="dashboard-grilla animar-aparecer animar-delay-4">
          <div class="tarjeta">
            <span class="etiqueta-seccion">ACTIVOS</span>
            <span class="metrica-grande" style="font-size:var(--tamano-2xl);color:var(--exito)">
              {{ almacenAnimales.animalesActivos }}
            </span>
          </div>
          <div class="tarjeta">
            <span class="etiqueta-seccion">INACTIVOS</span>
            <span class="metrica-grande" style="font-size:var(--tamano-2xl);color:var(--texto-terciario)">
              {{ almacenAnimales.animalesInactivos }}
            </span>
          </div>
        </div>

        <!-- Accesos rápidos -->
        <section class="animar-aparecer animar-delay-4" style="margin-top:8px">
          <span class="etiqueta-seccion">ACCIONES RÁPIDAS</span>
          <div class="dashboard-acciones">
            <button class="dashboard-accion-btn" @click="irAPesar">
              <span class="dashboard-accion-icono">📷</span>
              <span>Nuevo Pesaje</span>
            </button>
            <button class="dashboard-accion-btn" @click="irAAnimales">
              <span class="dashboard-accion-icono">🐮</span>
              <span>Ver Animales</span>
            </button>
            <button class="dashboard-accion-btn" @click="irAFincas">
              <span class="dashboard-accion-icono">🏡</span>
              <span>Mis Fincas</span>
            </button>
            <button class="dashboard-accion-btn" @click="irAReportes">
              <span class="dashboard-accion-icono">📄</span>
              <span>Reportes</span>
            </button>
          </div>
        </section>

        <!-- Actividad reciente -->
        <section class="animar-aparecer animar-delay-4" style="margin-top:8px">
          <span class="etiqueta-seccion">ACTIVIDAD RECIENTE</span>
          <div class="dashboard-actividad">
            <div v-for="pesaje in almacenPesajes.ultimosPesajes" :key="pesaje.id" class="actividad-item">
              <div class="actividad-icono">🐄</div>
              <div class="actividad-info">
                <strong>{{ pesaje.nombreAnimal }}</strong>
                <span>{{ pesaje.pesoEstimado }} kg — {{ pesaje.finca }}</span>
              </div>
              <span class="actividad-fecha">{{ pesaje.fecha }}</span>
            </div>
          </div>
        </section>

        <div style="height:32px"></div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup>
/* Dashboard principal — diseño basado en Stitch */
import { computed } from 'vue';
import { useRouter } from 'vue-router';
import {
  IonPage, IonHeader, IonToolbar, IonTitle, IonContent,
  IonButtons, IonButton, IonIcon, IonMenuButton
} from '@ionic/vue';
import { menuOutline, settingsOutline } from 'ionicons/icons';
import { useAlmacenAnimales } from '@/stores/animales.js';
import { useAlmacenFincas } from '@/stores/fincas.js';
import { useAlmacenPesajes } from '@/stores/pesajes.js';

const router = useRouter();
const almacenAnimales = useAlmacenAnimales();
const almacenFincas = useAlmacenFincas();
const almacenPesajes = useAlmacenPesajes();

const ultimoPesaje = computed(() => almacenPesajes.ultimosPesajes[0]);

function irAPesar() { router.push('/app/pesar'); }
function irAAnimales() { router.push('/app/animales'); }
function irAFincas() { router.push('/app/fincas'); }
function irAReportes() { router.push('/app/reportes'); }
function irAConfiguracion() { router.push('/app/configuracion'); }
</script>

<style scoped>
.encabezado-titulo {
  font-family: var(--fuente-display); font-weight: 800; font-size: 1.2rem;
}
.encabezado-titulo__bov { color: var(--primario-medio); }
.encabezado-titulo__weight { color: var(--texto-primario); }
.encabezado-titulo__cr { color: var(--acento); font-size: 0.9rem; }

.dashboard-contenido {
  padding: 0 20px; display: flex; flex-direction: column; gap: 16px;
}
.dashboard-resumen { margin-bottom: 4px; }
.dashboard-descripcion {
  font-size: var(--tamano-sm); color: var(--texto-secundario); margin-top: 4px;
}
.metrica-fila { display: flex; align-items: baseline; margin-top: 8px; }
.dashboard-meta-info {
  font-size: var(--tamano-xs); color: var(--texto-terciario); margin-top: 8px;
}
.dashboard-grilla { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; }
.dashboard-acciones {
  display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 12px;
}
.dashboard-accion-btn {
  display: flex; flex-direction: column; align-items: center; justify-content: center;
  gap: 8px; padding: 20px 12px; background: var(--superficie-tarjeta);
  border: 1px solid var(--borde-color); border-radius: var(--borde-radio-lg);
  cursor: pointer; font-family: var(--fuente-cuerpo); font-size: var(--tamano-sm);
  font-weight: 500; color: var(--texto-primario); transition: all var(--transicion-normal);
}
.dashboard-accion-btn:hover { transform: translateY(-2px); box-shadow: var(--sombra-md); }
.dashboard-accion-btn:active { transform: scale(0.97); }
.dashboard-accion-icono { font-size: 28px; }
.dashboard-actividad {
  display: flex; flex-direction: column; gap: 0; margin-top: 12px;
  background: var(--superficie-tarjeta); border-radius: var(--borde-radio-lg);
  overflow: hidden; border: 1px solid var(--borde-color);
}
.actividad-item {
  display: flex; align-items: center; gap: 12px; padding: 14px 16px;
  border-bottom: 1px solid var(--borde-color); transition: background var(--transicion-rapida);
}
.actividad-item:last-child { border-bottom: none; }
.actividad-item:hover { background: var(--superficie-elevada); }
.actividad-icono { font-size: 24px; }
.actividad-info {
  flex: 1; display: flex; flex-direction: column;
  font-size: var(--tamano-sm); color: var(--texto-primario);
}
.actividad-info span { font-size: var(--tamano-xs); color: var(--texto-terciario); }
.actividad-fecha { font-size: var(--tamano-xs); color: var(--texto-terciario); white-space: nowrap; }
</style>
