<template>
  <ion-page>
    <ion-header class="ion-no-border">
      <ion-toolbar>
        <ion-title>🩺 Módulo Veterinario</ion-title>
        <ion-buttons slot="end">
          <ion-button @click="cerrarSesion" style="color:var(--peligro)">
            🚪 Salir
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true">
      <div class="vf-contenido">

        <!-- Encabezado de bienvenida -->
        <section class="animar-aparecer">
          <span class="etiqueta-seccion">MÓDULO VETERINARIO</span>
          <h2 class="titulo-seccion">Mis Fincas Asignadas</h2>
          <p style="font-size:var(--tamano-sm);color:var(--texto-secundario);margin-top:4px">
            Seleccione una finca para consultar y gestionar los animales bajo su cuidado.
          </p>
        </section>

        <!-- Buscador -->
        <div class="vf-buscador animar-aparecer animar-delay-1">
          <span>🔍</span>
          <input
            id="busqueda-finca-vet"
            class="vf-buscador-input"
            v-model="busqueda"
            placeholder="Buscar finca por nombre..."
            type="search"
          />
        </div>

        <!-- Ordenar -->
        <div class="vf-filtros animar-aparecer animar-delay-1" v-if="fincas.length > 1">
          <button
            class="vf-filtro-btn"
            :class="{ 'vf-filtro-btn--activo': ordenPor === 'nombre' }"
            @click="ordenPor = 'nombre'"
          >A–Z</button>
          <button
            class="vf-filtro-btn"
            :class="{ 'vf-filtro-btn--activo': ordenPor === 'animales' }"
            @click="ordenPor = 'animales'"
          >Mayor cantidad</button>
        </div>

        <!-- Estado de carga -->
        <div v-if="cargando" class="estado-vacio animar-aparecer animar-delay-2">
          <span class="cargando-spinner"></span>
          <p style="margin-top:12px;color:var(--texto-terciario)">Cargando fincas asignadas...</p>
        </div>

        <!-- Sin fincas -->
        <div v-else-if="fincasFiltradas.length === 0" class="estado-vacio animar-aparecer animar-delay-2">
          <span class="estado-vacio__icono">🏡</span>
          <h3 class="estado-vacio__titulo">Sin fincas asignadas</h3>
          <p class="estado-vacio__descripcion">
            No tiene fincas asignadas en este momento.<br>
            Solicite una invitación al ganadero responsable.
          </p>
        </div>

        <!-- Lista de fincas -->
        <div v-else class="vf-lista animar-aparecer animar-delay-2">
          <div
            v-for="finca in fincasFiltradas"
            :key="finca.id"
            class="vf-tarjeta"
            @click="irAAnimales(finca.id)"
          >
            <!-- Icono y encabezado -->
            <div class="vf-tarjeta-cabecera">
              <div class="vf-finca-icono">🏡</div>
              <div class="vf-finca-titulo">
                <h3>{{ finca.nombre }}</h3>
                <span class="vf-finca-ubicacion">
                  📍 {{ finca.ubicacion || 'Ubicación no especificada' }}
                </span>
              </div>
              <span class="vf-flecha">›</span>
            </div>

            <!-- Métricas -->
            <div class="vf-metricas">
              <div class="vf-metrica-item">
                <span class="vf-metrica-valor">{{ contarAnimales(finca) }}</span>
                <span class="vf-metrica-label">Animales</span>
              </div>
              <div class="vf-metrica-item" v-if="finca.area_hectareas">
                <span class="vf-metrica-valor">{{ finca.area_hectareas }}</span>
                <span class="vf-metrica-label">Hectáreas</span>
              </div>
              <div class="vf-metrica-item" v-if="finca.user">
                <span class="vf-metrica-valor" style="font-size:var(--tamano-xs)">{{ finca.user.name || '—' }}</span>
                <span class="vf-metrica-label">Propietario</span>
              </div>
            </div>

            <!-- Descripción -->
            <p v-if="finca.descripcion" class="vf-descripcion">
              {{ finca.descripcion }}
            </p>

            <!-- Pie de tarjeta -->
            <div class="vf-tarjeta-pie">
              <span class="insignia insignia--primario" style="font-size:10px">
                Acceso autorizado
              </span>
              <button class="boton boton--primario boton--pequeno" @click.stop="irAAnimales(finca.id)">
                Ver Animales →
              </button>
            </div>
          </div>
        </div>

        <div style="height:32px"></div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup>
/* Vista principal del Módulo Veterinario — Mis Fincas Asignadas */
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonButton } from '@ionic/vue';
import { useAlmacenAuth } from '@/stores/auth.js';
import api from '@/services/api';

const router  = useRouter();
const almacenAuth = useAlmacenAuth();

async function cerrarSesion() {
  await almacenAuth.cerrarSesion();
  router.replace('/login');
}
const fincas  = ref([]);
const cargando = ref(false);
const busqueda = ref('');
const ordenPor = ref('nombre'); // 'nombre' | 'animales'

