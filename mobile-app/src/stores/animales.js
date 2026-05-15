/* === Almacén de Animales === */
import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '@/services/api';

export const useAlmacenAnimales = defineStore('animales', () => {
  const lista = ref([]);
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
        (a.nombre && a.nombre.toLowerCase().includes(termino)) ||
        a.arete.toLowerCase().includes(termino) ||
        (a.raza && a.raza.toLowerCase().includes(termino))
      );
    }
    if (filtroRaza.value) resultado = resultado.filter(a => a.raza === filtroRaza.value);
    // Nota: El backend usa soft deletes, 'estado' podría ser manejado de otra forma si es necesario
    if (filtroFinca.value) resultado = resultado.filter(a => a.farm_id === Number(filtroFinca.value));
    return resultado;
  });

  const totalAnimales = computed(() => lista.value.length);
  const pesoPromedio = computed(() => {
    if (!lista.value.length) return 0;
    const conPeso = lista.value.filter(a => a.peso_actual);
    if (!conPeso.length) return 0;
    return Math.round(conPeso.reduce((sum, a) => sum + Number(a.peso_actual), 0) / conPeso.length);
  });
  const razasDisponibles = computed(() => [...new Set(lista.value.map(a => a.raza).filter(r => r))]);

  /* Acciones */
  async function cargarAnimalesPorFinca(fincaId) {
    cargando.value = true;
    try {
      const respuesta = await api.get(`/fincas/${fincaId}/animales`);
      lista.value = respuesta.data.datos;
    } catch (err) {
      console.error('Error al cargar animales', err);
    } finally {
      cargando.value = false;
    }
  }

  function obtenerPorId(id) {
    return lista.value.find(a => a.id === Number(id));
  }

  async function agregarAnimal(nuevoAnimal) {
    cargando.value = true;
    try {
      const respuesta = await api.post('/animales', nuevoAnimal);
      lista.value.push(respuesta.data.datos);
      return { exito: true };
    } catch (err) {
      return { exito: false, error: err.response?.data?.mensaje || 'Error al registrar animal' };
    } finally {
      cargando.value = false;
    }
  }

  async function actualizarAnimal(id, datos) {
    cargando.value = true;
    try {
      const respuesta = await api.put(`/animales/${id}`, datos);
      const indice = lista.value.findIndex(a => a.id === Number(id));
      if (indice !== -1) lista.value[indice] = respuesta.data.datos;
      return { exito: true };
    } catch (err) {
      return { exito: false, error: err.response?.data?.mensaje || 'Error al actualizar animal' };
    } finally {
      cargando.value = false;
    }
  }

  async function eliminarAnimal(id) {
    cargando.value = true;
    try {
      await api.delete(`/animales/${id}`);
      lista.value = lista.value.filter(a => a.id !== Number(id));
      return { exito: true };
    } catch (err) {
      return { exito: false, error: err.response?.data?.mensaje || 'Error al eliminar animal' };
    } finally {
      cargando.value = false;
    }
  }

  return {
    lista, cargando, busqueda, filtroRaza, filtroEstado, filtroFinca,
    animalesFiltrados, totalAnimales, pesoPromedio, razasDisponibles,
    cargarAnimalesPorFinca, obtenerPorId, agregarAnimal, actualizarAnimal, eliminarAnimal
  };
});
