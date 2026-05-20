<template>
  <ion-page>
    <ion-header class="ion-no-border">
      <ion-toolbar>
        <ion-title class="encabezado-titulo">
          <span class="encabezado-titulo__bov">Bov</span><span>Weight</span>
          <span class="encabezado-titulo__cr">CR</span>
        </ion-title>
        <ion-buttons slot="end">
          <ion-button @click="mostrarFormulario = true">
            <ion-icon :icon="addOutline" />
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>

    <ion-content :fullscreen="true">
      <div class="animales-contenido">
        <section class="animar-aparecer">
          <span class="etiqueta-seccion">GESTIÓN DE GANADO</span>
          <h2 class="titulo-seccion">Animales</h2>
        </section>

        <!-- Barra de búsqueda -->
        <div class="barra-busqueda animar-aparecer animar-delay-1">
          <span class="barra-busqueda__icono">🔍</span>
          <input class="barra-busqueda__input" v-model="almacen.busqueda"
            placeholder="Buscar por nombre, arete o raza..." />
        </div>

        <!-- Filtros -->
        <div class="animales-filtros animar-aparecer animar-delay-2">
          <select class="filtro-select" v-model="almacen.filtroFinca">
            <option v-for="f in almacenFincas.lista" :key="f.id" :value="f.id">{{ f.nombre }}</option>
          </select>
          <select class="filtro-select" v-model="almacen.filtroRaza">
            <option value="">Todas las razas</option>
            <option v-for="raza in almacen.razasDisponibles" :key="raza" :value="raza">{{ raza }}</option>
          </select>
        </div>

        <!-- Contador -->
        <p class="animales-contador">
          {{ almacen.animalesFiltrados.length }} animales encontrados
        </p>

        <!-- Lista de animales -->
        <div v-if="almacen.animalesFiltrados.length" class="animales-lista">
          <div v-for="(animal, i) in almacen.animalesFiltrados" :key="animal.id"
            class="animal-tarjeta animar-aparecer" :style="{ animationDelay: (i * 60) + 'ms' }"
            @click="verDetalle(animal.id)">
            <div class="animal-avatar">
              {{ animal.nombre ? animal.nombre.charAt(0) : 'A' }}
            </div>
            <div class="animal-info">
              <div class="animal-cabecera">
                <strong>{{ animal.nombre || 'Sin nombre' }}</strong>
              </div>
              <span class="animal-detalle">{{ animal.arete }} · {{ animal.raza }} · {{ animal.genero }}</span>
              <span class="animal-peso">{{ animal.peso_actual || 0 }} kg</span>
            </div>
            <ion-icon :icon="chevronForwardOutline" class="animal-flecha" />
          </div>
        </div>

        <!-- Estado vacío -->
        <div v-else class="estado-vacio">
          <div class="estado-vacio__icono">🐮</div>
          <h3 class="estado-vacio__titulo">Sin resultados</h3>
          <p class="estado-vacio__descripcion">No se encontraron animales con los filtros seleccionados.</p>
          <button class="boton boton--secundario" @click="limpiarFiltros">Limpiar filtros</button>
        </div>
      </div>

      <!-- Modal agregar animal -->
      <div v-if="mostrarFormulario" class="modal-fondo" @click.self="mostrarFormulario = false">
        <div class="modal-contenido vidrio">
          <h3 class="modal-titulo">Nuevo Animal</h3>
          <div class="campo-grupo">
            <label class="campo-etiqueta">Número de Arete</label>
            <div style="display:flex;align-items:center;background:var(--superficie-tarjeta);border:1px solid var(--borde-color);border-radius:var(--borde-radio-md);padding:0 12px;margin-top:6px">
              <span style="font-weight:bold;color:var(--primario);margin-right:6px">CR-</span>
              <input class="campo-entrada" v-model="numeroAreteIngresado" placeholder="1001" style="border:none;padding:10px 0;outline:none;background:transparent;width:100%" />
            </div>
          </div>
          <div class="campo-grupo">
            <label class="campo-etiqueta">Nombre (Opcional)</label>
            <input class="campo-entrada" v-model="nuevoAnimal.nombre" placeholder="Nombre del animal" />
          </div>
          <div class="campo-grupo">
            <label class="campo-etiqueta">Raza</label>
            <input class="campo-entrada" v-model="nuevoAnimal.raza" placeholder="Brahman" />
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div class="campo-grupo">
              <label class="campo-etiqueta">Género</label>
              <select class="campo-entrada" v-model="nuevoAnimal.genero">
                <option>Macho</option><option>Hembra</option>
              </select>
            </div>
            <div class="campo-grupo">
              <label class="campo-etiqueta">Finca</label>
              <select class="campo-entrada" v-model="nuevoAnimal.farm_id">
                <option v-for="f in almacenFincas.lista" :key="f.id" :value="f.id">{{ f.nombre }}</option>
              </select>
            </div>
          </div>
          <div class="campo-grupo">
            <label class="campo-etiqueta">Fecha de Nacimiento</label>
            <input type="date" class="campo-entrada" v-model="nuevoAnimal.fecha_nacimiento" />
          </div>
          <div style="display:flex;gap:12px;margin-top:8px">
            <button class="boton boton--secundario" style="flex:1" @click="mostrarFormulario = false">Cancelar</button>
            <button class="boton boton--primario" style="flex:1" @click="guardarAnimal">Guardar</button>
          </div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup>
