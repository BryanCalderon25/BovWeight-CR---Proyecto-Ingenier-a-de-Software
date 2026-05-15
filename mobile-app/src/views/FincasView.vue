<template>
  <ion-page>
    <ion-header class="ion-no-border">
      <ion-toolbar>
        <ion-buttons slot="start">
          <ion-back-button default-href="/app/inicio" text="" />
        </ion-buttons>
        <ion-title>Fincas</ion-title>
        <ion-buttons slot="end">
          <ion-button @click="mostrarFormulario = true">
            <ion-icon :icon="addOutline" />
          </ion-button>
        </ion-buttons>
      </ion-toolbar>
    </ion-header>
    <ion-content :fullscreen="true">
      <div class="fincas-contenido">
        <section class="animar-aparecer">
          <span class="etiqueta-seccion">GESTIÓN TERRITORIAL</span>
          <h2 class="titulo-seccion">Mis Fincas</h2>
        </section>

        <div class="barra-busqueda animar-aparecer animar-delay-1">
          <span class="barra-busqueda__icono">🔍</span>
          <input class="barra-busqueda__input" v-model="almacen.busqueda" placeholder="Buscar finca..." />
        </div>

        <div v-if="almacen.fincasFiltradas.length" class="fincas-lista">
          <div v-for="(finca, i) in almacen.fincasFiltradas" :key="finca.id"
            class="finca-tarjeta animar-aparecer" :style="{ animationDelay: (i * 80) + 'ms' }">
            <div class="finca-cabecera">
              <div>
                <h3 class="finca-nombre">{{ finca.nombre }}</h3>
                <p class="finca-ubicacion">📍 {{ finca.ubicacion }}, {{ finca.provincia }}</p>
              </div>
              <span class="insignia insignia--exito">{{ finca.estado }}</span>
            </div>
            <div class="finca-stats">
              <div class="finca-stat">
                <span class="finca-stat-valor">{{ finca.animales }}</span>
                <span class="finca-stat-label">Animales</span>
              </div>
              <div class="finca-stat">
                <span class="finca-stat-valor">{{ finca.tamano }}</span>
                <span class="finca-stat-label">Hectáreas</span>
              </div>
              <div class="finca-stat">
                <span class="finca-stat-valor">{{ finca.canton }}</span>
                <span class="finca-stat-label">Cantón</span>
              </div>
            </div>
            <p class="finca-desc">{{ finca.descripcion }}</p>
            <div class="finca-acciones">
              <button class="boton boton--secundario boton--pequeno" @click="editarFinca(finca)">✏️ Editar</button>
              <button class="boton boton--secundario boton--pequeno" style="color:var(--peligro)" @click="eliminarFinca(finca.id)">🗑️ Eliminar</button>
            </div>
          </div>
        </div>

        <div v-else class="estado-vacio">
          <div class="estado-vacio__icono">🏡</div>
          <h3 class="estado-vacio__titulo">Sin fincas</h3>
          <p class="estado-vacio__descripcion">Agregue su primera finca para comenzar.</p>
          <button class="boton boton--primario" @click="mostrarFormulario = true">+ Agregar Finca</button>
        </div>
      </div>

      <!-- Modal -->
      <div v-if="mostrarFormulario" class="modal-fondo" @click.self="mostrarFormulario = false">
        <div class="modal-contenido vidrio">
          <h3 class="modal-titulo">{{ fincaEditando ? 'Editar Finca' : 'Nueva Finca' }}</h3>
          <div class="campo-grupo"><label class="campo-etiqueta">Nombre</label><input class="campo-entrada" v-model="formulario.nombre" placeholder="Nombre de la finca" /></div>
          <div class="campo-grupo"><label class="campo-etiqueta">Ubicación</label><input class="campo-entrada" v-model="formulario.ubicacion" placeholder="Dirección o zona" /></div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
            <div class="campo-grupo"><label class="campo-etiqueta">Provincia</label>
              <select class="campo-entrada" v-model="formulario.provincia">
                <option>San José</option><option>Alajuela</option><option>Cartago</option>
                <option>Heredia</option><option>Guanacaste</option><option>Puntarenas</option><option>Limón</option>
              </select>
            </div>
            <div class="campo-grupo"><label class="campo-etiqueta">Cantón</label><input class="campo-entrada" v-model="formulario.canton" placeholder="Cantón" /></div>
          </div>
          <div class="campo-grupo"><label class="campo-etiqueta">Tamaño (hectáreas)</label><input type="number" class="campo-entrada" v-model.number="formulario.tamano" placeholder="250" /></div>
          <div class="campo-grupo"><label class="campo-etiqueta">Descripción</label><textarea class="campo-entrada" v-model="formulario.descripcion" rows="3" placeholder="Descripción de la finca..."></textarea></div>
          <div style="display:flex;gap:12px;margin-top:8px">
            <button class="boton boton--secundario" style="flex:1" @click="cerrarFormulario">Cancelar</button>
            <button class="boton boton--primario" style="flex:1" @click="guardarFinca">{{ fincaEditando ? 'Actualizar' : 'Guardar' }}</button>
          </div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup>
