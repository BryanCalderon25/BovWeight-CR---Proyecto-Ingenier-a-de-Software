/* === Guardias de navegación === */
import { useAlmacenAuth } from '@/stores/auth.js';

/**
 * Protege rutas que requieren autenticación
 * Redirige al login si no hay sesión activa
 * Restringe el acceso de invitados a únicamente su finca autorizada
 */
export function protegerRuta(to, from, next) {
  const almacenAuth = useAlmacenAuth();
  
  if (almacenAuth.estaAutenticado) {
    if (almacenAuth.rolUsuario === 'invitado') {
      const invitedFarmId = almacenAuth.usuario?.invited_farm_id;
      
      // Permitir acceder al detalle de su finca autorizada
      if (to.name === 'DetalleFinca' && parseInt(to.params.id) === parseInt(invitedFarmId)) {
        next();
        return;
      }
      
      // Permitir acceder al detalle de animales (la API restringe los animales que pertenecen a otras fincas)
      if (to.name === 'DetalleAnimal') {
        next();
        return;
      }
      
      // Bloquear todo lo demás y redirigir directamente a la finca autorizada
      next(`/app/fincas/${invitedFarmId}`);
    } else {
      next();
    }
  } else {
    next('/login');
  }
}
