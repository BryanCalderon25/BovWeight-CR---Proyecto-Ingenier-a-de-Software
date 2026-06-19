<template>
  <ion-page>
    <ion-header class="ion-no-border">
      <ion-toolbar>
        <ion-buttons slot="start"><ion-back-button default-href="/app/inicio" text="" /></ion-buttons>
        <ion-title>Configuración</ion-title>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true">
      <div class="cfg-contenido">

        <!-- ── TARJETA DE PERFIL (ENFOQUE GANADERO) ─────────── -->
        <div class="cfg-perfil animar-aparecer">
          <!-- Avatar simple con las iniciales y color institucional -->
          <div class="cfg-avatar-wrap">
            <div class="cfg-avatar">
              {{ iniciales }}
            </div>
          </div>

          <!-- Info y Estadísticas Útiles para el Ganadero -->
          <div class="cfg-perfil-info">
            <h2 class="cfg-nombre">{{ almacenAuth.nombreCompleto || 'Usuario' }}</h2>
            <p class="cfg-correo">{{ almacenAuth.usuario?.email || '' }}</p>
            <div class="cfg-meta-row" style="margin-bottom: 8px">
              <span class="insignia insignia--primario">{{ almacenAuth.rolUsuario }}</span>
            </div>

            <!-- Grilla de Estadísticas de Negocio -->
            <div class="cfg-stats-grid">
              <div class="cfg-stat-item">
                <span class="cfg-stat-val">{{ almacenFincas.totalFincas }}</span>
                <span class="cfg-stat-lbl">Fincas</span>
              </div>
              <div class="cfg-stat-item">
                <span class="cfg-stat-val">{{ animalesActivosHato }}</span>
                <span class="cfg-stat-lbl">Ganado Activo</span>
              </div>
              <div class="cfg-stat-item">
                <span class="cfg-stat-val">{{ fechaCreacionShort || '...' }}</span>
                <span class="cfg-stat-lbl">Registro</span>
              </div>
            </div>
          </div>
        </div>

        <!-- ── SECCIÓN: MIS DATOS ─────────────────────────── -->
        <section class="animar-aparecer animar-delay-1">
          <span class="etiqueta-seccion">MIS DATOS</span>
          <p style="font-size:var(--tamano-xs);color:var(--texto-secundario);margin-top:4px;margin-bottom:8px">
            Aquí puede revisar y actualizar sus datos de cuenta.
          </p>
          <div class="cfg-lista">

            <!-- Ítem: Editar Nombre -->
            <div class="cfg-item cfg-item--expandible" @click="toggleSeccion('nombre')">
              <span>👤</span>
              <span class="cfg-item-label">Editar nombre</span>
              <span class="cfg-flecha" :class="{ 'cfg-flecha--abierto': seccionAbierta === 'nombre' }">›</span>
            </div>
            <!-- Panel expandible: Editar Nombre -->
            <transition name="expandir">
              <div v-if="seccionAbierta === 'nombre'" class="cfg-panel">
                <div class="campo-grupo">
                  <label class="campo-etiqueta" for="nuevo-nombre">Nombre completo</label>
                  <input
                    id="nuevo-nombre"
                    type="text"
                    class="campo-entrada"
                    :class="{ 'campo-entrada--error': erroresNombre }"
                    v-model="formNombre.name"
                    placeholder="Ingrese su nombre completo"
                    maxlength="255"
                    autocomplete="name"
                  />
                  <span v-if="erroresNombre" class="campo-error">{{ erroresNombre }}</span>
                </div>
                <div class="cfg-panel-acciones">
                  <button class="boton boton--secundario" @click="cancelarEdicion">Cancelar</button>
                  <button class="boton boton--primario" :disabled="guardandoNombre" @click="guardarNombre">
                    <span v-if="guardandoNombre" class="cargando-spinner" style="width:16px;height:16px;border-width:2px"></span>
                    <span v-else>Guardar</span>
                  </button>
                </div>
              </div>
            </transition>

            <!-- Ítem: Cambiar contraseña -->
            <div class="cfg-item cfg-item--expandible" @click="toggleSeccion('password')">
              <span>🔐</span>
              <span class="cfg-item-label">Cambiar contraseña</span>
              <span class="cfg-flecha" :class="{ 'cfg-flecha--abierto': seccionAbierta === 'password' }">›</span>
            </div>
            <!-- Panel expandible: Cambiar contraseña -->
            <transition name="expandir">
              <div v-if="seccionAbierta === 'password'" class="cfg-panel">
                <!-- Contraseña actual -->
                <div class="campo-grupo">
                  <label class="campo-etiqueta" for="pwd-actual">Contraseña actual</label>
                  <div style="position:relative">
                    <input
                      id="pwd-actual"
                      :type="verActual ? 'text' : 'password'"
                      class="campo-entrada"
                      :class="{ 'campo-entrada--error': erroresPwd.actual }"
                      v-model="formPwd.actual"
                      placeholder="••••••••"
                      autocomplete="current-password"
                    />
                    <button type="button" class="cfg-ojo" @click="verActual = !verActual">
                      {{ verActual ? '🙈' : '👁️' }}
                    </button>
                  </div>
                  <span v-if="erroresPwd.actual" class="campo-error">{{ erroresPwd.actual }}</span>
                </div>

                <!-- Nueva contraseña -->
                <div class="campo-grupo">
                  <label class="campo-etiqueta" for="pwd-nueva">Nueva contraseña</label>
                  <div style="position:relative">
                    <input
                      id="pwd-nueva"
                      :type="verNueva ? 'text' : 'password'"
                      class="campo-entrada"
                      :class="{ 'campo-entrada--error': erroresPwd.nueva }"
                      v-model="formPwd.nueva"
                      placeholder="Mínimo 8 caracteres"
                      autocomplete="new-password"
                    />
                    <button type="button" class="cfg-ojo" @click="verNueva = !verNueva">
                      {{ verNueva ? '🙈' : '👁️' }}
                    </button>
                  </div>
                  <span v-if="erroresPwd.nueva" class="campo-error">{{ erroresPwd.nueva }}</span>
                </div>

                <!-- Confirmar contraseña -->
                <div class="campo-grupo">
                  <label class="campo-etiqueta" for="pwd-confirmar">Confirmar nueva contraseña</label>
                  <div style="position:relative">
                    <input
                      id="pwd-confirmar"
                      :type="verConfirmar ? 'text' : 'password'"
                      class="campo-entrada"
                      :class="{ 'campo-entrada--error': erroresPwd.confirmar }"
                      v-model="formPwd.confirmar"
                      placeholder="Repita la nueva contraseña"
                      autocomplete="new-password"
                    />
                    <button type="button" class="cfg-ojo" @click="verConfirmar = !verConfirmar">
                      {{ verConfirmar ? '🙈' : '👁️' }}
                    </button>
                  </div>
                  <span v-if="erroresPwd.confirmar" class="campo-error">{{ erroresPwd.confirmar }}</span>
                </div>

                <p class="cfg-nota-pwd">
                  La contraseña debe tener al menos 8 caracteres, una letra mayúscula, una minúscula y un número.
                </p>

                <div class="cfg-panel-acciones">
                  <button class="boton boton--secundario" @click="cancelarEdicion">Cancelar</button>
                  <button class="boton boton--primario" :disabled="guardandoPwd" @click="guardarPassword">
                    <span v-if="guardandoPwd" class="cargando-spinner" style="width:16px;height:16px;border-width:2px"></span>
                    <span v-else>Actualizar</span>
                  </button>
                </div>
              </div>
            </transition>

          </div>
        </section>

        <!-- ── SECCIÓN: ACCESOS RÁPIDOS ───────────────────── -->
        <section class="animar-aparecer animar-delay-2">
          <span class="etiqueta-seccion">MI FINCA</span>
          <div class="cfg-lista">
            <div class="cfg-item" @click="irAFincas">
              <span>🏡</span><span class="cfg-item-label">Mis Fincas</span><span class="cfg-flecha">›</span>
            </div>
            <div class="cfg-item" @click="irAReportes">
              <span>📄</span><span class="cfg-item-label">Reportes</span><span class="cfg-flecha">›</span>
            </div>
          </div>
        </section>

        <!-- ── SECCIÓN: GENERAL ───────────────────────────── -->
        <section class="animar-aparecer animar-delay-3">
          <span class="etiqueta-seccion">GENERAL</span>
          <div class="cfg-lista">
            <div class="cfg-item">
              <span>🌙</span>
              <span class="cfg-item-label">Modo Oscuro</span>
              <label class="config-switch">
                <input type="checkbox" v-model="modoOscuro" @change="alternarModoOscuro" />
                <span class="config-switch__slider"></span>
              </label>
            </div>
            <div class="cfg-item">
              <span>📶</span>
              <span class="cfg-item-label">Estado de sincronización</span>
              <span class="insignia insignia--exito">Sincronizado</span>
            </div>
          </div>
        </section>

        <!-- ── SECCIÓN: INFORMACIÓN ───────────────────────── -->
        <section class="animar-aparecer animar-delay-3">
          <span class="etiqueta-seccion">INFORMACIÓN</span>
