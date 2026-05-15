/* === Almacén de Fincas === */
import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

const fincasDemo = [
  { id: 1, nombre: 'La Esperanza', ubicacion: 'San Carlos', provincia: 'Alajuela', canton: 'San Carlos', tamano: 250, descripcion: 'Finca ganadera principal con pastos mejorados.', estado: 'activa', animales: 4, ultimaActividad: '2023-10-24' },
  { id: 2, nombre: 'El Porvenir', ubicacion: 'Liberia', provincia: 'Guanacaste', canton: 'Liberia', tamano: 180, descripcion: 'Finca especializada en ganado Brahman.', estado: 'activa', animales: 2, ultimaActividad: '2023-10-23' },
  { id: 3, nombre: 'San Rafael', ubicacion: 'Turrialba', provincia: 'Cartago', canton: 'Turrialba', tamano: 120, descripcion: 'Finca lechera con producción artesanal.', estado: 'activa', animales: 2, ultimaActividad: '2023-10-19' }
];

export const useAlmacenFincas = defineStore('fincas', () => {
  const lista = ref([...fincasDemo]);
  const cargando = ref(false);
  const busqueda = ref('');

  const fincasFiltradas = computed(() => {
    if (!busqueda.value) return lista.value;
    const termino = busqueda.value.toLowerCase();
    return lista.value.filter(f =>
      f.nombre.toLowerCase().includes(termino) ||
      f.ubicacion.toLowerCase().includes(termino) ||
      f.provincia.toLowerCase().includes(termino)
    );
  });

  const totalFincas = computed(() => lista.value.length);

  function obtenerPorId(id) {
    return lista.value.find(f => f.id === Number(id));
  }

  function agregarFinca(nueva) {
    const id = Math.max(...lista.value.map(f => f.id)) + 1;
    lista.value.push({ ...nueva, id, animales: 0, estado: 'activa', ultimaActividad: new Date().toISOString().split('T')[0] });
  }

  function actualizarFinca(id, datos) {
    const indice = lista.value.findIndex(f => f.id === Number(id));
    if (indice !== -1) lista.value[indice] = { ...lista.value[indice], ...datos };
  }

  function eliminarFinca(id) {
    lista.value = lista.value.filter(f => f.id !== Number(id));
  }

  return { lista, cargando, busqueda, fincasFiltradas, totalFincas, obtenerPorId, agregarFinca, actualizarFinca, eliminarFinca };
});
