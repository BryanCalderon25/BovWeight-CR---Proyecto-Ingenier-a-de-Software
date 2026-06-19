<template>
  <ion-page>
    <ion-content :fullscreen="true" class="login-contenido">
      <div class="login-fondo">
        <div class="login-fondo__circulo login-fondo__circulo--1"></div>
        <div class="login-fondo__circulo login-fondo__circulo--2"></div>
      </div>
      <div class="login-envoltorio">
        <div class="login-cabecera animar-aparecer">
          <div class="login-logo">
            <svg viewBox="0 0 60 60" fill="none" xmlns="http://www.w3.org/2000/svg">
              <circle cx="30" cy="30" r="28" fill="#414833" stroke="#C2C5AA" stroke-width="2"/>
              <path d="M18 38 Q22 22 30 20 Q38 22 42 38" stroke="#C2C5AA" stroke-width="2.5" fill="none" stroke-linecap="round"/>
              <circle cx="25" cy="28" r="2.5" fill="#C2C5AA"/>
              <circle cx="35" cy="28" r="2.5" fill="#C2C5AA"/>
            </svg>
          </div>
          
          <template v-if="paso === 1">
            <h1 class="login-titulo">¿Olvidó su contraseña?</h1>
            <p class="login-subtitulo">Ingrese el correo con el que creó su cuenta. Le enviaremos un código de recuperación de 6 dígitos.</p>
          </template>
          <template v-else>
            <h1 class="login-titulo">Verificación</h1>
            <p class="login-subtitulo">Hemos enviado un código de recuperación a su correo.</p>
          </template>
        </div>
 
        <form v-if="paso === 1" class="login-formulario vidrio animar-aparecer animar-delay-1" @submit.prevent="manejarEnvio">
          <div class="campo-grupo">
            <label class="campo-etiqueta" for="correo">Correo electrónico</label>
            <input id="correo" type="email" class="campo-entrada" :class="{ 'campo-entrada--error': errorCorreo }"
              v-model="correo" placeholder="usuario@ejemplo.com" autocomplete="email" />
            <span v-if="errorCorreo" class="campo-error">{{ errorCorreo }}</span>
          </div>
 
          <button type="submit" class="boton boton--primario boton--completo boton--grande"
            :disabled="almacenAuth.cargando">
            <span v-if="almacenAuth.cargando" class="cargando-spinner" style="width:20px;height:20px;border-width:2px"></span>
            <span v-else>Enviar código</span>
          </button>
 
          <p v-if="almacenAuth.error" class="login-error-general">{{ almacenAuth.error }}</p>
          <p v-if="mensajeExito" class="login-exito-general">{{ mensajeExito }}</p>
        </form>

        <form v-else class="login-formulario vidrio animar-aparecer animar-delay-1" @submit.prevent="verificarCodigo">
          <div class="campo-grupo">
            <label class="campo-etiqueta" for="codigo">Código de 6 dígitos</label>
            <input id="codigo" type="text" class="campo-entrada" :class="{ 'campo-entrada--error': errorCodigo }"
              v-model="codigo" placeholder="123456" maxlength="6" autocomplete="off" />
            <span v-if="errorCodigo" class="campo-error">{{ errorCodigo }}</span>
          </div>
 
          <button type="submit" class="boton boton--primario boton--completo boton--grande"
            :disabled="almacenAuth.cargando">
            <span v-if="almacenAuth.cargando" class="cargando-spinner" style="width:20px;height:20px;border-width:2px"></span>
            <span v-else>Verificar código</span>
          </button>

          <button type="button" class="boton boton--secundario boton--completo" style="margin-top: 10px;"
            :disabled="almacenAuth.cargando" @click="reenviarCodigo">
            <span v-if="almacenAuth.cargando" class="cargando-spinner" style="width:20px;height:20px;border-width:2px"></span>
            <span v-else>Reenviar código</span>
          </button>
 
          <p v-if="almacenAuth.error" class="login-error-general">{{ almacenAuth.error }}</p>
          <p v-if="mensajeExito" class="login-exito-general">{{ mensajeExito }}</p>
        </form>
 
        <p class="login-registro animar-aparecer animar-delay-2">
          <a href="#" @click.prevent="volverAtras">{{ paso === 1 ? 'Volver al inicio de sesión' : 'Usar otro correo' }}</a>
        </p>
      </div>
    </ion-content>
  </ion-page>
</template>
 
