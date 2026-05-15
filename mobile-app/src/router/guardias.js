/* === Guardias de navegación === */
import { useAlmacenAuth } from '@/stores/auth.js';

/**
 * Protege rutas que requieren autenticación
 * Redirige al login si no hay sesión activa
 */
export function protegerRuta(to, from, next) {
  const almacenAuth = useAlmacenAuth();
  
  if (almacenAuth.estaAutenticado) {
    next();
  } else {
    next('/login');
  }
}
