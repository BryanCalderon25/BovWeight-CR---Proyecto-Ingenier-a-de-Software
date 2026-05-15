/* === Almacén de Autenticación === */
import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

export const useAlmacenAuth = defineStore('auth', () => {
  /* Estado */
  const usuario = ref(JSON.parse(localStorage.getItem('bw_usuario') || 'null'));
  const token = ref(localStorage.getItem('bw_token') || '');
  const cargando = ref(false);
  const error = ref('');

  /* Getters */
  const estaAutenticado = computed(() => !!token.value && !!usuario.value);
  const nombreCompleto = computed(() => usuario.value ? `${usuario.value.nombre} ${usuario.value.apellido}` : '');
  const rolUsuario = computed(() => usuario.value?.rol || 'ganadero');

  /* Acciones */
  async function iniciarSesion(credenciales) {
    cargando.value = true;
    error.value = '';
    try {
      /* Simulación de login - conectar con API real */
      await new Promise(r => setTimeout(r, 1200));
      
      const usuarioDemo = {
        id: 1,
        nombre: credenciales.correo.split('@')[0],
        apellido: 'García',
        correo: credenciales.correo,
        rol: 'administrador',
        avatar: null,
        fincas: 3
      };
      const tokenDemo = 'bw_token_' + Date.now();

      usuario.value = usuarioDemo;
      token.value = tokenDemo;
      localStorage.setItem('bw_usuario', JSON.stringify(usuarioDemo));
      localStorage.setItem('bw_token', tokenDemo);
      
      return { exito: true };
    } catch (err) {
      error.value = 'Credenciales incorrectas. Intente nuevamente.';
      return { exito: false, error: error.value };
    } finally {
      cargando.value = false;
    }
  }

  function cerrarSesion() {
    usuario.value = null;
    token.value = '';
    localStorage.removeItem('bw_usuario');
    localStorage.removeItem('bw_token');
  }

  async function recuperarContrasena(correo) {
    cargando.value = true;
    try {
      await new Promise(r => setTimeout(r, 1000));
      return { exito: true, mensaje: 'Se envió un enlace de recuperación a su correo.' };
    } finally {
      cargando.value = false;
    }
  }

  return {
    usuario, token, cargando, error,
    estaAutenticado, nombreCompleto, rolUsuario,
    iniciarSesion, cerrarSesion, recuperarContrasena
  };
});
