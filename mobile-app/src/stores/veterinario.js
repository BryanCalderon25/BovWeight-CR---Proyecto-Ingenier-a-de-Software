/* === Almacén del Módulo Veterinario === */
import { defineStore } from 'pinia';
import { ref } from 'vue';
import api from '@/services/api';

export const useAlmacenVeterinario = defineStore('veterinario', () => {
  const registros = ref([]);
  const cargando  = ref(false);

  /**
   * Cargar todos los registros veterinarios de un animal.
   */
  async function cargarRegistros(animalId) {
    cargando.value = true;
    try {
      const respuesta = await api.get(`/animales/${animalId}/veterinario`);
      registros.value = respuesta.data.datos;
    } catch (err) {
      console.error('Error al cargar registros veterinarios:', err);
      registros.value = [];
    } finally {
      cargando.value = false;
    }
  }

  /**
   * Crear un nuevo registro veterinario para un animal.
   */
  async function crearRegistro(animalId, datos) {
    cargando.value = true;
    try {
      const respuesta = await api.post(`/animales/${animalId}/veterinario`, datos);
      registros.value.unshift(respuesta.data.datos);
      return { exito: true, datos: respuesta.data.datos };
    } catch (err) {
      const mensaje = err.response?.data?.mensaje
        || Object.values(err.response?.data?.errors || {}).flat().join(' ')
        || 'Error al guardar el registro veterinario';
      return { exito: false, error: mensaje };
    } finally {
      cargando.value = false;
    }
  }

  /**
   * Actualizar un registro veterinario existente.
   */
  async function actualizarRegistro(id, datos) {
    cargando.value = true;
    try {
      const respuesta = await api.put(`/veterinario/${id}`, datos);
      const indice = registros.value.findIndex(r => r.id === Number(id));
      if (indice !== -1) registros.value[indice] = respuesta.data.datos;
      return { exito: true, datos: respuesta.data.datos };
    } catch (err) {
      const mensaje = err.response?.data?.mensaje
        || Object.values(err.response?.data?.errors || {}).flat().join(' ')
        || 'Error al actualizar el registro veterinario';
      return { exito: false, error: mensaje };
    } finally {
      cargando.value = false;
    }
  }

  /**
   * Eliminar un registro veterinario.
   */
  async function eliminarRegistro(id) {
    cargando.value = true;
    try {
      await api.delete(`/veterinario/${id}`);
      registros.value = registros.value.filter(r => r.id !== Number(id));
      return { exito: true };
    } catch (err) {
      return { exito: false, error: err.response?.data?.mensaje || 'Error al eliminar' };
    } finally {
      cargando.value = false;
    }
  }

  /**
   * Generar y descargar el Reporte Veterinario PDF de un animal.
   * Función reutilizada desde ReportesView y DetalleVeterinarioView — punto único de lógica (DRY).
   */
  async function generarReportePDF(animalId) {
    try {
      const respuesta = await api.get(`/animales/${animalId}/reporte-veterinario`, {
        responseType: 'blob',
      });
      return { exito: true, blob: respuesta.data };
    } catch (err) {
      console.error('Error al generar reporte veterinario:', err);
      return { exito: false, error: 'No se pudo generar el Reporte Veterinario. Verifique que el animal existe.' };
    }
  }

  /**
   * Descargar un blob PDF con el nombre de archivo dado.
   */
  function descargarBlob(blob, nombreArchivo) {
    const url  = window.URL.createObjectURL(new Blob([blob], { type: 'application/pdf' }));
    const link = document.createElement('a');
    link.href  = url;
    link.setAttribute('download', nombreArchivo);
    document.body.appendChild(link);
    link.click();
    link.parentNode.removeChild(link);
    window.URL.revokeObjectURL(url);
  }

  function limpiar() {
    registros.value = [];
  }

  return {
    registros, cargando,
    cargarRegistros, crearRegistro, actualizarRegistro, eliminarRegistro,
    generarReportePDF, descargarBlob, limpiar,
  };
});