const fincasFiltradas = computed(() => {
  let lista = [...fincas.value];

  // Filtro por búsqueda
  if (busqueda.value.trim()) {
    const termino = busqueda.value.toLowerCase();
    lista = lista.filter(f => f.nombre.toLowerCase().includes(termino));
  }

  // Ordenar
  if (ordenPor.value === 'nombre') {
    lista.sort((a, b) => a.nombre.localeCompare(b.nombre));
  } else if (ordenPor.value === 'animales') {
    lista.sort((a, b) => contarAnimales(b) - contarAnimales(a));
  }

  return lista;
});

function contarAnimales(finca) {
  if (Array.isArray(finca.animals)) return finca.animals.length;
  return finca.animals_count ?? 0;
}

async function cargarFincas() {
  cargando.value = true;
  try {
    // GET /fincas ya maneja invitados: devuelve solo la finca asignada
    const respuesta = await api.get('/fincas');
    fincas.value = respuesta.data.datos;
  } catch (err) {
    console.error('Error al cargar fincas:', err);
  } finally {
    cargando.value = false;
  }
}

function irAAnimales(fincaId) {
  router.push(`/app/veterinario/finca/${fincaId}`);
}

onMounted(cargarFincas);
</script>

<style scoped>
.vf-contenido { padding: 0 20px; display: flex; flex-direction: column; gap: 20px; }

.vf-buscador {
  display: flex; align-items: center; gap: 10px;
  background: var(--superficie-tarjeta);
  border: 1.5px solid var(--borde-color);
  border-radius: var(--borde-radio-md);
  padding: 10px 14px;
}
.vf-buscador-input {
  flex: 1; background: transparent; border: none; outline: none;
  font-family: var(--fuente-cuerpo); font-size: var(--tamano-sm); color: var(--texto-primario);
}

.vf-filtros { display: flex; gap: 8px; }
.vf-filtro-btn {
  padding: 6px 14px; border-radius: 20px; border: 1.5px solid var(--borde-color);
  background: var(--superficie-tarjeta); font-size: var(--tamano-xs);
  font-family: var(--fuente-cuerpo); cursor: pointer; color: var(--texto-secundario);
  transition: all var(--transicion-normal);
}
.vf-filtro-btn--activo {
  border-color: var(--primario); background: var(--primario-ultra-suave); color: var(--primario); font-weight: 600;
}

.vf-lista { display: flex; flex-direction: column; gap: 14px; }
.vf-tarjeta {
  background: var(--superficie-tarjeta);
  border: 1.5px solid var(--borde-color);
  border-radius: var(--borde-radio-lg);
  padding: 18px;
  cursor: pointer;
  transition: all var(--transicion-normal);
  display: flex; flex-direction: column; gap: 14px;
}
.vf-tarjeta:hover {
  border-color: var(--primario-suave);
  box-shadow: 0 4px 16px rgba(101,109,74,0.12);
  transform: translateY(-2px);
}

.vf-tarjeta-cabecera {
  display: flex; align-items: flex-start; gap: 12px;
}
.vf-finca-icono {
  width: 48px; height: 48px; border-radius: 12px;
  background: var(--primario-ultra-suave);
  display: flex; align-items: center; justify-content: center;
  font-size: 22px; flex-shrink: 0;
}
.vf-finca-titulo { flex: 1; }
.vf-finca-titulo h3 {
  font-family: var(--fuente-display); font-weight: 700;
  font-size: var(--tamano-lg); margin: 0; color: var(--texto-primario);
}
.vf-finca-ubicacion {
  font-size: var(--tamano-xs); color: var(--texto-terciario); display: block; margin-top: 4px;
}
.vf-flecha { font-size: 1.6rem; color: var(--texto-terciario); line-height: 1; }

.vf-metricas {
  display: flex; gap: 0;
  background: var(--superficie-hundida);
  border-radius: var(--borde-radio-md);
  overflow: hidden;
}
.vf-metrica-item {
  flex: 1; display: flex; flex-direction: column; align-items: center;
  padding: 10px 8px; gap: 2px;
  border-right: 1px solid var(--borde-color);
}
.vf-metrica-item:last-child { border-right: none; }
.vf-metrica-valor {
  font-family: var(--fuente-display); font-weight: 700;
  font-size: var(--tamano-lg); color: var(--primario);
}
.vf-metrica-label {
  font-size: 10px; color: var(--texto-terciario); text-transform: uppercase; letter-spacing: 0.3px;
}

.vf-descripcion {
  font-size: var(--tamano-xs); color: var(--texto-secundario);
  margin: 0; line-height: 1.5;
  display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;
}

.vf-tarjeta-pie {
  display: flex; justify-content: space-between; align-items: center;
  padding-top: 10px; border-top: 1px solid var(--borde-color);
}
</style>
