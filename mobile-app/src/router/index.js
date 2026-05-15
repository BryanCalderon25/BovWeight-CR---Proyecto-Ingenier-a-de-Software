/* === Enrutador principal de BovWeight CR === */
import { createRouter, createWebHistory } from '@ionic/vue-router';

/* Importar guardias de navegación */
import { protegerRuta } from './guardias.js';

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
    component: () => import('@/views/LoginView.vue')
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
    path: '/app/fincas',
    name: 'Fincas',
    component: () => import('@/views/FincasView.vue'),
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
  }
];

export const enrutador = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes: rutas
});

export default enrutador;
