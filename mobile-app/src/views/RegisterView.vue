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
          <p class="login-subtitulo">Cree su cuenta para continuar</p>
        </div>

        <form class="login-formulario vidrio animar-aparecer animar-delay-1" @submit.prevent="manejarRegistro">
          <div class="campo-grupo">
            <label class="campo-etiqueta" for="nombre">Nombre completo</label>
            <input id="nombre" type="text" class="campo-entrada" :class="{ 'campo-entrada--error': errores.nombre }"
              v-model="formulario.nombre" placeholder="Nombre completo" autocomplete="name" />
            <span v-if="errores.nombre" class="campo-error">{{ errores.nombre }}</span>
          </div>

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
                placeholder="••••••••" autocomplete="new-password" />
              <button type="button" class="login-ojo" @click="mostrarContrasena = !mostrarContrasena">
                {{ mostrarContrasena ? '🙈' : '👁️' }}
              </button>
            </div>
            <span v-if="errores.contrasena" class="campo-error">{{ errores.contrasena }}</span>
          </div>

          <div class="campo-grupo">
            <label class="campo-etiqueta" for="confirmarContrasena">Confirmar contraseña</label>
            <div style="position:relative">
              <input id="confirmarContrasena" :type="mostrarConfirmarContrasena ? 'text' : 'password'" class="campo-entrada"
                :class="{ 'campo-entrada--error': errores.confirmarContrasena }" v-model="formulario.confirmarContrasena"
                placeholder="••••••••" autocomplete="new-password" />
              <button type="button" class="login-ojo" @click="mostrarConfirmarContrasena = !mostrarConfirmarContrasena">
                {{ mostrarConfirmarContrasena ? '🙈' : '👁️' }}
              </button>
            </div>
            <span v-if="errores.confirmarContrasena" class="campo-error">{{ errores.confirmarContrasena }}</span>
          </div>

          <button type="submit" class="boton boton--primario boton--completo boton--grande"
            :disabled="almacenAuth.cargando">
            <span v-if="almacenAuth.cargando" class="cargando-spinner" style="width:20px;height:20px;border-width:2px"></span>
            <span v-else>Registrarse</span>
          </button>

          <p v-if="almacenAuth.error" class="login-error-general">{{ almacenAuth.error }}</p>
        </form>

        <p class="login-registro animar-aparecer animar-delay-2">
          ¿Ya tiene cuenta? <a href="#" @click.prevent="router.push('/login')">Inicie sesión aquí</a>
        </p>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup>
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import { IonPage, IonContent } from '@ionic/vue';
import { useAlmacenAuth } from '@/stores/auth.js';

const router = useRouter();
const almacenAuth = useAlmacenAuth();

const formulario = reactive({
  nombre: '',
  correo: '',
  contrasena: '',
  confirmarContrasena: ''
});

const errores = reactive({
  nombre: '',
  correo: '',
  contrasena: '',
  confirmarContrasena: ''
});

const mostrarContrasena = ref(false);
const mostrarConfirmarContrasena = ref(false);

function validarFormulario() {
  let esValido = true;
  errores.nombre = '';
  errores.correo = '';
  errores.contrasena = '';
  errores.confirmarContrasena = '';

  if (!formulario.nombre.trim()) {
    errores.nombre = 'Ingrese su nombre completo';
    esValido = false;
  }

  if (!formulario.correo) {
    errores.correo = 'Ingrese su correo electrónico';
    esValido = false;
  } else if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(formulario.correo)) {
    errores.correo = 'Ingrese un correo válido';
    esValido = false;
  }

  if (!formulario.contrasena) {
    errores.contrasena = 'Ingrese una contraseña';
    esValido = false;
  } else if (formulario.contrasena.length < 8) {
    errores.contrasena = 'La contraseña debe tener al menos 8 caracteres';
    esValido = false;
  } else if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/.test(formulario.contrasena)) {
    errores.contrasena = 'La contraseña debe incluir al menos una letra mayúscula, una minúscula y un número';
    esValido = false;
  }

  if (!formulario.confirmarContrasena) {
    errores.confirmarContrasena = 'Confirme su contraseña';
    esValido = false;
  } else if (formulario.contrasena !== formulario.confirmarContrasena) {
    errores.confirmarContrasena = 'Las contraseñas no coinciden';
    esValido = false;
  }

  return esValido;
}

async function manejarRegistro() {
  if (!validarFormulario()) return;

  const resultado = await almacenAuth.registrarse(formulario);
  if (resultado.exito) {
    if (resultado.autologin) {
      router.replace({ path: '/app/inicio', query: { bienvenido: 'true' } });
    } else {
      router.replace({ path: '/login', query: { registrado: 'true' } });
    }
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
.login-error-general {
  text-align: center; color: var(--peligro); font-size: var(--tamano-sm);
  background: var(--peligro-suave); padding: 8px; border-radius: var(--borde-radio-sm);
}
.login-registro { font-size: var(--tamano-sm); color: var(--texto-secundario); }
.login-registro a { color: var(--primario); font-weight: 600; text-decoration: none; }
</style>
