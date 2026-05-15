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
          <h1 class="login-titulo">BovWeight <span>CR</span></h1>
          <p class="login-subtitulo">Inicie sesión para continuar</p>
        </div>

        <form class="login-formulario vidrio animar-aparecer animar-delay-1" @submit.prevent="manejarLogin">
          <div class="campo-grupo">
            <label class="campo-etiqueta" for="correo">Correo electrónico</label>
            <input id="correo" type="email" class="campo-entrada" :class="{ 'campo-entrada--error': errores.correo }"
              v-model="formulario.correo" placeholder="usuario@ejemplo.com" autocomplete="email" />
            <span v-if="errores.correo" class="campo-error">{{ errores.correo }}</span>
          </div>

          <div class="campo-grupo">
            <label class="campo-etiqueta" for="contrasena">Contraseña</label>
            <div style="position:relative">
              <input id="contrasena" :type="mostrarContrasena ? 'text' : 'password'" class="campo-entrada"
                :class="{ 'campo-entrada--error': errores.contrasena }" v-model="formulario.contrasena"
                placeholder="••••••••" autocomplete="current-password" />
              <button type="button" class="login-ojo" @click="mostrarContrasena = !mostrarContrasena">
                {{ mostrarContrasena ? '🙈' : '👁️' }}
              </button>
            </div>
            <span v-if="errores.contrasena" class="campo-error">{{ errores.contrasena }}</span>
          </div>

          <button type="button" class="login-olvido" @click="mostrarRecuperacion = true">
            ¿Olvidó su contraseña?
          </button>

          <button type="submit" class="boton boton--primario boton--completo boton--grande"
            :disabled="almacenAuth.cargando">
            <span v-if="almacenAuth.cargando" class="cargando-spinner" style="width:20px;height:20px;border-width:2px"></span>
            <span v-else>Iniciar Sesión</span>
          </button>

          <p v-if="almacenAuth.error" class="login-error-general">{{ almacenAuth.error }}</p>
        </form>

        <p class="login-registro animar-aparecer animar-delay-2">
          ¿No tiene cuenta? <a href="#" @click.prevent>Regístrese aquí</a>
        </p>

        <!-- Modal de recuperación -->
        <div v-if="mostrarRecuperacion" class="login-modal-fondo" @click.self="mostrarRecuperacion = false">
          <div class="login-modal vidrio">
            <h3>Recuperar Contraseña</h3>
            <p>Ingrese su correo para recibir un enlace de recuperación.</p>
            <input type="email" class="campo-entrada" v-model="correoRecuperacion" placeholder="su@correo.com"/>
            <button class="boton boton--primario boton--completo" @click="manejarRecuperacion" :disabled="almacenAuth.cargando">
              Enviar enlace
            </button>
            <button class="boton boton--fantasma boton--completo" @click="mostrarRecuperacion = false">Cancelar</button>
          </div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup>
/* Vista de Login con glassmorphism y validaciones */
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import { IonPage, IonContent } from '@ionic/vue';
import { useAlmacenAuth } from '@/stores/auth.js';

const router = useRouter();
const almacenAuth = useAlmacenAuth();

const formulario = reactive({ correo: '', contrasena: '' });
const errores = reactive({ correo: '', contrasena: '' });
const mostrarContrasena = ref(false);
const mostrarRecuperacion = ref(false);
const correoRecuperacion = ref('');

function validarFormulario() {
  let esValido = true;
  errores.correo = '';
  errores.contrasena = '';

  if (!formulario.correo) {
    errores.correo = 'El correo es obligatorio';
    esValido = false;
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formulario.correo)) {
    errores.correo = 'Ingrese un correo válido';
    esValido = false;
  }
  if (!formulario.contrasena) {
    errores.contrasena = 'La contraseña es obligatoria';
    esValido = false;
  } else if (formulario.contrasena.length < 4) {
    errores.contrasena = 'Mínimo 4 caracteres';
    esValido = false;
  }
  return esValido;
}

async function manejarLogin() {
  if (!validarFormulario()) return;
  const resultado = await almacenAuth.iniciarSesion(formulario);
  if (resultado.exito) {
    router.replace('/app/inicio');
  }
}

async function manejarRecuperacion() {
  if (!correoRecuperacion.value) return;
  await almacenAuth.recuperarContrasena(correoRecuperacion.value);
  mostrarRecuperacion.value = false;
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
.login-cabecera { text-align: center; }
.login-logo svg { width: 72px; height: 72px; margin-bottom: 16px; }
.login-titulo {
  font-family: var(--fuente-display); font-size: 2rem; font-weight: 800;
  color: var(--texto-primario);
}
.login-titulo span { color: var(--acento); }
.login-subtitulo { color: var(--texto-secundario); font-size: var(--tamano-sm); margin-top: 4px; }
.login-formulario {
  width: 100%; max-width: 380px; padding: 32px 24px;
  border-radius: var(--borde-radio-xl); display: flex; flex-direction: column; gap: 16px;
}
.login-ojo {
  position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
  background: none; border: none; cursor: pointer; font-size: 16px;
}
.login-olvido {
  background: none; border: none; color: var(--primario-medio);
  font-size: var(--tamano-sm); cursor: pointer; text-align: right;
  font-family: var(--fuente-cuerpo);
}
.login-olvido:hover { color: var(--primario); text-decoration: underline; }
.login-error-general {
  text-align: center; color: var(--peligro); font-size: var(--tamano-sm);
  background: var(--peligro-suave); padding: 8px; border-radius: var(--borde-radio-sm);
}
.login-registro { font-size: var(--tamano-sm); color: var(--texto-secundario); }
.login-registro a { color: var(--primario); font-weight: 600; text-decoration: none; }
.login-modal-fondo {
  position: fixed; inset: 0; background: rgba(0,0,0,0.4);
  display: flex; align-items: center; justify-content: center; z-index: 1000; padding: 24px;
}
.login-modal {
  width: 100%; max-width: 380px; padding: 32px 24px;
  border-radius: var(--borde-radio-xl); display: flex; flex-direction: column; gap: 16px;
}
.login-modal h3 { font-family: var(--fuente-display); font-size: var(--tamano-xl); font-weight: 700; }
.login-modal p { font-size: var(--tamano-sm); color: var(--texto-secundario); }
</style>
