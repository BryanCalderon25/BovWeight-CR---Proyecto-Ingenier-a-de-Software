/* === Almacén de Fincas === */
import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '@/services/api';

export const useAlmacenFincas = defineStore('fincas', () => {
  const lista = ref([]);
  const cargando = ref(false);
  const busqueda = ref('');

  const fincasFiltradas = computed(() => {
    if (!busqueda.value) return lista.value;
    const termino = busqueda.value.toLowerCase();
    return lista.value.filter(f =>
      f.nombre.toLowerCase().includes(termino) ||
      (f.ubicacion && f.ubicacion.toLowerCase().includes(termino))
    );
  });

  const totalFincas = computed(() => lista.value.length);

  async function cargarFincas() {
    cargando.value = true;
    try {
      const respuesta = await api.get('/fincas');
      lista.value = respuesta.data.datos;
    } catch (err) {
      console.error('Error al cargar fincas', err);
    } finally {
      cargando.value = false;
    }
  }

  function obtenerPorId(id) {
    return lista.value.find(f => f.id === Number(id));
  }

  async function agregarFinca(nueva) {
    cargando.value = true;
    try {
      const respuesta = await api.post('/fincas', nueva);
      lista.value.push(respuesta.data.datos);
      return { exito: true };
    } catch (err) {
      console.error('Error detallado al agregar finca:', err.response?.data);
      const mensajeError = err.response?.data?.mensaje || err.response?.data?.message || 'Error desconocido';
      return { exito: false, error: mensajeError };
    } finally {
      cargando.value = false;
    }
  }

  async function actualizarFinca(id, datos) {
    cargando.value = true;
    try {
      const respuesta = await api.put(`/fincas/${id}`, datos);
      const indice = lista.value.findIndex(f => f.id === Number(id));
      if (indice !== -1) lista.value[indice] = respuesta.data.datos;
      return { exito: true };
    } catch (err) {
      return { exito: false, error: err.response?.data?.mensaje || 'Error al actualizar finca' };
    } finally {
      cargando.value = false;
    }
  }

  async function eliminarFinca(id) {
    cargando.value = true;
    try {
      await api.delete(`/fincas/${id}`);
      lista.value = lista.value.filter(f => f.id !== Number(id));
      return { exito: true };
    } catch (err) {
      return { exito: false, error: err.response?.data?.mensaje || 'Error al eliminar finca' };
    } finally {
      cargando.value = false;
    }
  }

  return { 
    lista, cargando, busqueda, fincasFiltradas, totalFincas, 
    cargarFincas, obtenerPorId, agregarFinca, actualizarFinca, eliminarFinca 
  };
});
