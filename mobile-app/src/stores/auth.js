/* === Almacén de Autenticación === */
import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '@/services/api';

export const useAlmacenAuth = defineStore('auth', () => {
  /* Estado */
  let usuarioInicial = null;
  try {
    const almacenado = localStorage.getItem('bw_usuario');
    if (almacenado && almacenado !== 'undefined' && almacenado !== 'null') {
      usuarioInicial = JSON.parse(almacenado);
    }
  } catch (e) {
    console.error('Error al inicializar usuario desde localStorage:', e);
    localStorage.removeItem('bw_usuario');
  }

  const usuario = ref(usuarioInicial);
  const token = ref(localStorage.getItem('bw_token') || '');
  const cargando = ref(false);
  const error = ref('');

  /* Getters */
  const estaAutenticado = computed(() => !!token.value && !!usuario.value);
  const nombreCompleto = computed(() => usuario.value ? usuario.value.name : '');
  const rolUsuario = computed(() => usuario.value?.rol || usuario.value?.role || 'ganadero');

  /* Acciones */
  async function iniciarSesion(credenciales) {
    cargando.value = true;
    error.value = '';
    try {
      const respuesta = await api.post('/login', {
        email: credenciales.correo,
        password: credenciales.contrasena
      });
      
      const { datos, token_acceso } = respuesta.data;

      usuario.value = datos;
      token.value = token_acceso;
      localStorage.setItem('bw_usuario', JSON.stringify(datos));
      localStorage.setItem('bw_token', token_acceso);
      
      return { exito: true };
    } catch (err) {
      if (!err.response) {
        error.value = 'No se pudo conectar con el servidor';
      } else if (err.response.status === 401 || err.response.status === 404) {
        error.value = 'Credenciales incorrectas';
      } else {
        error.value = err.response?.data?.mensaje || 'Error al iniciar sesión';
      }
      return { exito: false, error: error.value };
    } finally {
      cargando.value = false;
    }
  }

  async function registrarse(datosRegistro) {
    cargando.value = true;
    error.value = '';
    try {
      const respuesta = await api.post('/registro', {
        name: datosRegistro.nombre,
        email: datosRegistro.correo,
        password: datosRegistro.contrasena,
        password_confirmation: datosRegistro.confirmarContrasena
      });

      const { datos, token_acceso } = respuesta.data;

      if (token_acceso && datos) {
        usuario.value = datos;
        token.value = token_acceso;
        localStorage.setItem('bw_usuario', JSON.stringify(datos));
        localStorage.setItem('bw_token', token_acceso);
        return { exito: true, autologin: true };
      } else {
        return { exito: true, autologin: false, mensaje: respuesta.data?.mensaje || 'Usuario registrado exitosamente' };
      }
    } catch (err) {
      if (!err.response) {
        error.value = 'No se pudo conectar con el servidor';
      } else if (err.response.status === 422) {
        const validationErrors = err.response.data?.errors;
        if (validationErrors) {
          error.value = Object.values(validationErrors).flat().join(' ');
        } else {
          error.value = err.response.data?.mensaje || 'Datos de registro inválidos';
        }
      } else {
        error.value = err.response?.data?.mensaje || 'Error en el registro';
      }
      return { exito: false, error: error.value };
    } finally {
      cargando.value = false;
    }
  }

  function iniciarSesionInvitado(datosInvitacion) {
    const { token: tokenInvitado, usuario: usuarioInvitado } = datosInvitacion;
    usuario.value = usuarioInvitado;
    token.value = tokenInvitado;
    localStorage.setItem('bw_usuario', JSON.stringify(usuarioInvitado));
    localStorage.setItem('bw_token', tokenInvitado);
  }

  async function cerrarSesion() {
    const tokenActual = token.value;

    usuario.value = null;
    token.value = '';
    localStorage.removeItem('bw_usuario');
    localStorage.removeItem('bw_token');
    sessionStorage.removeItem('bw_usuario');
    sessionStorage.removeItem('bw_token');

    if (tokenActual) {
      try {
        await api.post('/logout', {}, {
          headers: {
            Authorization: `Bearer ${tokenActual}`
          }
        });
      } catch (err) {
        console.error('Error al cerrar sesión en el servidor', err);
      }
    }
  }

  async function obtenerPerfil() {
    try {
      const respuesta = await api.get('/perfil');
      usuario.value = respuesta.data.datos;
      localStorage.setItem('bw_usuario', JSON.stringify(respuesta.data.datos));
    } catch (err) {
      console.error('Error al obtener perfil', err);
    }
  }

  async function solicitarRecuperacionPassword(email) {
    cargando.value = true;
    error.value = '';
    try {
      const respuesta = await api.post('/forgot-password', { email });
      return { exito: true, mensaje: respuesta.data?.mensaje || respuesta.data?.message };
    } catch (err) {
      if (!err.response) {
        error.value = 'No se pudo conectar con el servidor.';
      } else {
        error.value = err.response.data?.mensaje || err.response.data?.message || 'Error al solicitar la recuperación.';
      }
      return { exito: false, error: error.value };
    } finally {
      cargando.value = false;
    }
  }

  async function restablecerPassword(datos) {
    cargando.value = true;
    error.value = '';
    try {
      const respuesta = await api.post('/reset-password', {
        email: datos.email,
        token: datos.token,
        password: datos.password,
        password_confirmation: datos.password_confirmation
      });
      return { exito: true, mensaje: respuesta.data?.mensaje || respuesta.data?.message };
    } catch (err) {
      if (!err.response) {
        error.value = 'No se pudo conectar con el servidor.';
      } else if (err.response.status === 422) {
        error.value = err.response.data?.mensaje || err.response.data?.message || 'La contraseña debe tener mínimo 8 caracteres, una mayúscula, una minúscula y un número.';
      } else if (err.response.status === 400) {
        error.value = err.response.data?.mensaje || err.response.data?.message || 'El enlace venció o no es válido.';
      } else {
        error.value = err.response.data?.mensaje || err.response.data?.message || 'Error al restablecer la contraseña.';
      }
      return { exito: false, error: error.value };
    } finally {
      cargando.value = false;
    }
  }

  return {
    usuario, token, cargando, error,
    estaAutenticado, nombreCompleto, rolUsuario,
    iniciarSesion, registrarse, cerrarSesion, obtenerPerfil, iniciarSesionInvitado,
    solicitarRecuperacionPassword, restablecerPassword
  };
});