<<<<<<< Updated upstream
          <div class="cfg-lista">
            <div class="cfg-item"><span>ℹ️</span><span class="cfg-item-label">Acerca de BovWeight CR</span><span class="cfg-flecha">›</span></div>
            <div class="cfg-item"><span>📋</span><span class="cfg-item-label">Términos y Condiciones</span><span class="cfg-flecha">›</span></div>
            <div class="cfg-item"><span>🔒</span><span class="cfg-item-label">Política de Privacidad</span><span class="cfg-flecha">›</span></div>
=======
          <div class="config-lista">
            <div class="config-item" @click="irAAcercaDe">
  <span>ℹ️</span>
  <span>Acerca de BovWeight CR</span>
  <span class="config-flecha">›</span>
</div>

<div class="config-item" @click="irATerminos">
  <span>📋</span>
  <span>Términos y Condiciones</span>
  <span class="config-flecha">›</span>
</div>

<div class="config-item" @click="irAPoliticaPrivacidad">
  <span>🔒</span>
  <span>Política de Privacidad</span>
  <span class="config-flecha">›</span>
</div>
>>>>>>> Stashed changes
          </div>
        </section>

        <!-- ── CERRAR SESIÓN ──────────────────────────────── -->
        <button
          class="boton boton--secundario boton--completo animar-aparecer animar-delay-4"
          style="color:var(--peligro);margin-top:8px"
          @click="cerrarSesion"
        >
          🚪 Cerrar Sesión
        </button>

        <p class="cfg-version">BovWeight CR v1.0.0</p>
        <div style="height:40px"></div>
      </div>

      <!-- ── TOASTS ─────────────────────────────────────── -->
      <div v-if="toast.visible" :class="['toast', `toast--${toast.tipo}`]">
        {{ toast.mensaje }}
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup>
/* Vista de Configuración — BWCR-53 */
import { ref, reactive, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import {
  IonPage, IonHeader, IonToolbar, IonTitle, IonContent,
  IonButtons, IonBackButton
} from '@ionic/vue';
import { useAlmacenAuth } from '@/stores/auth.js';
import { useAlmacenFincas } from '@/stores/fincas.js';
import { useAlmacenAnimales } from '@/stores/animales.js';
import api from '@/services/api';

const router        = useRouter();
const almacenAuth   = useAlmacenAuth();
const almacenFincas = useAlmacenFincas();
const almacenAnimales = useAlmacenAnimales();
const modoOscuro    = ref(document.documentElement.getAttribute('data-tema') === 'oscuro');

// ── Estado Local para Estadísticas de Negocio ───────────
const animalesActivosHato = ref(0);

// ── Avatar ──────────────────────────────────────────────
const iniciales = computed(() => {
  const nombre = almacenAuth.nombreCompleto || '';
  const partes  = nombre.trim().split(' ').filter(Boolean);
  if (partes.length >= 2) return (partes[0][0] + partes[1][0]).toUpperCase();
  return nombre.charAt(0).toUpperCase() || 'U';
});

const fechaCreacionShort = computed(() => {
  const raw = almacenAuth.usuario?.created_at;
  if (!raw) return '';
  try {
    const date = new Date(raw);
    return date.toLocaleDateString('es-CR', { month: 'short', year: 'numeric' });
  } catch {
    return '';
  }
});

// ── Secciones expandibles ────────────────────────────────
const seccionAbierta = ref(null);

function toggleSeccion(nombre) {
  seccionAbierta.value = seccionAbierta.value === nombre ? null : nombre;
  // Limpiar formularios al cerrar
  if (seccionAbierta.value !== 'nombre') resetFormNombre();
  if (seccionAbierta.value !== 'password') resetFormPwd();
}

function cancelarEdicion() {
  seccionAbierta.value = null;
  resetFormNombre();
  resetFormPwd();
}

// ── Formulario: Editar nombre ────────────────────────────
const formNombre      = reactive({ name: '' });
const erroresNombre   = ref('');
const guardandoNombre = ref(false);

function resetFormNombre() {
  formNombre.name   = almacenAuth.nombreCompleto || '';
  erroresNombre.value = '';
}

onMounted(async () => {
  resetFormNombre();

  // Cargar fincas
  await almacenFincas.cargarFincas();
  
  // Calcular total de ganado activo sumando los animales de cada finca
  let tempActivos = 0;
  for (const finca of almacenFincas.lista) {
    try {
      const respuesta = await api.get(`/fincas/${finca.id}/animales`);
      const animales = respuesta.data.datos || [];
      tempActivos += animales.filter(a => a.estado === 'activo' || !a.estado).length;
    } catch (err) {
      console.error(`Error al calcular ganado activo de la finca ${finca.id} para el perfil:`, err);
    }
  }
  animalesActivosHato.value = tempActivos;
});

function validarNombre() {
  erroresNombre.value = '';
  const nombre = formNombre.name.trim();
  if (!nombre) {
    erroresNombre.value = 'El nombre es requerido.';
    return false;
  }
  if (nombre.length < 2) {
    erroresNombre.value = 'El nombre debe tener al menos 2 caracteres.';
    return false;
  }
  if (nombre.length > 255) {
    erroresNombre.value = 'El nombre no puede superar 255 caracteres.';
    return false;
  }
  return true;
}

async function guardarNombre() {
  if (!validarNombre()) return;
  guardandoNombre.value = true;
  const resultado = await almacenAuth.actualizarPerfil({ name: formNombre.name.trim() });
  guardandoNombre.value = false;
  if (resultado.exito) {
    mostrarToast('Perfil actualizado correctamente.', 'exito');
    seccionAbierta.value = null;
  } else {
    erroresNombre.value = resultado.error || 'Error al actualizar el perfil.';
  }
}

// ── Formulario: Cambiar contraseña ────────────────────────
const formPwd       = reactive({ actual: '', nueva: '', confirmar: '' });
const erroresPwd    = reactive({ actual: '', nueva: '', confirmar: '' });
const guardandoPwd  = ref(false);
const verActual     = ref(false);
const verNueva      = ref(false);
const verConfirmar  = ref(false);

function resetFormPwd() {
  formPwd.actual   = '';
  formPwd.nueva    = '';
  formPwd.confirmar = '';
  erroresPwd.actual   = '';
  erroresPwd.nueva    = '';
  erroresPwd.confirmar = '';
  verActual.value    = false;
  verNueva.value     = false;
  verConfirmar.value = false;
}

function validarPassword() {
  erroresPwd.actual   = '';
  erroresPwd.nueva    = '';
  erroresPwd.confirmar = '';
  let valido = true;

  if (!formPwd.actual) {
    erroresPwd.actual = 'Ingrese su contraseña actual.';
    valido = false;
  }
  if (!formPwd.nueva) {
    erroresPwd.nueva = 'Ingrese la nueva contraseña.';
    valido = false;
  } else if (formPwd.nueva.length < 8) {
    erroresPwd.nueva = 'La contraseña debe tener mínimo 8 caracteres.';
    valido = false;
  } else if (!/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).{8,}$/.test(formPwd.nueva)) {
    erroresPwd.nueva = 'Debe incluir mayúscula, minúscula y un número.';
    valido = false;
  }
  if (!formPwd.confirmar) {
    erroresPwd.confirmar = 'Confirme la nueva contraseña.';
    valido = false;
  } else if (formPwd.nueva !== formPwd.confirmar) {
    erroresPwd.confirmar = 'Las contraseñas no coinciden.';
    valido = false;
  }
  return valido;
}

