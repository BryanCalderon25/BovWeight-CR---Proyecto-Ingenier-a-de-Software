/* === Enrutador principal de BovWeight CR === */
import { createRouter, createWebHistory } from '@ionic/vue-router';

/* Importar guardias de navegación */
import { protegerRuta, evitarAutenticados } from './guardias.js';

const rutas = [
  {
    path: '/',
    redirect: '/splash'
  },
  {
    path: '/splash',
    name: 'Splash',
    component: () => import('@/views/SplashScreen.vue')
  },
  {
    path: '/bienvenida',
    name: 'Bienvenida',
    component: () => import('@/views/OnboardingView.vue')
  },
  {
    path: '/login',
    name: 'Login',
    component: () => import('@/views/LoginView.vue'),
    beforeEnter: evitarAutenticados
  },
  {
    path: '/registro',
    name: 'Registro',
    component: () => import('@/views/RegisterView.vue'),
    beforeEnter: evitarAutenticados
  },
  {
    path: '/olvide-contrasena',
    name: 'OlvideContrasena',
    component: () => import('@/views/OlvideContrasenaView.vue'),
    beforeEnter: evitarAutenticados
  },
  {
    path: '/restablecer-contrasena',
    name: 'RestablecerContrasena',
    component: () => import('@/views/RestablecerContrasenaView.vue'),
    beforeEnter: evitarAutenticados
  },
  {
    path: '/invitado/acceso/:token',
    name: 'AccesoInvitado',
    component: () => import('@/views/AccesoInvitadoView.vue')
  },
  {
    /* Rutas principales con tabs */
    path: '/app/',
    component: () => import('@/views/TabsLayout.vue'),
    beforeEnter: protegerRuta,
    children: [
      {
        path: '',
        redirect: '/app/inicio'
      },
      {
        path: 'inicio',
        name: 'Inicio',
        component: () => import('@/views/DashboardView.vue')
      },
      {
        path: 'animales',
        name: 'Animales',
        component: () => import('@/views/AnimalesView.vue')
      },
      {
        path: 'pesar',
        name: 'Pesar',
        component: () => import('@/views/PesajeView.vue')
      },
      {
        path: 'historial',
        name: 'Historial',
        component: () => import('@/views/HistorialView.vue')
      },
      {
        path: 'fincas',
        name: 'Fincas',
        component: () => import('@/views/FincasView.vue')
      }
    ]
  },
  {
    path: '/app/animales/:id',
    name: 'DetalleAnimal',
    component: () => import('@/views/AnimalDetalleView.vue'),
    beforeEnter: protegerRuta
  },
  {
    path: '/app/fincas/:id',
    name: 'DetalleFinca',
    component: () => import('@/views/FincaDetalleView.vue'),
    beforeEnter: protegerRuta
  },
  {
    path: '/app/reportes',
    name: 'Reportes',
    component: () => import('@/views/ReportesView.vue'),
    beforeEnter: protegerRuta
  },
  {
    path: '/app/configuracion',
    name: 'Configuracion',
    component: () => import('@/views/ConfiguracionView.vue'),
    beforeEnter: protegerRuta
  },
  {
    path: '/app/admin',
    name: 'AdminDashboard',
    component: () => import('@/views/AdminDashboardView.vue'),
    beforeEnter: protegerRuta
  },
  // === MÓDULO VETERINARIO ===
  {
    path: '/app/veterinario',
    name: 'HistorialVeterinario',
    component: () => import('@/views/HistorialVeterinarioView.vue'),
    beforeEnter: protegerRuta
  },
  {
    path: '/app/veterinario/finca/:farmId',
    name: 'VetAnimalesFinca',
    component: () => import('@/views/VetAnimalesFincaView.vue'),
    beforeEnter: protegerRuta
  },
  {
    path: '/app/veterinario/animal/:id',
    name: 'DetalleVeterinario',
    component: () => import('@/views/DetalleVeterinarioView.vue'),
    beforeEnter: protegerRuta
  },
  {
    path: '/app/veterinario/animal/:id/nuevo',
    name: 'NuevaAtencion',
    component: () => import('@/views/FormularioVeterinarioView.vue'),
    beforeEnter: protegerRuta
  },
  {
    path: '/app/veterinario/registro/:regId/editar',
    name: 'EditarAtencion',
    component: () => import('@/views/FormularioVeterinarioView.vue'),
    beforeEnter: protegerRuta
  }
];

export const enrutador = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: rutas
});

export default enrutador;
