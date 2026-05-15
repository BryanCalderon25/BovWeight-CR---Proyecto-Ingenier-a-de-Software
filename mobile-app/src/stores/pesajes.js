/* === Almacén de Pesajes === */
import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

const pesajesDemo = [
  { id: 1, animalId: 1, arete: 'BOV-001', nombreAnimal: 'Luna', raza: 'Brahman', pesoEstimado: 542, margenError: 15, confianza: 94, foto: null, fecha: '2023-10-24', finca: 'La Esperanza' },
  { id: 2, animalId: 2, arete: 'BOV-002', nombreAnimal: 'Tormenta', raza: 'Holstein', pesoEstimado: 680, margenError: 18, confianza: 91, foto: null, fecha: '2023-10-22', finca: 'La Esperanza' },
  { id: 3, animalId: 6, arete: 'BOV-006', nombreAnimal: 'Trueno', raza: 'Brahman', pesoEstimado: 810, margenError: 22, confianza: 88, foto: null, fecha: '2023-10-23', finca: 'El Porvenir' },
  { id: 4, animalId: 3, arete: 'BOV-003', nombreAnimal: 'Estrella', raza: 'Jersey', pesoEstimado: 410, margenError: 12, confianza: 95, foto: null, fecha: '2023-10-20', finca: 'El Porvenir' },
  { id: 5, animalId: 4, arete: 'BOV-004', nombreAnimal: 'Centella', raza: 'Angus', pesoEstimado: 720, margenError: 20, confianza: 89, foto: null, fecha: '2023-10-18', finca: 'La Esperanza' },
  { id: 6, animalId: 7, arete: 'BOV-007', nombreAnimal: 'Paloma', raza: 'Holstein', pesoEstimado: 385, margenError: 10, confianza: 96, foto: null, fecha: '2023-10-21', finca: 'La Esperanza' },
  { id: 7, animalId: 8, arete: 'BOV-008', nombreAnimal: 'Rayo', raza: 'Angus', pesoEstimado: 695, margenError: 19, confianza: 90, foto: null, fecha: '2023-10-19', finca: 'San Rafael' },
  { id: 8, animalId: 1, arete: 'BOV-001', nombreAnimal: 'Luna', raza: 'Brahman', pesoEstimado: 530, margenError: 14, confianza: 93, foto: null, fecha: '2023-09-24', finca: 'La Esperanza' },
  { id: 9, animalId: 1, arete: 'BOV-001', nombreAnimal: 'Luna', raza: 'Brahman', pesoEstimado: 518, margenError: 16, confianza: 91, foto: null, fecha: '2023-08-24', finca: 'La Esperanza' }
];

export const useAlmacenPesajes = defineStore('pesajes', () => {
  const lista = ref([...pesajesDemo]);
  const cargando = ref(false);
  const procesando = ref(false);
  const resultadoActual = ref(null);

  const ultimosPesajes = computed(() => {
    return [...lista.value].sort((a, b) => new Date(b.fecha) - new Date(a.fecha)).slice(0, 5);
  });

  const pesoTotalHato = computed(() => {
    const animalesUnicos = {};
    lista.value.forEach(p => {
      if (!animalesUnicos[p.animalId] || new Date(p.fecha) > new Date(animalesUnicos[p.animalId].fecha)) {
        animalesUnicos[p.animalId] = p;
      }
    });
    return Object.values(animalesUnicos).reduce((sum, p) => sum + p.pesoEstimado, 0);
  });

  const datosTendencia = computed(() => {
    const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun'];
    const datos = [4200, 4350, 4500, 4680, 4820, 4950];
    return { etiquetas: meses, valores: datos };
  });

  function obtenerHistorialAnimal(animalId) {
    return lista.value
      .filter(p => p.animalId === Number(animalId))
      .sort((a, b) => new Date(b.fecha) - new Date(a.fecha));
  }

  async function estimarPeso(imagenBase64, datosAnimal) {
    procesando.value = true;
    try {
      /* Simulación del procesamiento ML */
      await new Promise(r => setTimeout(r, 3000));
      
      const pesoBase = {
        'Brahman': 520, 'Holstein': 600, 'Jersey': 380,
        'Angus': 650, 'Pardo Suizo': 550
      };
      const base = pesoBase[datosAnimal.raza] || 500;
      const variacion = (Math.random() - 0.5) * 100;
      const pesoEstimado = Math.round(base + variacion);
      const margenError = Math.round(10 + Math.random() * 15);
      const confianza = Math.round(85 + Math.random() * 12);

      const resultado = {
        id: Math.max(...lista.value.map(p => p.id)) + 1,
        animalId: datosAnimal.id,
        arete: datosAnimal.arete,
        nombreAnimal: datosAnimal.nombre,
        raza: datosAnimal.raza,
        pesoEstimado,
        margenError,
        confianza,
        foto: imagenBase64,
        fecha: new Date().toISOString().split('T')[0],
        finca: datosAnimal.fincaNombre || 'Sin finca'
      };

      lista.value.unshift(resultado);
      resultadoActual.value = resultado;
      return resultado;
    } finally {
      procesando.value = false;
    }
  }

  return {
    lista, cargando, procesando, resultadoActual,
    ultimosPesajes, pesoTotalHato, datosTendencia,
    obtenerHistorialAnimal, estimarPeso
  };
});
