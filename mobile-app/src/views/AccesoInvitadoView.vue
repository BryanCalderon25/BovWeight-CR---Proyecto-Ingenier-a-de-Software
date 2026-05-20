<template>
  <ion-page>
    <ion-content class="ion-padding ion-text-center acceso-invitado-content">
      <div class="caja-centrada animar-aparecer">
        <div class="logo-bovweight">🐂</div>
        <h2 class="titulo-acceso">BovWeight CR</h2>
        <span class="etiqueta-acceso">ACCESO TEMPORAL SEGURO</span>

        <div class="tarjeta-vidrio">
          <div v-if="estado === 'validando'" class="loader-container">
            <div class="spinner"></div>
            <p class="texto-cargando">Validando código de invitación...</p>
            <p class="texto-secundario">Espere unos instantes mientras autorizamos su entrada segura.</p>
          </div>

          <div v-else-if="estado === 'exito'" class="loader-container">
            <div class="success-icon">✓</div>
            <p class="texto-exito" style="color:var(--exito)">Acceso Concedido</p>
            <p class="texto-nombre">{{ usuarioNombre }}</p>
            <p class="texto-secundario">Redirigiendo al panel de control de la finca...</p>
          </div>

          <div v-else-if="estado === 'error'" class="loader-container">
            <div class="error-icon">✗</div>
            <p class="texto-error" style="color:var(--peligro)">Enlace Inválido</p>
            <p class="texto-secundario">{{ errorMensaje }}</p>
            <button class="boton boton--primario" style="margin-top:20px;width:100%" @click="irAlLogin">
              Volver al Login
            </button>
          </div>
        </div>
      </div>
    </ion-content>
  </ion-page>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { IonPage, IonContent } from '@ionic/vue';
import { useAlmacenAuth } from '@/stores/auth.js';
import api from '@/services/api';

const route = useRoute();
const router = useRouter();
const almacenAuth = useAlmacenAuth();

const estado = ref('validando'); // 'validando' | 'exito' | 'error'
const errorMensaje = ref('');
const usuarioNombre = ref('');

onMounted(async () => {
  const token = route.params.token;
  if (!token) {
    estado.value = 'error';
    errorMensaje.value = 'No se proporcionó ningún código de acceso.';
    return;
  }

  try {
    const respuesta = await api.post(`/invitaciones/resolver/${token}`);
    const datos = respuesta.data.datos;
    
    // Iniciar sesión del invitado en el store Pinia con invited_farm_id explícito
    almacenAuth.iniciarSesionInvitado({
      token: datos.token,
      usuario: {
        ...datos.usuario,
        invited_farm_id: datos.finca_id
      }
    });

    usuarioNombre.value = datos.usuario.name;
    estado.value = 'exito';

    // Redirigir directamente a la finca invitada después de 1.5 segundos
    setTimeout(() => {
      router.push(`/app/fincas/${datos.finca_id}`);
    }, 1500);
  } catch (err) {
    console.error('Error al resolver invitación:', err);
    estado.value = 'error';
    errorMensaje.value = err.response?.data?.mensaje || 'El enlace de invitación ha expirado o ya no está disponible.';
  }
});

function irAlLogin() {
  router.push('/login');
}
</script>

<style scoped>
.acceso-invitado-content {
  --background: radial-gradient(circle at top, #F4F6F0 0%, #E9EDE4 100%);
  display: flex;
  align-items: center;
  justify-content: center;
}
.caja-centrada {
  max-width: 400px;
  margin: 80px auto 0 auto;
  display: flex;
  flex-direction: column;
  align-items: center;
}
.logo-bovweight {
  font-size: 80px;
  margin-bottom: 12px;
  animation: float 4s ease-in-out infinite;
}
.titulo-acceso {
  font-size: var(--tamano-xl);
  font-weight: bold;
  color: var(--primario);
  margin: 0;
}
.etiqueta-acceso {
  font-size: var(--tamano-xxs);
  letter-spacing: 2px;
  font-weight: bold;
  color: var(--texto-terciario);
  margin-top: 4px;
  margin-bottom: 24px;
}
.tarjeta-vidrio {
  width: 100%;
  padding: 30px;
  background: rgba(255, 255, 255, 0.7);
  backdrop-filter: blur(12px);
  -webkit-backdrop-filter: blur(12px);
  border: 1px solid rgba(255, 255, 255, 0.5);
  border-radius: var(--borde-radio-lg);
  box-shadow: 0 8px 32px rgba(0, 0, 0, 0.05);
}
.loader-container {
  display: flex;
  flex-direction: column;
  align-items: center;
  gap: 12px;
}
.spinner {
  width: 48px;
  height: 48px;
  border: 4px solid rgba(101, 109, 74, 0.1);
  border-left-color: var(--primario);
  border-radius: 50%;
  animation: spin 1s linear infinite;
  margin-bottom: 8px;
}
.success-icon {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: var(--exito-suave);
  color: var(--exito);
  font-size: 28px;
  font-weight: bold;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 8px;
}
.error-icon {
  width: 56px;
  height: 56px;
  border-radius: 50%;
  background: var(--peligro-suave);
  color: var(--peligro);
  font-size: 28px;
  font-weight: bold;
  display: flex;
  align-items: center;
  justify-content: center;
  margin-bottom: 8px;
}
.texto-cargando, .texto-exito, .texto-error {
  font-size: var(--tamano-md);
  font-weight: bold;
  color: var(--texto-principal);
  margin: 0;
}
.texto-nombre {
  font-size: var(--tamano-lg);
  font-weight: bold;
  color: var(--primario);
  margin: 0;
}
.texto-secundario {
  font-size: var(--tamano-sm);
  color: var(--texto-secundario);
  margin: 0;
  line-height: 1.4;
}

@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}
@keyframes float {
  0%, 100% { transform: translateY(0px); }
  50% { transform: translateY(-10px); }
}
</style>
