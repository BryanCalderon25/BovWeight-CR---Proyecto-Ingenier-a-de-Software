<template>
  <ion-page>
    <ion-header class="ion-no-border">
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button default-href="/app/configuracion" text="" />
        </ion-buttons>
        <ion-title>Administración</ion-title>
        <ion-buttons slot="end">
          <ion-button @click="abrirAgregar">
            <ion-icon :icon="addOutline" />
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true">
      <div class="admin-contenido">
        
        <!-- Cabecera -->
        <section class="animar-aparecer">
          <span class="etiqueta-seccion">CONTROL GLOBAL</span>
          <h2 class="titulo-seccion">Panel de Administración</h2>
        </section>

        <!-- Segmentos -->
        <div class="segment-wrap animar-aparecer animar-delay-1">
          <button 
            :class="['segment-btn', { active: segmentoActivo === 'fincas' }]" 
            @click="cambiarSegmento('fincas')"
          >
            🏡 Fincas
          </button>
          <button 
            :class="['segment-btn', { active: segmentoActivo === 'usuarios' }]" 
            @click="cambiarSegmento('usuarios')"
          >
            👥 Usuarios
          </button>
        </div>

        <!-- Buscador -->
        <div class="barra-busqueda animar-aparecer animar-delay-1">
          <span class="barra-busqueda__icono">🔍</span>
          <input 
            class="barra-busqueda__input" 
            v-model="busqueda" 
            :placeholder="segmentoActivo === 'fincas' ? 'Buscar finca...' : 'Buscar usuario...'" 
          />
        </div>

        <!-- Carga -->
        <div v-if="cargando" class="cargando-estado">
          <span class="cargando-spinner"></span>
          <p>Cargando información...</p>
        </div>

        <!-- SECCIÓN: FINCAS -->
        <div v-else-if="segmentoActivo === 'fincas'" class="fincas-sec">
          <div v-if="fincasFiltradas.length" class="admin-lista">
            <div 
              v-for="(finca, i) in fincasFiltradas" 
              :key="finca.id"
              class="finca-tarjeta animar-aparecer" 
              :style="{ animationDelay: (i * 80) + 'ms' }"
            >
              <div class="finca-cabecera">
                <div>
                  <h3 class="finca-nombre">{{ finca.nombre }}</h3>
                  <p class="finca-ubicacion">📍 {{ finca.ubicacion || 'Sin ubicación' }}</p>
                </div>
                <span class="insignia insignia--primario" style="margin-left:auto">
                  ID: {{ finca.id }}
                </span>
              </div>

              <div class="finca-stats">
                <div class="finca-stat">
                  <span class="finca-stat-valor">{{ finca.animals?.length || 0 }}</span>
                  <span class="finca-stat-label">Animales</span>
                </div>
                <div class="finca-stat">
                  <span class="finca-stat-valor">{{ finca.area_hectareas || 0 }}</span>
                  <span class="finca-stat-label">Hectáreas</span>
                </div>
                <div class="finca-stat">
                  <span class="finca-stat-valor owner-text" :title="getNombrePropietario(finca.user_id)">
                    {{ getNombrePropietario(finca.user_id) }}
                  </span>
                  <span class="finca-stat-label">Propietario</span>
                </div>
              </div>

              <p class="finca-desc">{{ finca.descripcion || 'Sin descripción' }}</p>

              <div class="finca-acciones">
                <button class="boton boton--secundario boton--pequeno" @click="editarFinca(finca)">✏️ Editar</button>
                <button class="boton boton--secundario boton--pequeno" style="color:var(--peligro)" @click="eliminarFinca(finca.id)">🗑️ Eliminar</button>
              </div>
            </div>
          </div>

          <div v-else class="estado-vacio">
            <div class="estado-vacio__icono">🏡</div>
            <h3 class="estado-vacio__titulo">Sin fincas</h3>
            <p class="estado-vacio__descripcion">No se encontraron fincas en el sistema.</p>
            <button class="boton boton--primario" @click="abrirAgregar">+ Nueva Finca</button>
          </div>
        </div>

        <!-- SECCIÓN: USUARIOS -->
        <div v-else-if="segmentoActivo === 'usuarios'" class="usuarios-sec">
          <div v-if="usuariosFiltrados.length" class="admin-lista">
            <div 
              v-for="(usr, i) in usuariosFiltrados" 
              :key="usr.id"
              class="usuario-tarjeta animar-aparecer" 
              :style="{ animationDelay: (i * 80) + 'ms' }"
            >
              <div class="usr-cabecera">
                <div class="usr-avatar">
                  {{ usr.name.charAt(0).toUpperCase() }}
                </div>
                <div class="usr-info">
                  <h3 class="usr-nombre">{{ usr.name }}</h3>
                  <p class="usr-email">✉️ {{ usr.email }}</p>
                </div>
                <span :class="['insignia', getBadgeClase(usr.role)]" style="margin-left:auto">
                  {{ usr.role }}
                </span>
              </div>

              <div class="usr-detalles">
                <p v-if="usr.role === 'veterinario'">
                  <strong>🏡 Fincas Asignadas:</strong> 
                  <span v-if="usr.shared_farms && usr.shared_farms.length">
                    {{ usr.shared_farms.map(f => f.nombre).join(', ') }}
                  </span>
                  <span v-else style="color:var(--peligro)">Ninguna finca asignada</span>
                </p>
                <p v-else-if="usr.invited_farm_id">
                  <strong>🏡 Finca Asignada:</strong> {{ usr.invited_farm?.nombre || 'Finca ID: ' + usr.invited_farm_id }}
                </p>
                <p v-if="usr.guest_expires_at">
                  <strong>⏳ Acceso Expira:</strong> {{ formatearFecha(usr.guest_expires_at) }}
                </p>
              </div>

              <div class="finca-acciones" style="margin-top:12px">
                <button class="boton boton--secundario boton--pequeno" @click="editarUsuario(usr)">✏️ Editar</button>
                <button class="boton boton--secundario boton--pequeno" style="color:var(--peligro)" @click="eliminarUsuario(usr.id)">🗑️ Eliminar</button>
              </div>
            </div>
          </div>

          <div v-else class="estado-vacio">
            <div class="estado-vacio__icono">👥</div>
            <h3 class="estado-vacio__titulo">Sin usuarios</h3>
            <p class="estado-vacio__descripcion">No se encontraron usuarios en el sistema.</p>
            <button class="boton boton--primario" @click="abrirAgregar">+ Nuevo Usuario</button>
          </div>
        </div>

      </div>

      <!-- MODAL FINCA -->
      <div v-if="mostrarModalFinca" class="modal-fondo" @click.self="cerrarModalFinca">
        <div class="modal-contenido vidrio">
          <h3 class="modal-titulo">{{ fincaEditando ? 'Editar Finca' : 'Nueva Finca' }}</h3>
          
          <div class="campo-grupo">
            <label class="campo-etiqueta">Nombre</label>
            <input class="campo-entrada" v-model="formFinca.nombre" placeholder="Nombre de la finca" />
          </div>

          <div class="campo-grupo">
            <label class="campo-etiqueta">Ubicación</label>
            <input class="campo-entrada" v-model="formFinca.ubicacion" placeholder="Dirección o zona" />
          </div>

          <div class="campo-grupo">
            <label class="campo-etiqueta">Área (hectáreas)</label>
            <input type="number" class="campo-entrada" v-model.number="formFinca.area_hectareas" placeholder="250" />
          </div>

          <div class="campo-grupo">
            <label class="campo-etiqueta">Descripción</label>
            <textarea class="campo-entrada" v-model="formFinca.descripcion" rows="3" placeholder="Descripción de la finca..."></textarea>
          </div>

          <!-- Selector de Propietario -->
          <div class="campo-grupo">
            <label class="campo-etiqueta">Propietario (Ganadero)</label>
            <select class="campo-entrada" v-model="formFinca.user_id">
              <option :value="null">-- Seleccione Propietario --</option>
              <option v-for="ganadero in listaGanaderos" :key="ganadero.id" :value="ganadero.id">
                {{ ganadero.name }} ({{ ganadero.email }})
              </option>
            </select>
          </div>

          <div style="display:flex;gap:12px;margin-top:8px">
            <button class="boton boton--secundario" style="flex:1" @click="cerrarModalFinca">Cancelar</button>
            <button class="boton boton--primario" style="flex:1" @click="guardarFinca" :disabled="guardando">
              {{ fincaEditando ? 'Actualizar' : 'Guardar' }}
            </button>
          </div>
        </div>
      </div>

      <!-- MODAL USUARIO -->
      <div v-if="mostrarModalUsuario" class="modal-fondo" @click.self="cerrarModalUsuario">
        <div class="modal-contenido vidrio">
          <h3 class="modal-titulo">{{ usuarioEditando ? 'Editar Usuario' : 'Nuevo Usuario' }}</h3>
          
          <div class="campo-grupo">
            <label class="campo-etiqueta">Nombre completo</label>
            <input class="campo-entrada" v-model="formUsuario.name" placeholder="Ej. Juan Pérez" />
          </div>

          <div class="campo-grupo">
            <label class="campo-etiqueta">Correo electrónico</label>
            <input type="email" class="campo-entrada" v-model="formUsuario.email" placeholder="correo@ejemplo.com" />
          </div>

          <div class="campo-grupo">
            <label class="campo-etiqueta">
              Contraseña {{ usuarioEditando ? '(dejar en blanco para conservar)' : '' }}
            </label>
            <input type="password" class="campo-entrada" v-model="formUsuario.password" placeholder="Mínimo 8 caracteres" />
          </div>

          <!-- Selector de Rol -->
          <div class="campo-grupo">
            <label class="campo-etiqueta">Rol del sistema</label>
            <select class="campo-entrada" v-model="formUsuario.role">
              <option value="invitado">Invitado (Lector)</option>
              <option value="veterinario">Veterinario (Médico)</option>
              <option value="ganadero">Ganadero (Dueño)</option>
              <option value="admin">Administrador (Total)</option>
            </select>
          </div>

          <!-- Selector de Fincas Autorizadas para Veterinarios (Multi-select) -->
          <div v-if="formUsuario.role === 'veterinario'" class="campo-grupo">
            <label class="campo-etiqueta">Fincas Autorizadas <span style="color:var(--peligro)">*</span></label>
            <div class="checkbox-lista">
              <div v-for="f in listaFincas" :key="f.id" class="checkbox-item">
                <input type="checkbox" :id="'farm-' + f.id" :value="f.id" v-model="formUsuario.farm_ids" />
                <label :for="'farm-' + f.id">🏡 {{ f.nombre }}</label>
              </div>
            </div>
          </div>

          <!-- Selector de Finca Asignada (Solo para Invitado) -->
          <div v-if="formUsuario.role === 'invitado'" class="campo-grupo">
            <label class="campo-etiqueta">Finca Autorizada <span style="color:var(--peligro)">*</span></label>
            <select class="campo-entrada" v-model="formUsuario.invited_farm_id">
              <option :value="null">-- Seleccione una Finca --</option>
              <option v-for="f in listaFincas" :key="f.id" :value="f.id">
                {{ f.nombre }}
              </option>
            </select>
          </div>

          <!-- Fecha de Expiración del Acceso (Opcional, para Invitados) -->
          <div v-if="formUsuario.role === 'invitado'" class="campo-grupo">
            <label class="campo-etiqueta">Expiración de acceso (Opcional)</label>
            <input type="date" class="campo-entrada" v-model="formUsuario.guest_expires_at" />
          </div>

          <div style="display:flex;gap:12px;margin-top:8px">
            <button class="boton boton--secundario" style="flex:1" @click="cerrarModalUsuario">Cancelar</button>
            <button class="boton boton--primario" style="flex:1" @click="guardarUsuario" :disabled="guardando">
              {{ usuarioEditando ? 'Actualizar' : 'Guardar' }}
            </button>
          </div>
        </div>
      </div>

      <!-- TOAST -->
      <div v-if="toast.visible" :class="['toast', `toast--${toast.tipo}`]">
        {{ toast.mensaje }}
      </div>

    </ion-content>
  </ion-page>
