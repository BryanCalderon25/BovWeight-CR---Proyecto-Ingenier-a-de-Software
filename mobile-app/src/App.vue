<template>
  <ion-app>
    <ion-router-outlet />
  </ion-app>
</template>

<script setup>
/* Componente raíz de la aplicación BovWeight CR */
import { onMounted } from 'vue';
import { IonApp, IonRouterOutlet } from '@ionic/vue';

onMounted(() => {
  const temaGuardado = localStorage.getItem('bw_tema');
  if (temaGuardado === 'oscuro') {
    document.documentElement.setAttribute('data-tema', 'oscuro');
  } else if (temaGuardado === 'claro') {
    document.documentElement.setAttribute('data-tema', '');
  } else {
    // Opcional: preferencia del sistema por defecto si no se ha configurado
    const prefiereOscuro = window.matchMedia('(prefers-color-scheme: dark)').matches;
    if (prefiereOscuro) {
      document.documentElement.setAttribute('data-tema', 'oscuro');
      localStorage.setItem('bw_tema', 'oscuro');
    }
  }
});
</script>

<style>
/* Los estilos globales se cargan desde theme/global.css */
</style>
