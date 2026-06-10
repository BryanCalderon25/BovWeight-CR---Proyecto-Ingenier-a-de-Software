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

  // Usuario invitado
  if (almacenAuth.rolUsuario === 'invitado') {

    const invitedFarmId = almacenAuth.usuario?.invited_farm_id

    // 🔥 VALIDACIÓN CRÍTICA
    if (!invitedFarmId) {
      console.error('Invited farm ID no existe')

      return next('/login')
    }

    // Permitir acceso a su finca
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
 * Evita que usuarios autenticados ingresen a rutas de invitados (login, registro)
 */
export function evitarAutenticados(to, from, next) {
  const almacenAuth = useAlmacenAuth()

  if (almacenAuth.estaAutenticado) {
    return next('/app/inicio')
  }
  return next()
}