</template>

<script setup>
/* Vista de Dashboard de Administración global */
import { ref, reactive, computed, onMounted } from 'vue';
import { 
  IonPage, IonHeader, IonToolbar, IonTitle, IonContent, 
  IonButtons, IonButton, IonIcon, IonBackButton 
} from '@ionic/vue';
import { addOutline } from 'ionicons/icons';
import api from '@/services/api';

const segmentoActivo = ref('fincas');
const busqueda = ref('');
const cargando = ref(false);
const guardando = ref(false);

const listaFincas = ref([]);
const listaUsuarios = ref([]);

// Modals
const mostrarModalFinca = ref(false);
const fincaEditando = ref(null);
const formFinca = reactive({
  nombre: '',
  ubicacion: '',
  area_hectareas: 0,
  descripcion: '',
  user_id: null
});

const mostrarModalUsuario = ref(false);
const usuarioEditando = ref(null);
const formUsuario = reactive({
  name: '',
  email: '',
  password: '',
  role: 'invitado',
  invited_farm_id: null,
  guest_expires_at: '',
  farm_ids: []
});

// Toast state
const toast = reactive({ visible: false, mensaje: '', tipo: 'exito' });
let toastTimer = null;

function mostrarToast(mensaje, tipo = 'exito') {
  if (toastTimer) clearTimeout(toastTimer);
  toast.mensaje = mensaje;
  toast.tipo = tipo;
  toast.visible = true;
  toastTimer = setTimeout(() => { toast.visible = false; }, 3500);
}

