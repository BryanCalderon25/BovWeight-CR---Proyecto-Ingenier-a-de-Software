/* === Entrada principal de BovWeight CR === */
import { createApp } from 'vue';
import { createPinia } from 'pinia';
import { IonicVue } from '@ionic/vue';
import App from './App.vue';
import { enrutador } from './router/index.js';

/* Estilos de Ionic */
import '@ionic/vue/css/core.css';
import '@ionic/vue/css/normalize.css';
import '@ionic/vue/css/structure.css';
import '@ionic/vue/css/typography.css';
import '@ionic/vue/css/padding.css';
import '@ionic/vue/css/float-elements.css';
import '@ionic/vue/css/text-alignment.css';
import '@ionic/vue/css/text-transformation.css';
import '@ionic/vue/css/flex-utils.css';
import '@ionic/vue/css/display.css';

/* Tema personalizado BovWeight CR */
import './theme/global.css';

const almacen = createPinia();
const app = createApp(App);

app.use(IonicVue, {
  mode: 'ios',
  animated: true
});
app.use(almacen);
app.use(enrutador);

enrutador.isReady().then(() => {
  app.mount('#app');
});
