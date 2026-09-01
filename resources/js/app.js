import { createApp } from 'vue';
import Dashboard from './components/Dashboard.vue';
import * as bootstrap from 'bootstrap';

// Cung cấp global bootstrap để các trang Blade dùng modal/tooltip.
window.bootstrap = bootstrap;

// Chỉ mount Vue khi trang có dashboard. Các trang Blade khác không biên dịch Vue → nhanh hơn.
const dashEl = document.getElementById('vue-dashboard');
if (dashEl) {
    let stats = {};
    try {
        stats = JSON.parse(dashEl.dataset.stats || '{}');
    } catch {
        stats = {};
    }

    createApp(Dashboard, { stats }).mount(dashEl);
}