function cambiarSegmento(seg) {
  segmentoActivo.value = seg;
  busqueda.value = '';
}

async function cargarDatos() {
  cargando.value = true;
  try {
    const resFincas = await api.get('/fincas');
    listaFincas.value = resFincas.data.datos || [];

    const resUsuarios = await api.get('/users');
    listaUsuarios.value = resUsuarios.data.datos || [];
  } catch (err) {
    mostrarToast(err.response?.data?.mensaje || 'Error al cargar datos del panel', 'error');
  } finally {
    cargando.value = false;
  }
}

onMounted(() => {
  cargarDatos();
});

const fincasFiltradas = computed(() => {
  const q = busqueda.value.toLowerCase().trim();
  if (!q) return listaFincas.value;
  return listaFincas.value.filter(f => 
    f.nombre.toLowerCase().includes(q) || 
    (f.ubicacion && f.ubicacion.toLowerCase().includes(q))
  );
});

const usuariosFiltrados = computed(() => {
  const q = busqueda.value.toLowerCase().trim();
  if (!q) return listaUsuarios.value;
  return listaUsuarios.value.filter(u => 
    u.name.toLowerCase().includes(q) || 
    u.email.toLowerCase().includes(q) ||
    u.role.toLowerCase().includes(q)
  );
});

