import * as bootstrap from 'bootstrap';
import { createApp } from 'vue';
import Dashboard from './components/Dashboard.vue';

// Bootstrap JS dùng chung cho modal xác nhận (data-confirm) ở layout.
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