async function guardarPassword() {
  if (!validarPassword()) return;
  guardandoPwd.value = true;
  const resultado = await almacenAuth.cambiarPassword({
    passwordActual:      formPwd.actual,
    passwordNuevo:       formPwd.nueva,
    passwordConfirmacion: formPwd.confirmar
  });
  guardandoPwd.value = false;

  if (resultado.exito) {
    mostrarToast('Contraseña actualizada correctamente.', 'exito');
    resetFormPwd();
    seccionAbierta.value = null;
  } else {
    const msg = resultado.error || '';
    if (msg.toLowerCase().includes('actual')) {
      erroresPwd.actual = msg;
    } else if (msg.toLowerCase().includes('coinciden')) {
      erroresPwd.confirmar = msg;
    } else {
      erroresPwd.nueva = msg;
    }
  }
}

// ── Modo oscuro ──────────────────────────────────────────
function alternarModoOscuro() {
  document.documentElement.setAttribute('data-tema', modoOscuro.value ? 'oscuro' : '');
  localStorage.setItem('bw_tema', modoOscuro.value ? 'oscuro' : 'claro');
}

// ── Navegación ───────────────────────────────────────────
function irAFincas()   { router.push('/app/fincas'); }
function irAReportes() { router.push('/app/reportes'); }

