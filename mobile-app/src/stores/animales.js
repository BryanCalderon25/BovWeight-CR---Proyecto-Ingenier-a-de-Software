/* === Almacén de Animales === */
import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

/* Datos de demostración de animales */
const animalesDemo = [
  { id: 1, arete: 'BOV-001', nombre: 'Luna', raza: 'Brahman', sexo: 'Hembra', edad: '3 años', estado: 'activo', fincaId: 1, fincaNombre: 'La Esperanza', pesoActual: 542, foto: null, ultimoPesaje: '2023-10-24' },
  { id: 2, arete: 'BOV-002', nombre: 'Tormenta', raza: 'Holstein', sexo: 'Macho', edad: '4 años', estado: 'activo', fincaId: 1, fincaNombre: 'La Esperanza', pesoActual: 680, foto: null, ultimoPesaje: '2023-10-22' },
  { id: 3, arete: 'BOV-003', nombre: 'Estrella', raza: 'Jersey', sexo: 'Hembra', edad: '2 años', estado: 'activo', fincaId: 2, fincaNombre: 'El Porvenir', pesoActual: 410, foto: null, ultimoPesaje: '2023-10-20' },
  { id: 4, arete: 'BOV-004', nombre: 'Centella', raza: 'Angus', sexo: 'Macho', edad: '5 años', estado: 'activo', fincaId: 1, fincaNombre: 'La Esperanza', pesoActual: 720, foto: null, ultimoPesaje: '2023-10-18' },
  { id: 5, arete: 'BOV-005', nombre: 'Mariposa', raza: 'Pardo Suizo', sexo: 'Hembra', edad: '3 años', estado: 'inactivo', fincaId: 3, fincaNombre: 'San Rafael', pesoActual: 495, foto: null, ultimoPesaje: '2023-09-15' },
  { id: 6, arete: 'BOV-006', nombre: 'Trueno', raza: 'Brahman', sexo: 'Macho', edad: '6 años', estado: 'activo', fincaId: 2, fincaNombre: 'El Porvenir', pesoActual: 810, foto: null, ultimoPesaje: '2023-10-23' },
  { id: 7, arete: 'BOV-007', nombre: 'Paloma', raza: 'Holstein', sexo: 'Hembra', edad: '2 años', estado: 'activo', fincaId: 1, fincaNombre: 'La Esperanza', pesoActual: 385, foto: null, ultimoPesaje: '2023-10-21' },
  { id: 8, arete: 'BOV-008', nombre: 'Rayo', raza: 'Angus', sexo: 'Macho', edad: '4 años', estado: 'activo', fincaId: 3, fincaNombre: 'San Rafael', pesoActual: 695, foto: null, ultimoPesaje: '2023-10-19' }
];

export const useAlmacenAnimales = defineStore('animales', () => {
  const lista = ref([...animalesDemo]);
  const cargando = ref(false);
  const busqueda = ref('');
  const filtroRaza = ref('');
  const filtroEstado = ref('');
  const filtroFinca = ref('');

  /* Getters */
  const animalesFiltrados = computed(() => {
    let resultado = lista.value;
    if (busqueda.value) {
      const termino = busqueda.value.toLowerCase();
      resultado = resultado.filter(a =>
        a.nombre.toLowerCase().includes(termino) ||
        a.arete.toLowerCase().includes(termino) ||
        a.raza.toLowerCase().includes(termino)
      );
    }
    if (filtroRaza.value) resultado = resultado.filter(a => a.raza === filtroRaza.value);
    if (filtroEstado.value) resultado = resultado.filter(a => a.estado === filtroEstado.value);
    if (filtroFinca.value) resultado = resultado.filter(a => a.fincaId === Number(filtroFinca.value));
    return resultado;
  });

  const totalAnimales = computed(() => lista.value.length);
  const animalesActivos = computed(() => lista.value.filter(a => a.estado === 'activo').length);
  const animalesInactivos = computed(() => lista.value.filter(a => a.estado === 'inactivo').length);
  const pesoPromedio = computed(() => {
    const activos = lista.value.filter(a => a.estado === 'activo');
    if (!activos.length) return 0;
    return Math.round(activos.reduce((sum, a) => sum + a.pesoActual, 0) / activos.length);
  });
  const razasDisponibles = computed(() => [...new Set(lista.value.map(a => a.raza))]);

  /* Acciones */
  function obtenerPorId(id) {
    return lista.value.find(a => a.id === Number(id));
  }

  function agregarAnimal(nuevoAnimal) {
    const id = Math.max(...lista.value.map(a => a.id)) + 1;
    lista.value.push({ ...nuevoAnimal, id, ultimoPesaje: new Date().toISOString().split('T')[0] });
  }

  function actualizarAnimal(id, datos) {
    const indice = lista.value.findIndex(a => a.id === Number(id));
    if (indice !== -1) lista.value[indice] = { ...lista.value[indice], ...datos };
  }

  function eliminarAnimal(id) {
    lista.value = lista.value.filter(a => a.id !== Number(id));
  }

  function existeArete(arete, excluirId = null) {
    return lista.value.some(a => a.arete === arete && a.id !== excluirId);
  }

  return {
    lista, cargando, busqueda, filtroRaza, filtroEstado, filtroFinca,
    animalesFiltrados, totalAnimales, animalesActivos, animalesInactivos,
    pesoPromedio, razasDisponibles,
    obtenerPorId, agregarAnimal, actualizarAnimal, eliminarAnimal, existeArete
  };
});
