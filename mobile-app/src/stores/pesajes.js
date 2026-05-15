/* === Almacén de Pesajes === */
import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import api from '@/services/api';

export const useAlmacenPesajes = defineStore('pesajes', () => {
  const lista = ref([]);
  const cargando = ref(false);
  const procesando = ref(false);
  const resultadoActual = ref(null);
  const colaOffline = ref(JSON.parse(localStorage.getItem('bw_cola_offline') || '[]'));

  const ultimosPesajes = computed(() => {
    return [...lista.value].sort((a, b) => new Date(b.fecha_pesaje) - new Date(a.fecha_pesaje)).slice(0, 5);
  });

  const pesoTotalHato = computed(() => {
    if (!lista.value.length) return 0;
    const animalesUnicos = {};
    lista.value.forEach(p => {
      if (!animalesUnicos[p.animal_id] || new Date(p.fecha_pesaje) > new Date(animalesUnicos[p.animal_id].fecha_pesaje)) {
        animalesUnicos[p.animal_id] = p;
      }
    });
    return Object.values(animalesUnicos).reduce((sum, p) => sum + Number(p.peso_estimado), 0);
  });

  async function cargarHistorialAnimal(animalId) {
    cargando.value = true;
    try {
      const respuesta = await api.get(`/animales/${animalId}/pesajes`);
      lista.value = respuesta.data.datos;
    } catch (err) {
      console.error('Error al cargar historial', err);
    } finally {
      cargando.value = false;
    }
  }

  async function estimarPeso(imagenBlob, animalId) {
    procesando.value = true;
    try {
      const formData = new FormData();
      formData.append('animal_id', animalId);
      formData.append('imagen', imagenBlob);

      const respuesta = await api.post('/ml/estimar-peso', formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      });

      const { datos, disclaimer } = respuesta.data;
      resultadoActual.value = {
        ...datos.registro,
        detalles_modelo: datos.detalles_modelo,
        disclaimer
      };

      // Añadir al inicio de la lista
      lista.value.unshift(datos.registro);
      
      return { exito: true, datos: resultadoActual.value };
    } catch (err) {
      // Si falla por conexión, guardar en cola offline
      if (!navigator.onLine || err.code === 'ERR_NETWORK') {
        const pesajeOffline = {
          animal_id: animalId,
          peso_estimado: 0, // Se estimará al sincronizar o es manual
          fecha_pesaje: new Date().toISOString().split('T')[0],
          notas: 'Pendiente de sincronización offline',
          offline: true
        };
        colaOffline.value.push(pesajeOffline);
        localStorage.setItem('bw_cola_offline', JSON.stringify(colaOffline.value));
        return { exito: false, offline: true, mensaje: 'Guardado localmente para sincronizar luego.' };
      }
      return { exito: false, error: err.response?.data?.mensaje || 'Error al procesar estimación' };
    } finally {
      procesando.value = false;
    }
  }

  async function sincronizarOffline() {
    if (colaOffline.value.length === 0) return { exito: true, mensaje: 'Nada para sincronizar' };
    
    cargando.value = true;
    try {
      const respuesta = await api.post('/sincronizacion/pesajes', {
        registros: colaOffline.value
      });
      
      if (respuesta.data.sincronizados > 0) {
        colaOffline.value = [];
        localStorage.removeItem('bw_cola_offline');
        return { exito: true, mensaje: `Sincronizados ${respuesta.data.sincronizados} registros.` };
      }
      return { exito: false, error: 'No se sincronizaron registros.' };
    } catch (err) {
      return { exito: false, error: 'Error al sincronizar datos offline.' };
    } finally {
      cargando.value = false;
    }
  }

  return {
    lista, cargando, procesando, resultadoActual, colaOffline,
    ultimosPesajes, pesoTotalHato,
    cargarHistorialAnimal, estimarPeso, sincronizarOffline
  };
});
