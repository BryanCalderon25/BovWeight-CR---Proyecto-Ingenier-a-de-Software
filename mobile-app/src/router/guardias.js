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

  // Proteger rutas de admin
  if (to.path.startsWith('/app/admin')) {
    if (almacenAuth.rolUsuario !== 'admin') {
      console.warn('Acceso denegado a ruta de administración.')
      return next('/app/inicio')
    }
  }

  // Bloquear a los administradores de ingresar a las vistas del ganadero
  const rutasGanadero = ['Inicio', 'Fincas', 'Animales', 'Pesar', 'Historial', 'DetalleFinca', 'DetalleAnimal', 'Reportes'];
  if (rutasGanadero.includes(to.name) && almacenAuth.rolUsuario === 'admin') {
    console.warn('Acceso denegado: El administrador no puede ingresar a vistas de ganadero.')
    return next('/app/admin')
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

  // Usuario Veterinario (rol directo o rol de invitado)
  const esVeterinario = almacenAuth.rolUsuario === 'veterinario' || almacenAuth.usuario?.guest_role === 'veterinario';
  if (esVeterinario) {
    const rutasVet = ['HistorialVeterinario', 'VetAnimalesFinca', 'DetalleVeterinario', 'NuevaAtencion', 'EditarAtencion'];
    if (rutasVet.includes(to.name)) {
      return next();
    }
    // Redirigir al home del veterinario
    return next('/app/veterinario');
  }

  // Usuario invitado (no veterinario)
  if (almacenAuth.rolUsuario === 'invitado') {

    const invitedFarmId = almacenAuth.usuario?.invited_farm_id

    // 🔥 VALIDACIÓN CRÍTICA
    if (!invitedFarmId) {
      console.error('Invited farm ID no existe')
      return next('/login')
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

/**
 * Evita que usuarios autenticados ingresen a rutas de invitados (login, registro, recuperación)
 */
export function evitarAutenticados(to, from, next) {
  const almacenAuth = useAlmacenAuth()

  if (almacenAuth.estaAutenticado) {
    if (almacenAuth.rolUsuario === 'admin') {
      return next('/app/admin')
    } else if (almacenAuth.rolUsuario === 'veterinario' || almacenAuth.usuario?.guest_role === 'veterinario') {
      return next('/app/veterinario')
    } else if (almacenAuth.rolUsuario === 'invitado') {
      return next(`/app/fincas/${almacenAuth.usuario.invited_farm_id}`)
    }
    return next('/app/inicio')
  }
  return next()
}