<script setup>
import { ref } from 'vue';
import { useRouter } from 'vue-router';
import { IonPage, IonContent } from '@ionic/vue';
import { useAlmacenAuth } from '@/stores/auth.js';
 
const router = useRouter();
const almacenAuth = useAlmacenAuth();
 
const paso = ref(1);
const correo = ref('');
const codigo = ref('');
const errorCorreo = ref('');
const errorCodigo = ref('');
const mensajeExito = ref('');
 
function validarPaso1() {
  let esValido = true;
  errorCorreo.value = '';
  mensajeExito.value = '';
  almacenAuth.error = '';
 
  if (!correo.value) {
    errorCorreo.value = 'Ingrese su correo electrónico.';
    esValido = false;
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(correo.value)) {
    errorCorreo.value = 'Ingrese un correo electrónico válido.';
    esValido = false;
  }
  return esValido;
}

function validarPaso2() {
  let esValido = true;
  errorCodigo.value = '';
  mensajeExito.value = '';
  almacenAuth.error = '';
 
  if (!codigo.value) {
    errorCodigo.value = 'Ingrese el código.';
    esValido = false;
  } else if (!/^\d{6}$/.test(codigo.value)) {
    errorCodigo.value = 'El código debe tener exactamente 6 dígitos.';
    esValido = false;
  }
  return esValido;
}
 
async function manejarEnvio() {
  if (!validarPaso1()) return;
 
  const resultado = await almacenAuth.solicitarRecuperacionPassword(correo.value);
  if (resultado.exito) {
    mensajeExito.value = resultado.mensaje;
    paso.value = 2; // Avanzar a pantalla de verificación
  }
}

async function reenviarCodigo() {
  if (!validarPaso1()) return;
  const resultado = await almacenAuth.solicitarRecuperacionPassword(correo.value);
  if (resultado.exito) {
    mensajeExito.value = 'Nuevo código enviado exitosamente.';
    codigo.value = '';
  }
}

async function verificarCodigo() {
  if (!validarPaso2()) return;
 
  const resultado = await almacenAuth.verificarCodigoRecuperacion({
    email: correo.value,
    token: codigo.value
  });
  
  if (resultado.exito) {
    // Si es válido, redirigir a Pantalla 3
    router.push({
      path: '/restablecer-contrasena',
      query: { email: correo.value, token: codigo.value }
    });
  }
}

function volverAtras() {
  if (paso.value === 2) {
    paso.value = 1;
    codigo.value = '';
    mensajeExito.value = '';
    almacenAuth.error = '';
  } else {
    router.push('/login');
  }
}
</script>
 
<style scoped>
.login-contenido { --background: var(--superficie); }
.login-fondo { position: fixed; inset: 0; overflow: hidden; pointer-events: none; }
.login-fondo__circulo {
  position: absolute; border-radius: 50%; opacity: 0.15;
  background: var(--primario);
}
.login-fondo__circulo--1 { width: 400px; height: 400px; top: -100px; right: -100px; }
.login-fondo__circulo--2 { width: 300px; height: 300px; bottom: -50px; left: -80px; background: var(--acento); }
.login-envoltorio {
  display: flex; flex-direction: column; align-items: center;
  justify-content: center; min-height: 100%; padding: 32px 24px; gap: 24px;
  position: relative; z-index: 1;
}
.login-cabecera { text-align: center; max-width: 420px; }
.login-logo svg { width: 72px; height: 72px; margin-bottom: 16px; }
.login-titulo {
  font-family: var(--fuente-display); font-size: 1.75rem; font-weight: 800;
  color: var(--texto-primario);
}
.login-subtitulo { color: var(--texto-secundario); font-size: var(--tamano-sm); margin-top: 8px; line-height: 1.5; }
.login-formulario {
  width: 100%; max-width: 380px; padding: 32px 24px;
  border-radius: var(--borde-radio-xl); display: flex; flex-direction: column; gap: 16px;
}
.login-error-general {
  text-align: center; color: var(--peligro); font-size: var(--tamano-sm);
  background: var(--peligro-suave); padding: 8px; border-radius: var(--borde-radio-sm);
}
.login-exito-general {
  text-align: center; color: #2E7D32; font-size: var(--tamano-sm);
  background: #E8F5E9; padding: 12px; border-radius: var(--borde-radio-sm);
  line-height: 1.4;
}
.login-registro { font-size: var(--tamano-sm); color: var(--texto-secundario); }
.login-registro a { color: var(--primario); font-weight: 600; text-decoration: none; }
</style>