/* Vista de Animales con búsqueda, filtros y CRUD */
import { ref, reactive, onMounted, watch } from 'vue';
import { useRouter } from 'vue-router';
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonButton, IonIcon } from '@ionic/vue';
import { addOutline, chevronForwardOutline } from 'ionicons/icons';
import { useAlmacenAnimales } from '@/stores/animales.js';
import { useAlmacenFincas } from '@/stores/fincas.js';

const router = useRouter();
const almacen = useAlmacenAnimales();
const almacenFincas = useAlmacenFincas();

const mostrarFormulario = ref(false);
const numeroAreteIngresado = ref('');
const nuevoAnimal = reactive({
  arete: '', nombre: '', raza: '', genero: 'Macho', fecha_nacimiento: '',
  farm_id: null, peso_actual: 0, notas: ''
});

onMounted(async () => {
  if (almacenFincas.lista.length === 0) {
    await almacenFincas.cargarFincas();
  }
  
  if (almacenFincas.lista.length > 0) {
    if (!almacen.filtroFinca) {
      almacen.filtroFinca = almacenFincas.lista[0].id;
    }
    await almacen.cargarAnimalesPorFinca(almacen.filtroFinca);
  }
});

watch(() => almacen.filtroFinca, async (nuevaFincaId) => {
  if (nuevaFincaId) {
    await almacen.cargarAnimalesPorFinca(nuevaFincaId);
  }
});

function verDetalle(id) { router.push(`/app/animales/${id}`); }

function limpiarFiltros() {
  almacen.busqueda = '';
  almacen.filtroRaza = '';
}

async function guardarAnimal() {
  let refNumero = numeroAreteIngresado.value.trim().toUpperCase();
  if (!refNumero) {
    alert('Por favor ingrese el número de arete.');
    return;
  }
  
  if (refNumero.startsWith('CR-')) {
    refNumero = refNumero.substring(3);
  }
  
  nuevoAnimal.arete = 'CR-' + refNumero;
  if (!nuevoAnimal.farm_id) {
    alert('Por favor seleccione una finca.');
    return;
  }
  
  const resultado = await almacen.agregarAnimal({ ...nuevoAnimal });
  if (resultado.exito) {
    mostrarFormulario.value = false;
    numeroAreteIngresado.value = '';
    Object.assign(nuevoAnimal, { arete: '', nombre: '', raza: '', genero: 'Macho', fecha_nacimiento: '', farm_id: almacen.filtroFinca });
  } else {
    alert(resultado.error);
  }
}
</script>

<style scoped>
.encabezado-titulo { font-family: var(--fuente-display); font-weight: 800; font-size: 1.2rem; }
.encabezado-titulo__bov { color: var(--primario-medio); }
.encabezado-titulo__cr { color: var(--acento); font-size: 0.9rem; }
.animales-contenido { padding: 0 20px; display: flex; flex-direction: column; gap: 16px; }
.animales-filtros { display: flex; gap: 12px; }
.filtro-select {
  flex: 1; padding: 10px 12px; border-radius: var(--borde-radio-completo);
  border: 1px solid var(--borde-color); background: var(--superficie-tarjeta);
  font-family: var(--fuente-cuerpo); font-size: var(--tamano-sm);
  color: var(--texto-primario); cursor: pointer; outline: none;
}
.animales-contador { font-size: var(--tamano-xs); color: var(--texto-terciario); }
.animales-lista { display: flex; flex-direction: column; gap: 8px; padding-bottom: 32px; }
.animal-tarjeta {
  display: flex; align-items: center; gap: 14px; padding: 16px;
  background: var(--superficie-tarjeta); border-radius: var(--borde-radio-lg);
  border: 1px solid var(--borde-color); cursor: pointer;
  transition: all var(--transicion-normal);
}
.animal-tarjeta:hover { transform: translateY(-1px); box-shadow: var(--sombra-md); }
.animal-avatar {
  width: 48px; height: 48px; border-radius: 50%;
  background: var(--primario-ultra-suave); display: flex; align-items: center;
  justify-content: center; font-family: var(--fuente-display); font-weight: 700;
  font-size: var(--tamano-lg); color: var(--primario); flex-shrink: 0;
}
.animal-info { flex: 1; display: flex; flex-direction: column; gap: 2px; }
.animal-cabecera { display: flex; align-items: center; justify-content: space-between; }
.animal-detalle { font-size: var(--tamano-xs); color: var(--texto-terciario); }
.animal-peso { font-family: var(--fuente-display); font-weight: 700; font-size: var(--tamano-base); color: var(--primario); }
.animal-flecha { color: var(--texto-terciario); font-size: 18px; }
.modal-fondo {
  position: fixed; inset: 0; background: rgba(0,0,0,0.4); display: flex;
  align-items: flex-end; justify-content: center; z-index: 1000;
}
.modal-contenido {
  width: 100%; max-width: 480px; max-height: 85vh; overflow-y: auto;
  padding: 32px 24px; border-radius: var(--borde-radio-xl) var(--borde-radio-xl) 0 0;
  display: flex; flex-direction: column; gap: 14px;
}
.modal-titulo { font-family: var(--fuente-display); font-size: var(--tamano-xl); font-weight: 700; }
</style>
