import * as bootstrap from 'bootstrap';
import { createApp } from 'vue';

window.bootstrap = bootstrap;

const app = createApp({});

const components = import.meta.glob('./components/**/*.vue', { eager: true });
Object.entries(components).forEach(([path, component]) => {
    const name = path
        .split('/')
        .pop()
        .replace(/\.vue$/, '');
    app.component(name, component.default || component);
});

if (document.getElementById('app')) {
    app.mount('#app');
}