<<<<<<< Updated upstream
async function cerrarSesion() {
  await almacenAuth.cerrarSesion();
=======
function irAAcercaDe() {
  router.push('/app/acerca-de');
}

function irATerminos() {
  router.push('/app/terminos');
}

function irAPoliticaPrivacidad() {
  router.push('/app/politica-privacidad');
}

function cerrarSesion() {
  almacenAuth.cerrarSesion();
>>>>>>> Stashed changes
  router.replace('/login');
}

// ── Toasts ───────────────────────────────────────────────
const toast = reactive({ visible: false, mensaje: '', tipo: 'exito' });
let toastTimer = null;

function mostrarToast(mensaje, tipo = 'exito') {
  if (toastTimer) clearTimeout(toastTimer);
  toast.mensaje  = mensaje;
  toast.tipo     = tipo;
  toast.visible  = true;
  toastTimer = setTimeout(() => { toast.visible = false; }, 3500);
}
</script>

<style scoped>
/* ── Contenedor principal ──────────────────────────────── */
.cfg-contenido {
  padding: 0 20px;
  display: flex;
  flex-direction: column;
  gap: 20px;
}

/* ── Tarjeta de perfil ─────────────────────────────────── */
.cfg-perfil {
  display: flex;
  align-items: center;
  gap: 16px;
  padding: 16px 20px;
  background: var(--superficie-tarjeta);
  border-radius: var(--borde-radio-xl);
  border: 1px solid var(--borde-color);
}
.cfg-avatar-wrap {
  display: flex;
  flex-direction: column;
  align-items: center;
  flex-shrink: 0;
}
.cfg-avatar {
  width: 54px;
  height: 54px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--fuente-display);
  font-weight: 800;
  font-size: 1.3rem;
  color: #fff;
  background: var(--primario); /* Color institucional de BovWeight CR */
}

