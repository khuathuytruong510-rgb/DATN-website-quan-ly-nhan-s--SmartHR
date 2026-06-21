import { createApp } from 'vue';
import AlertComponent from './components/Alert.vue';

const app = createApp({});

app.component('Alert', AlertComponent);

// Auto-register all Vue components
const components = import.meta.glob('./components/**/*.vue', { eager: true });
Object.entries(components).forEach(([path, component]) => {
    const name = path
        .split('/')
        .pop()
        .replace(/\.vue$/, '');
    app.component(name, component.default || component);
});

// Only mount if #app exists to avoid interfering with auth pages
if (document.getElementById('app')) {
    app.mount('#app');
}
