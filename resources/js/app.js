import { createApp } from 'vue';
import App from './components/App.vue';
// Importar Axios y Vue Router
import axios from 'axios';
import VueAxios from 'vue-axios';
import { createRouter, createWebHistory } from 'vue-router';
import { routes } from './routes';

// Crear el enrutador
const router = createRouter({
    history: createWebHistory(),
    routes,
});

// Crear la aplicación Vue
createApp(App)
    .use(router)
    .use(VueAxios, axios)
    .mount('#app');
