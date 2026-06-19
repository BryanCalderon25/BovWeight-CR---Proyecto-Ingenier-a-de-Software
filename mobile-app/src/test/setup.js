/**
 * setup.js — Configuración global del entorno de pruebas Vitest
 * Se ejecuta antes de cada archivo de test.
 */

// ── Mocks de Capacitor (no disponible en Node/jsdom) ──────────
vi.mock('@capacitor/preferences', () => ({
  Preferences: {
    get: vi.fn().mockResolvedValue({ value: null }),
    set: vi.fn().mockResolvedValue(undefined),
    remove: vi.fn().mockResolvedValue(undefined),
    clear: vi.fn().mockResolvedValue(undefined),
  },
}));

vi.mock('@capacitor/camera', () => ({
  Camera: {
    getPhoto: vi.fn().mockResolvedValue({
      dataUrl: 'data:image/jpeg;base64,mock',
      format: 'jpeg',
    }),
  },
  CameraResultType: { DataUrl: 'dataUrl', Uri: 'uri', Base64: 'base64' },
  CameraSource: { Camera: 'CAMERA', Photos: 'PHOTOS' },
}));

vi.mock('@capacitor/core', () => ({
  Capacitor: {
    isNativePlatform: vi.fn().mockReturnValue(false),
    getPlatform: vi.fn().mockReturnValue('web'),
  },
}));

// ── Mock de localStorage ────────────────────────────────────────
const localStorageMock = (() => {
  let store = {};
  return {
    getItem:   (key) => store[key] ?? null,
    setItem:   (key, value) => { store[key] = String(value); },
    removeItem:(key) => { delete store[key]; },
    clear:     () => { store = {}; },
  };
})();

Object.defineProperty(globalThis, 'localStorage', {
  value: localStorageMock,
  writable: true,
});

// ── Suprimir console.log de ruido en tests ──────────────────────
globalThis.console.log = vi.fn();