/* Vista de Fincas con CRUD completo */
import { ref, reactive } from 'vue';
import { IonPage, IonHeader, IonToolbar, IonTitle, IonContent, IonButtons, IonButton, IonIcon, IonBackButton } from '@ionic/vue';
import { addOutline } from 'ionicons/icons';
import { useAlmacenFincas } from '@/stores/fincas.js';

const almacen = useAlmacenFincas();
const mostrarFormulario = ref(false);
const fincaEditando = ref(null);
const formulario = reactive({ nombre: '', ubicacion: '', provincia: 'Alajuela', canton: '', tamano: 0, descripcion: '' });

function editarFinca(finca) {
  fincaEditando.value = finca.id;
  Object.assign(formulario, { nombre: finca.nombre, ubicacion: finca.ubicacion, provincia: finca.provincia, canton: finca.canton, tamano: finca.tamano, descripcion: finca.descripcion });
  mostrarFormulario.value = true;
}

function guardarFinca() {
  if (!formulario.nombre) return;
  if (fincaEditando.value) {
    almacen.actualizarFinca(fincaEditando.value, { ...formulario });
  } else {
    almacen.agregarFinca({ ...formulario });
  }
  cerrarFormulario();
}

function eliminarFinca(id) {
  if (confirm('¿Eliminar esta finca?')) almacen.eliminarFinca(id);
}

function cerrarFormulario() {
  mostrarFormulario.value = false;
  fincaEditando.value = null;
  Object.assign(formulario, { nombre: '', ubicacion: '', provincia: 'Alajuela', canton: '', tamano: 0, descripcion: '' });
}
</script>

<style scoped>
.fincas-contenido{padding:0 20px;display:flex;flex-direction:column;gap:16px}
.fincas-lista{display:flex;flex-direction:column;gap:16px;padding-bottom:32px}
.finca-tarjeta{background:var(--superficie-tarjeta);border:1px solid var(--borde-color);border-radius:var(--borde-radio-lg);padding:20px;transition:all var(--transicion-normal)}
.finca-tarjeta:hover{box-shadow:var(--sombra-md)}
.finca-cabecera{display:flex;justify-content:space-between;align-items:flex-start}
.finca-nombre{font-family:var(--fuente-display);font-size:var(--tamano-lg);font-weight:700}
.finca-ubicacion{font-size:var(--tamano-xs);color:var(--texto-terciario);margin-top:2px}
.finca-stats{display:grid;grid-template-columns:repeat(3,1fr);gap:8px;margin:16px 0;padding:12px;background:var(--superficie-hundida);border-radius:var(--borde-radio-md)}
.finca-stat{text-align:center}
.finca-stat-valor{display:block;font-family:var(--fuente-display);font-weight:700;font-size:var(--tamano-lg);color:var(--primario)}
.finca-stat-label{font-size:var(--tamano-xs);color:var(--texto-terciario)}
.finca-desc{font-size:var(--tamano-sm);color:var(--texto-secundario);margin-bottom:12px}
.finca-acciones{display:flex;gap:8px}
.modal-fondo{position:fixed;inset:0;background:rgba(0,0,0,0.4);display:flex;align-items:flex-end;justify-content:center;z-index:1000}
.modal-contenido{width:100%;max-width:480px;max-height:85vh;overflow-y:auto;padding:32px 24px;border-radius:var(--borde-radio-xl) var(--borde-radio-xl) 0 0;display:flex;flex-direction:column;gap:14px}
.modal-titulo{font-family:var(--fuente-display);font-size:var(--tamano-xl);font-weight:700}
textarea.campo-entrada{resize:vertical;min-height:60px}
</style>
