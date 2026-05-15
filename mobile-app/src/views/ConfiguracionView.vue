<template>
  <ion-page>
    <ion-header class="ion-no-border">
      <ion-toolbar>
        <ion-buttons slot="start"><ion-back-button default-href="/app/inicio" text="" /></ion-buttons>
        <ion-title>Configuración</ion-title>
      </ion-toolbar>
    </ion-header>
    <ion-content :fullscreen="true">
      <div class="config-contenido">
        <!-- Perfil -->
        <div class="config-perfil animar-aparecer">
          <div class="config-avatar">{{ almacenAuth.usuario?.nombre?.charAt(0) || 'U' }}</div>
          <div>
            <h3 style="font-family:var(--fuente-display);font-weight:700">{{ almacenAuth.nombreCompleto || 'Usuario' }}</h3>
            <p style="font-size:var(--tamano-xs);color:var(--texto-terciario)">{{ almacenAuth.usuario?.correo || '' }}</p>
            <span class="insignia insignia--primario" style="margin-top:4px">{{ almacenAuth.rolUsuario }}</span>
          </div>
        </div>

        <!-- Opciones -->
        <section class="animar-aparecer animar-delay-1">
          <span class="etiqueta-seccion">GENERAL</span>
          <div class="config-lista">
            <div class="config-item">
              <span>🌙</span><span>Modo Oscuro</span>
              <label class="config-switch">
                <input type="checkbox" v-model="modoOscuro" @change="alternarModoOscuro" />
                <span class="config-switch__slider"></span>
              </label>
            </div>
            <div class="config-item" @click="irAFincas">
              <span>🏡</span><span>Mis Fincas</span><span class="config-flecha">›</span>
            </div>
            <div class="config-item" @click="irAReportes">
              <span>📄</span><span>Reportes</span><span class="config-flecha">›</span>
            </div>
          </div>
        </section>

        <section class="animar-aparecer animar-delay-2">
          <span class="etiqueta-seccion">DATOS Y SINCRONIZACIÓN</span>
          <div class="config-lista">
            <div class="config-item">
              <span>📶</span><span>Estado de sincronización</span>
              <span class="insignia insignia--exito">Sincronizado</span>
            </div>
            <div class="config-item">
              <span>💾</span><span>Datos locales</span>
              <span style="font-size:var(--tamano-xs);color:var(--texto-terciario)">2.4 MB</span>
            </div>
          </div>
        </section>

        <section class="animar-aparecer animar-delay-3">
          <span class="etiqueta-seccion">INFORMACIÓN</span>
          <div class="config-lista">
            <div class="config-item"><span>ℹ️</span><span>Acerca de BovWeight CR</span><span class="config-flecha">›</span></div>
            <div class="config-item"><span>📋</span><span>Términos y Condiciones</span><span class="config-flecha">›</span></div>
            <div class="config-item"><span>🔒</span><span>Política de Privacidad</span><span class="config-flecha">›</span></div>
          </div>
        </section>

        <button class="boton boton--secundario boton--completo animar-aparecer animar-delay-4" 
          style="color:var(--peligro);margin-top:16px" @click="cerrarSesion">
          🚪 Cerrar Sesión
        </button>

        <p class="config-version">BovWeight CR v1.0.0</p>
        <div style="height:32px"></div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup>
/* Vista de Configuración */
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonBackButton } from '@ionic/vue';
import { useAlmacenAuth } from '@/stores/auth.js';

const router = useRouter();
const almacenAuth = useAlmacenAuth();
const modoOscuro = ref(document.documentElement.getAttribute('data-tema') === 'oscuro');

function alternarModoOscuro() {
  document.documentElement.setAttribute('data-tema', modoOscuro.value ? 'oscuro' : '');
  localStorage.setItem('bw_tema', modoOscuro.value ? 'oscuro' : 'claro');
}

function irAFincas() { router.push('/app/fincas'); }
function irAReportes() { router.push('/app/reportes'); }

function cerrarSesion() {
  almacenAuth.cerrarSesion();
  router.replace('/login');
}
</script>

<style scoped>
.config-contenido{padding:0 20px;display:flex;flex-direction:column;gap:20px}
.config-perfil{display:flex;align-items:center;gap:16px;padding:24px;background:var(--superficie-tarjeta);border-radius:var(--borde-radio-xl);border:1px solid var(--borde-color)}
.config-avatar{width:60px;height:60px;border-radius:50%;background:var(--primario);display:flex;align-items:center;justify-content:center;font-family:var(--fuente-display);font-weight:800;font-size:1.5rem;color:#fff;flex-shrink:0}
.config-lista{display:flex;flex-direction:column;gap:2px;margin-top:12px;background:var(--superficie-tarjeta);border-radius:var(--borde-radio-lg);overflow:hidden;border:1px solid var(--borde-color)}
.config-item{display:flex;align-items:center;gap:12px;padding:16px;cursor:pointer;transition:background var(--transicion-rapida);font-size:var(--tamano-sm);border-bottom:1px solid var(--borde-color)}
.config-item:last-child{border-bottom:none}
.config-item:hover{background:var(--superficie-elevada)}
.config-item span:nth-child(2){flex:1}
.config-flecha{color:var(--texto-terciario);font-size:var(--tamano-xl)}
.config-switch{position:relative;width:48px;height:28px;display:inline-block}
.config-switch input{opacity:0;width:0;height:0}
.config-switch__slider{position:absolute;inset:0;background:var(--superficie-hundida);border-radius:14px;cursor:pointer;transition:all 0.3s}
.config-switch__slider::before{content:'';position:absolute;width:22px;height:22px;border-radius:50%;background:#fff;left:3px;top:3px;transition:transform 0.3s;box-shadow:0 1px 3px rgba(0,0,0,0.2)}
.config-switch input:checked + .config-switch__slider{background:var(--primario)}
.config-switch input:checked + .config-switch__slider::before{transform:translateX(20px)}
.config-version{text-align:center;font-size:var(--tamano-xs);color:var(--texto-terciario);margin-top:16px}
</style>