const listaGanaderos = computed(() => {
  return listaUsuarios.value.filter(u => u.role === 'ganadero' || u.role === 'admin');
});

const requiereFinca = computed(() => {
  return formUsuario.role === 'veterinario' || formUsuario.role === 'invitado';
});

function getNombrePropietario(userId) {
  if (!userId) return 'Sin asignar';
  const u = listaUsuarios.value.find(usr => usr.id === userId);
  return u ? u.name : `Ganadero (ID: ${userId})`;
}

function getBadgeClase(role) {
  if (role === 'admin') return 'insignia--peligro';
  if (role === 'ganadero') return 'insignia--primario';
  if (role === 'veterinario') return 'insignia--advertencia';
  return 'insignia--secundario';
}

function formatearFecha(fechaStr) {
  if (!fechaStr) return '';
  try {
    const d = new Date(fechaStr);
    return d.toLocaleDateString('es-CR');
  } catch {
    return fechaStr;
  }
}

function abrirAgregar() {
  if (segmentoActivo.value === 'fincas') {
    fincaEditando.value = null;
    Object.assign(formFinca, {
      nombre: '',
      ubicacion: '',
      area_hectareas: 0,
      descripcion: '',
      user_id: null
    });
    mostrarModalFinca.value = true;
  } else {
    usuarioEditando.value = null;
    Object.assign(formUsuario, {
      name: '',
      email: '',
      password: '',
      role: 'invitado',
      invited_farm_id: null,
      guest_expires_at: '',
      farm_ids: []
    });
    mostrarModalUsuario.value = true;
  }
}

// CRUD Fincas
function editarFinca(finca) {
  fincaEditando.value = finca.id;
  Object.assign(formFinca, {
    nombre: finca.nombre,
    ubicacion: finca.ubicacion || '',
    area_hectareas: finca.area_hectareas || 0,
    descripcion: finca.descripcion || '',
    user_id: finca.user_id
  });
  mostrarModalFinca.value = true;
}

function cerrarModalFinca() {
  mostrarModalFinca.value = false;
  fincaEditando.value = null;
}

