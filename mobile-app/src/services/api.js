import axios from 'axios';

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || 'http://127.0.0.1:8000/api',
  headers: {
    'Content-Type': 'application/json',
    'Accept': 'application/json',
  },
});

console.log('API Conectada a:', api.defaults.baseURL);

// Interceptor para añadir el token de autenticación a todas las peticiones
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('bw_token');
    if (token) {
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Interceptor para manejar errores globales (como 401 Unauthorized)
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response && error.response.status === 401) {
      // Si el token expira o es inválido, limpiar localStorage y redirigir al login
      localStorage.removeItem('bw_token');
      localStorage.removeItem('bw_usuario');
      // Podrías emitir un evento o usar el store de auth aquí si es necesario
      window.location.href = '/login';
    }
    return Promise.reject(error);
  }
);

export default api;