/* ── Info del perfil ───────────────────────────────────── */
.cfg-perfil-info { flex: 1; min-width: 0; }
.cfg-nombre {
  font-family: var(--fuente-display);
  font-weight: 700;
  font-size: var(--tamano-lg);
  margin: 0 0 2px 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.cfg-correo {
  font-size: var(--tamano-xs);
  color: var(--texto-terciario);
  margin: 0 0 8px 0;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}
.cfg-meta-row {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-wrap: wrap;
}

/* ── Grilla de Estadísticas de Negocio ────────────────── */
.cfg-stats-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
  margin-top: 8px;
  padding-top: 8px;
  border-top: 1px solid var(--borde-color);
}
.cfg-stat-item {
  display: flex;
  flex-direction: column;
  align-items: flex-start;
}
.cfg-stat-val {
  font-family: var(--fuente-display);
  font-size: var(--tamano-base);
  font-weight: 700;
  color: var(--texto-primario);
}
.cfg-stat-lbl {
  font-size: 9px;
  color: var(--texto-terciario);
  text-transform: uppercase;
  letter-spacing: 0.5px;
  margin-top: 2px;
}

/* ── Lista de configuración ────────────────────────────── */
.cfg-lista {
  display: flex;
  flex-direction: column;
  gap: 0;
  margin-top: 10px;
  background: var(--superficie-tarjeta);
  border-radius: var(--borde-radio-lg);
  overflow: hidden;
  border: 1px solid var(--borde-color);
}
.cfg-item {
  display: flex;
  align-items: center;
  gap: 12px;
  padding: 16px;
  cursor: pointer;
  transition: background var(--transicion-rapida);
  font-size: var(--tamano-sm);
  border-bottom: 1px solid var(--borde-color);
}
.cfg-item:last-child { border-bottom: none; }
.cfg-item:hover { background: var(--superficie-elevada); }
.cfg-item--expandible { user-select: none; }
.cfg-item-label { flex: 1; }
.cfg-flecha {
  color: var(--texto-terciario);
  font-size: 1.3rem;
  transition: transform 0.22s;
}
.cfg-flecha--abierto { transform: rotate(90deg); }