async function guardarFinca() {
  if (!formFinca.nombre) {
    mostrarToast('El nombre de la finca es requerido', 'error');
    return;
  }
  
  guardando.value = true;
  try {
    if (fincaEditando.value) {
      await api.put(`/fincas/${fincaEditando.value}`, formFinca);
      mostrarToast('Finca actualizada exitosamente', 'exito');
    } else {
      await api.post('/fincas', formFinca);
      mostrarToast('Finca creada exitosamente', 'exito');
    }
    cerrarModalFinca();
    await cargarDatos();
  } catch (err) {
    mostrarToast(err.response?.data?.mensaje || 'Error al guardar la finca', 'error');
  } finally {
    guardando.value = false;
  }
}

async function eliminarFinca(id) {
  if (!confirm('¿Está seguro de eliminar esta finca? Se borrarán sus animales asociados.')) return;
  
  cargando.value = true;
  try {
    await api.delete(`/fincas/${id}`);
    mostrarToast('Finca eliminada exitosamente', 'exito');
    await cargarDatos();
  } catch (err) {
    mostrarToast(err.response?.data?.mensaje || 'Error al eliminar la finca', 'error');
  } finally {
    cargando.value = false;
  }
}

// CRUD Usuarios
function editarUsuario(usr) {
  usuarioEditando.value = usr.id;
  
  let formattedExpDate = '';
  if (usr.guest_expires_at) {
    try {
      const d = new Date(usr.guest_expires_at);
      formattedExpDate = d.toISOString().split('T')[0];
    } catch {
      // blank
    }
  }

  Object.assign(formUsuario, {
    name: usr.name,
    email: usr.email,
    password: '',
    role: usr.role,
    invited_farm_id: usr.invited_farm_id,
    guest_expires_at: formattedExpDate,
    farm_ids: Array.isArray(usr.shared_farms) ? usr.shared_farms.map(f => f.id) : []
  });
  mostrarModalUsuario.value = true;
}

function cerrarModalUsuario() {
  mostrarModalUsuario.value = false;
  usuarioEditando.value = null;
}

async function guardarUsuario() {
  if (!formUsuario.name || !formUsuario.email) {
    mostrarToast('El nombre y correo electrónico son requeridos', 'error');
    return;
  }
  if (!usuarioEditando.value && !formUsuario.password) {
    mostrarToast('La contraseña es requerida para nuevos usuarios', 'error');
    return;
  }
  if (formUsuario.role === 'invitado' && !formUsuario.invited_farm_id) {
    mostrarToast('Debe asignar una finca autorizada para el Invitado', 'error');
    return;
  }
  if (formUsuario.role === 'veterinario' && formUsuario.farm_ids.length === 0) {
    mostrarToast('Debe seleccionar al menos una finca autorizada para el Veterinario', 'error');
    return;
  }

  guardando.value = true;
  try {
    const payload = { ...formUsuario };
    if (!payload.password) delete payload.password;
    if (!requiereFinca.value) {
      payload.invited_farm_id = null;
      payload.guest_expires_at = null;
      payload.farm_ids = [];
    } else if (payload.role !== 'invitado') {
      payload.guest_expires_at = null;
    }

    if (usuarioEditando.value) {
      await api.put(`/users/${usuarioEditando.value}`, payload);
      mostrarToast('Usuario actualizado exitosamente', 'exito');
    } else {
      await api.post('/users', payload);
      mostrarToast('Usuario creado exitosamente', 'exito');
    }
    cerrarModalUsuario();
    await cargarDatos();
  } catch (err) {
    mostrarToast(err.response?.data?.mensaje || 'Error al guardar el usuario', 'error');
  } finally {
    guardando.value = false;
  }
}

async function eliminarUsuario(id) {
  if (!confirm('¿Está seguro de eliminar este usuario?')) return;
  
  cargando.value = true;
  try {
    await api.delete(`/users/${id}`);
    mostrarToast('Usuario eliminado exitosamente', 'exito');
    await cargarDatos();
  } catch (err) {
    mostrarToast(err.response?.data?.mensaje || 'Error al eliminar el usuario', 'error');
  } finally {
    cargando.value = false;
  }
}
</script>

<style scoped>
.admin-contenido {
  padding: 0 20px;
  display: flex;
  flex-direction: column;
  gap: 16px;
}

.segment-wrap {
  display: grid;
  grid-template-columns: 1fr 1fr;
  background: var(--superficie-hundida);
  padding: 4px;
  border-radius: var(--borde-radio-lg);
  border: 1px solid var(--borde-color);
}

.segment-btn {
  background: transparent;
  border: none;
  padding: 12px;
  border-radius: var(--borde-radio-md);
  font-family: var(--fuente-display);
  font-weight: 700;
  font-size: var(--tamano-sm);
  color: var(--texto-secundario);
  cursor: pointer;
  transition: all var(--transicion-rapida);
}

