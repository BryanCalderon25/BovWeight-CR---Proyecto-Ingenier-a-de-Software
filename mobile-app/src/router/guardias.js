/* === Guardias de navegación === */
import { useAlmacenAuth } from '@/stores/auth.js'

/**
 * Protege rutas que requieren autenticación
 * Redirige al login si no hay sesión activa
 * Restringe el acceso de invitados a únicamente su finca autorizada
 */
export function protegerRuta(to, from, next) {

  const almacenAuth = useAlmacenAuth()

  // No autenticado
  if (!almacenAuth.estaAutenticado) {
    return next('/login')
  }

  // Bloquear rutas de escritura veterinaria para no-veterinarios
  const rutasVetEscritura = ['NuevaAtencion', 'EditarAtencion']
  if (rutasVetEscritura.includes(to.name)) {
    const esVet = almacenAuth.usuario?.guest_role === 'veterinario' || almacenAuth.rolUsuario === 'veterinario'
    if (!esVet) {
      console.warn('Acceso denegado a ruta de escritura veterinaria.')
      const idAnimal = to.params.id || to.params.animalId
      if (idAnimal) {
        return next(`/app/veterinario/animal/${idAnimal}`)
      }
      return next('/app/inicio')
    }
  }

  // Usuario invitado
  if (almacenAuth.rolUsuario === 'invitado') {

    const invitedFarmId = almacenAuth.usuario?.invited_farm_id
    const guestRole     = almacenAuth.usuario?.guest_role

    // 🔥 VALIDACIÓN CRÍTICA
    if (!invitedFarmId) {
      console.error('Invited farm ID no existe')
      return next('/login')
    }

    // Veterinario: permitir acceso completo al módulo veterinario
    if (guestRole === 'veterinario') {
      const rutasVet = ['HistorialVeterinario', 'VetAnimalesFinca', 'DetalleVeterinario', 'NuevaAtencion', 'EditarAtencion']
      if (rutasVet.includes(to.name)) return next()
      // Redirigir al home del veterinario
      if (to.fullPath === '/app/veterinario') return next()
      return next('/app/veterinario')
    }

    // Invitado normal: permitir acceso a su finca
    if (
      to.name === 'DetalleFinca' &&
      parseInt(to.params.id) === parseInt(invitedFarmId)
    ) {
      return next()
    }

    // Permitir animales
    if (to.name === 'DetalleAnimal') {
      return next()
    }

    // Evitar loop infinito
    if (to.fullPath === `/app/fincas/${invitedFarmId}`) {
      return next()
    }

    // Redirección segura
    return next(`/app/fincas/${invitedFarmId}`)
  }

  // Usuario normal
  return next()
}