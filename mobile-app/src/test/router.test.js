/**
 * router.test.js — Pruebas unitarias del enrutador Vue Router
 *
 * Verifica que las rutas principales estén definidas correctamente,
 * que las rutas protegidas tengan guard y que las rutas públicas no lo tengan.
 */

import { describe, it, expect, beforeEach, vi } from 'vitest';

// ── Mock de guardias para evitar dependencias externas ──────────
vi.mock('@/router/guardias.js', () => ({
  protegerRuta:    vi.fn((to, from, next) => next && next()),
  evitarAutenticados: vi.fn((to, from, next) => next && next()),
}));

// ── Mock de Ionic Vue Router ────────────────────────────────────
vi.mock('@ionic/vue-router', () => ({
  createRouter: vi.fn((config) => ({
    ...config,
    // Simular getRoutes() retornando las rutas del config
    getRoutes: () => config.routes || [],
  })),
  createWebHistory: vi.fn(() => 'web-history'),
}));

// ─────────────────────────────────────────────────────────────────
describe('Router — Rutas y Guards', () => {

  let rutas;

  beforeEach(async () => {
    vi.resetModules();
    const routerModule = await import('@/router/index.js');
    rutas = routerModule.default?.options?.routes ?? routerModule.default?.getRoutes?.() ?? [];
  });

  // ── Ayudantes ──────────────────────────────────────────────────

  /** Encuentra una ruta por su path exacto (top-level o hija). */
  function encontrarRuta(path, lista = rutas) {
    for (const ruta of lista) {
      if (ruta.path === path) return ruta;
      if (ruta.children) {
        const encontrada = encontrarRuta(path, ruta.children);
        if (encontrada) return encontrada;
      }
    }
    return null;
  }

  /** Encuentra una ruta por su nombre. */
  function encontrarPorNombre(nombre, lista = rutas) {
    for (const ruta of lista) {
      if (ruta.name === nombre) return ruta;
      if (ruta.children) {
        const encontrada = encontrarPorNombre(nombre, ruta.children);
        if (encontrada) return encontrada;
      }
    }
    return null;
  }

  // ── Estructura general ─────────────────────────────────────────

  it('debería tener al menos 10 rutas definidas', () => {
    // Contar todas las rutas (incluyendo hijas)
    function contarRutas(lista) {
      return lista.reduce((acc, r) => acc + 1 + (r.children ? contarRutas(r.children) : 0), 0);
    }
    expect(contarRutas(rutas)).toBeGreaterThanOrEqual(10);
  });

  // ── Rutas públicas (sin guard) ─────────────────────────────────

  it('la ruta /splash no debería tener guard beforeEnter', () => {
    const ruta = encontrarRuta('/splash');
    expect(ruta).toBeDefined();
    expect(ruta?.beforeEnter).toBeUndefined();
  });

  it('la ruta /login debería tener el guard evitarAutenticados', () => {
    const ruta = encontrarRuta('/login');
    expect(ruta).toBeDefined();
    expect(ruta?.beforeEnter).toBeDefined();
  });

  it('la ruta /olvide-contrasena debería tener guard', () => {
    const ruta = encontrarRuta('/olvide-contrasena');
    expect(ruta).toBeDefined();
    expect(ruta?.beforeEnter).toBeDefined();
  });

  it('la ruta /restablecer-contrasena debería tener guard', () => {
    const ruta = encontrarRuta('/restablecer-contrasena');
    expect(ruta).toBeDefined();
    expect(ruta?.beforeEnter).toBeDefined();
  });

  // ── Rutas protegidas (deben tener guard) ──────────────────────

  it('la ruta /app/reportes debería estar protegida', () => {
    const ruta = encontrarRuta('/app/reportes');
    expect(ruta).toBeDefined();
    expect(ruta?.beforeEnter).toBeDefined();
  });

  it('la ruta /app/configuracion debería estar protegida', () => {
    const ruta = encontrarRuta('/app/configuracion');
    expect(ruta).toBeDefined();
    expect(ruta?.beforeEnter).toBeDefined();
  });

  it('la ruta /app/admin debería estar protegida', () => {
    const ruta = encontrarRuta('/app/admin');
    expect(ruta).toBeDefined();
    expect(ruta?.beforeEnter).toBeDefined();
  });

  it('la ruta /app/animales/:id debería estar protegida', () => {
    const ruta = encontrarRuta('/app/animales/:id');
    expect(ruta).toBeDefined();
    expect(ruta?.beforeEnter).toBeDefined();
  });

  // ── Módulo veterinario ─────────────────────────────────────────

  it('la ruta HistorialVeterinario debería estar definida', () => {
    const ruta = encontrarPorNombre('HistorialVeterinario');
    expect(ruta).toBeDefined();
  });

  it('la ruta DetalleVeterinario debería estar definida', () => {
    const ruta = encontrarPorNombre('DetalleVeterinario');
    expect(ruta).toBeDefined();
  });

  it('la ruta NuevaAtencion debería estar definida', () => {
    const ruta = encontrarPorNombre('NuevaAtencion');
    expect(ruta).toBeDefined();
  });

  // ── Rutas principales del TabsLayout ──────────────────────────

  it('el TabsLayout (/app/) debería tener las rutas hija principales', () => {
    const appRuta = rutas.find(r => r.path === '/app/');
    expect(appRuta).toBeDefined();
    expect(appRuta?.children).toBeDefined();

    const hijas = appRuta.children;
    const nombres = hijas.map(r => r.name).filter(Boolean);

    expect(nombres).toContain('Inicio');
    expect(nombres).toContain('Animales');
    expect(nombres).toContain('Pesar');
    expect(nombres).toContain('Historial');
    expect(nombres).toContain('Fincas');
  });

  it('la ruta raíz / debería redirigir a /splash', () => {
    const raiz = encontrarRuta('/');
    expect(raiz).toBeDefined();
    expect(raiz?.redirect).toBe('/splash');
  });

  // ── Rutas de información legal ─────────────────────────────────

  it('deberían existir rutas para Términos y Política de Privacidad', () => {
    const terminos  = encontrarPorNombre('Terminos');
    const politica  = encontrarPorNombre('PoliticaPrivacidad');
    const acercaDe  = encontrarPorNombre('AcercaDe');

    expect(terminos).toBeDefined();
    expect(politica).toBeDefined();
    expect(acercaDe).toBeDefined();
  });
});