/* ── Panel expandible ──────────────────────────────────── */
.cfg-panel {
  padding: 16px;
  background: var(--superficie);
  border-top: 1px solid var(--borde-color);
  display: flex;
  flex-direction: column;
  gap: 14px;
}
.cfg-panel-acciones {
  display: flex;
  gap: 10px;
  justify-content: flex-end;
}
.cfg-panel-acciones .boton { min-width: 100px; }

.cfg-nota-pwd {
  font-size: var(--tamano-xs);
  color: var(--texto-terciario);
  margin: 0;
  line-height: 1.4;
}

/* Botón ojo en los campos de contraseña */
.cfg-ojo {
  position: absolute;
  right: 12px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  cursor: pointer;
  font-size: 15px;
  padding: 0;
}

/* ── Switch modo oscuro ────────────────────────────────── */
.config-switch { position: relative; width: 48px; height: 28px; display: inline-block; }
.config-switch input { opacity: 0; width: 0; height: 0; }
.config-switch__slider {
  position: absolute;
  inset: 0;
  background: var(--superficie-hundida);
  border-radius: 14px;
  cursor: pointer;
  transition: all 0.3s;
}
.config-switch__slider::before {
  content: '';
  position: absolute;
  width: 22px;
  height: 22px;
  border-radius: 50%;
  background: #fff;
  left: 3px;
  top: 3px;
  transition: transform 0.3s;
  box-shadow: 0 1px 3px rgba(0,0,0,0.2);
}
.config-switch input:checked + .config-switch__slider { background: var(--primario); }
.config-switch input:checked + .config-switch__slider::before { transform: translateX(20px); }

/* ── Notas y versión ───────────────────────────────────── */
.cfg-version {
  text-align: center;
  font-size: var(--tamano-xs);
  color: var(--texto-terciario);
  margin: 0;
}

/* ── Animación expandir ────────────────────────────────── */
.expandir-enter-active,
.expandir-leave-active {
  transition: max-height 0.28s ease, opacity 0.22s ease;
  max-height: 600px;
  overflow: hidden;
}
.expandir-enter-from,
.expandir-leave-to {
  max-height: 0;
  opacity: 0;
}
</style>