.segment-btn.active {
  background: var(--superficie-tarjeta);
  color: var(--primario);
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.admin-lista {
  display: flex;
  flex-direction: column;
  gap: 16px;
  padding-bottom: 32px;
}

.finca-tarjeta, .usuario-tarjeta {
  background: var(--superficie-tarjeta);
  border: 1px solid var(--borde-color);
  border-radius: var(--borde-radio-lg);
  padding: 20px;
  transition: all var(--transicion-normal);
}

.finca-tarjeta:hover, .usuario-tarjeta:hover {
  box-shadow: var(--sombra-md);
}

.finca-cabecera, .usr-cabecera {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 12px;
}

.finca-nombre, .usr-nombre {
  font-family: var(--fuente-display);
  font-size: var(--tamano-lg);
  font-weight: 700;
  margin: 0;
}

.finca-ubicacion, .usr-email {
  font-size: var(--tamano-xs);
  color: var(--texto-terciario);
  margin-top: 2px;
  margin-bottom: 0;
}

.finca-stats {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
  margin: 16px 0;
  padding: 12px;
  background: var(--superficie-hundida);
  border-radius: var(--borde-radio-md);
}

.finca-stat {
  text-align: center;
  min-width: 0;
}

.finca-stat-valor {
  display: block;
  font-family: var(--fuente-display);
  font-weight: 700;
  font-size: var(--tamano-base);
  color: var(--primario);
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
}

.finca-stat-label {
  font-size: 10px;
  color: var(--texto-terciario);
  text-transform: uppercase;
  letter-spacing: 0.5px;
}

.finca-desc {
  font-size: var(--tamano-sm);
  color: var(--texto-secundario);
  margin-bottom: 16px;
  line-height: 1.4;
}

.finca-acciones {
  display: flex;
  gap: 8px;
}

.usr-avatar {
  width: 44px;
  height: 44px;
  border-radius: 50%;
  background: var(--primario);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: var(--fuente-display);
  font-weight: 700;
  font-size: var(--tamano-base);
}

.usr-info {
  flex: 1;
  min-width: 0;
}

.usr-detalles {
  background: var(--superficie-hundida);
  border-radius: var(--borde-radio-md);
  padding: 10px 14px;
  font-size: var(--tamano-xs);
  color: var(--texto-secundario);
  display: flex;
  flex-direction: column;
  gap: 6px;
}

.usr-detalles p {
  margin: 0;
}

.insignia--peligro {
  background: var(--superficie-hundida);
  color: var(--peligro);
  border: 1px solid rgba(220, 53, 69, 0.2);
}

.insignia--advertencia {
  background: var(--superficie-hundida);
  color: var(--acento);
  border: 1px solid rgba(200, 169, 81, 0.2);
}

.cargando-estado {
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding: 60px 40px;
  color: var(--texto-terciario);
  gap: 12px;
}

.owner-text {
  font-size: var(--tamano-xs);
}

.modal-fondo {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.4);
  display: flex;
  align-items: flex-end;
  justify-content: center;
  z-index: 1000;
}

.modal-contenido {
  width: 100%;
  max-width: 480px;
  max-height: 85vh;
  overflow-y: auto;
  padding: 32px 24px;
  border-radius: var(--borde-radio-xl) var(--borde-radio-xl) 0 0;
  display: flex;
  flex-direction: column;
  gap: 14px;
}

.modal-titulo {
  font-family: var(--fuente-display);
  font-size: var(--tamano-xl);
  font-weight: 700;
  margin: 0 0 6px 0;
}

textarea.campo-entrada {
  resize: vertical;
  min-height: 60px;
}

select.campo-entrada {
  appearance: auto;
}

.checkbox-lista {
  max-height: 150px;
  overflow-y: auto;
  border: 1px solid var(--borde-color);
  border-radius: var(--borde-radio-md);
  padding: 12px;
  background: var(--superficie-hundida);
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.checkbox-item {
  display: flex;
  align-items: center;
  gap: 10px;
  font-size: var(--tamano-sm);
}
.checkbox-item input {
  cursor: pointer;
  width: 18px;
  height: 18px;
}
.checkbox-item label {
  cursor: pointer;
  user-select: none;
  color: var(--texto-primario);
}
</style>
