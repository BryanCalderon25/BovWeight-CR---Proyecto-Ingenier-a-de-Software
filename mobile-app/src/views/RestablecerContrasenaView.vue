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
          <h1 class="login-titulo">Nueva contraseña</h1>
          <p class="login-subtitulo">Ingrese su nueva contraseña. Debe usar mínimo 8 caracteres, incluir mayúscula, minúscula y un número.</p>
        </div>

        <form class="login-formulario vidrio animar-aparecer animar-delay-1" @submit.prevent="manejarRestablecer">
          <div class="campo-grupo">
            <label class="campo-etiqueta" for="password">Nueva contraseña</label>
            <div style="position:relative">
              <input id="password" :type="mostrarPassword ? 'text' : 'password'" class="campo-entrada"
                :class="{ 'campo-entrada--error': errores.password }" v-model="formulario.password"
                placeholder="••••••••" autocomplete="new-password" />
              <button type="button" class="login-ojo" @click="mostrarPassword = !mostrarPassword">
                {{ mostrarPassword ? '🙈' : '👁️' }}
              </button>
            </div>
            <span v-if="errores.password" class="campo-error">{{ errores.password }}</span>
          </div>

          <div class="campo-grupo">
            <label class="campo-etiqueta" for="password_confirmation">Confirmar contraseña</label>
            <div style="position:relative">
              <input id="password_confirmation" :type="mostrarConfirmarPassword ? 'text' : 'password'" class="campo-entrada"
                :class="{ 'campo-entrada--error': errores.password_confirmation }" v-model="formulario.password_confirmation"
                placeholder="••••••••" autocomplete="new-password" />
              <button type="button" class="login-ojo" @click="mostrarConfirmarPassword = !mostrarConfirmarPassword">
                {{ mostrarConfirmarPassword ? '🙈' : '👁️' }}
              </button>
            </div>
            <span v-if="errores.password_confirmation" class="campo-error">{{ errores.password_confirmation }}</span>
          </div>

          <button type="submit" class="boton boton--primario boton--completo boton--grande"
            :disabled="almacenAuth.cargando">
            <span v-if="almacenAuth.cargando" class="cargando-spinner" style="width:20px;height:20px;border-width:2px"></span>
            <span v-else>Actualizar contraseña</span>
          </button>

          <p v-if="almacenAuth.error" class="login-error-general">{{ almacenAuth.error }}</p>
        </form>

        <p class="login-registro animar-aparecer animar-delay-2">
          <a href="#" @click.prevent="router.push('/login')">Cancelar y volver al login</a>
        </p>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup>
import { ref, reactive, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { IonPage, IonContent } from '@ionic/vue';
import { useAlmacenAuth } from '@/stores/auth.js';

const router = useRouter();
const route = useRoute();
const almacenAuth = useAlmacenAuth();

const formulario = reactive({
  email: '',
  token: '',
  password: '',
  password_confirmation: ''
});

const errores = reactive({
  password: '',
  password_confirmation: ''
});

const mostrarPassword = ref(false);
const mostrarConfirmarPassword = ref(false);

onMounted(() => {
  formulario.email = route.query.email || '';
  formulario.token = route.query.token || '';
});

function validarFormulario() {
  let esValido = true;
  errores.password = '';
  errores.password_confirmation = '';

  if (!formulario.password) {
    errores.password = 'Ingrese una contraseña.';
    esValido = false;
  } else if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/.test(formulario.password)) {
    errores.password = 'La contraseña debe tener mínimo 8 caracteres, una mayúscula, una minúscula y un número.';
    esValido = false;
  }

  if (!formulario.password_confirmation) {
    errores.password_confirmation = 'Confirme su contraseña.';
    esValido = false;
  } else if (formulario.password !== formulario.password_confirmation) {
    errores.password_confirmation = 'Las contraseñas no coinciden.';
    esValido = false;
  }

  if (!formulario.email || !formulario.token) {
    almacenAuth.error = 'Falta el código de verificación o el correo.';
    esValido = false;
  }

  return esValido;
}

async function manejarRestablecer() {
  if (!validarFormulario()) return;

  const resultado = await almacenAuth.restablecerPassword(formulario);
  if (resultado.exito) {
    router.replace({ path: '/login', query: { restablecido: 'true' } });
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
.login-ojo {
  position: absolute; right: 12px; top: 50%; transform: translateY(-50%);
  background: none; border: none; cursor: pointer; font-size: 16px;
}
.login-error-general {
  text-align: center; color: var(--peligro); font-size: var(--tamano-sm);
  background: var(--peligro-suave); padding: 8px; border-radius: var(--borde-radio-sm);
}
.login-registro { font-size: var(--tamano-sm); color: var(--texto-secundario); }
.login-registro a { color: var(--primario); font-weight: 600; text-decoration: none; }
</style>
