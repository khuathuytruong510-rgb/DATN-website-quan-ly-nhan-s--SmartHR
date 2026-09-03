import * as bootstrap from 'bootstrap';
import { createApp } from 'vue';
import Dashboard from './components/Dashboard.vue';

window.bootstrap = bootstrap;

function decodeConfirmMessage(value) {
    return String(value || '')
        .replace(/\\n/g, '\n')
        .replace(/&#10;/g, '\n')
        .trim();
}

function initAppConfirm() {
    const modalEl = document.getElementById('appConfirmModal');
    if (!modalEl || !window.bootstrap?.Modal) {
        window.smarthrConfirm = (message) => Promise.resolve(window.confirm(decodeConfirmMessage(message)));
        return;
    }

    const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    const titleEl = modalEl.querySelector('#appConfirmTitle');
    const bodyEl = modalEl.querySelector('#appConfirmBody');
    const okBtn = modalEl.querySelector('#appConfirmOk');
    let resolver = null;

    const finish = (result) => {
        const resolve = resolver;
        resolver = null;
        if (resolve) {
            resolve(result);
        }
    };

    okBtn?.addEventListener('click', () => {
        finish(true);
        modal.hide();
    });

    modalEl.addEventListener('hidden.bs.modal', () => finish(false));

    window.smarthrConfirm = (message, options = {}) => {
        const opts = typeof options === 'string' ? { variant: options } : (options || {});
        return new Promise((resolve) => {
            resolver = resolve;
            if (titleEl) {
                titleEl.textContent = opts.title || 'Xác nhận';
            }
            if (bodyEl) {
                bodyEl.textContent = decodeConfirmMessage(message);
            }
            if (okBtn) {
                okBtn.textContent = opts.okLabel || 'Xác nhận';
                const danger = opts.variant === 'danger';
                okBtn.classList.toggle('primary', !danger);
                okBtn.classList.toggle('danger', danger);
            }
            modal.show();
        });
    };

    const confirmFrom = (el) => el?.getAttribute?.('data-confirm');

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest?.('[data-confirm]');
        if (!trigger || trigger.tagName === 'FORM' || trigger.type === 'submit' || trigger.closest('form')) {
            return;
        }
        const message = confirmFrom(trigger);
        if (!message) {
            return;
        }
        event.preventDefault();
        const href = trigger.getAttribute('href');
        window.smarthrConfirm(message, {
            variant: trigger.getAttribute('data-confirm-variant') || 'primary',
            title: trigger.getAttribute('data-confirm-title') || 'Xác nhận',
            okLabel: trigger.getAttribute('data-confirm-ok') || 'Xác nhận',
        }).then((ok) => {
            if (!ok || !href || href === '#') {
                return;
            }
            window.location.href = href;
        });
    });

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || form.dataset.confirmBusy === '1') {
            return;
        }

        const submitter = event.submitter instanceof HTMLElement ? event.submitter : null;
        const source = submitter && confirmFrom(submitter) ? submitter : form;
        const message = confirmFrom(source);
        if (!message) {
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();

        const variant = source.getAttribute('data-confirm-variant')
            || ((source.classList.contains('danger') || source.classList.contains('btn-danger')) ? 'danger' : 'primary');

        window.smarthrConfirm(message, {
            variant,
            title: source.getAttribute('data-confirm-title') || 'Xác nhận',
            okLabel: source.getAttribute('data-confirm-ok') || 'Xác nhận',
        }).then((ok) => {
            if (!ok) {
                return;
            }
            form.dataset.confirmBusy = '1';
            if (typeof form.requestSubmit === 'function') {
                form.requestSubmit(submitter || undefined);
            } else {
                form.submit();
            }
            delete form.dataset.confirmBusy;
        });
    }, true);
}

document.addEventListener('DOMContentLoaded', initAppConfirm);

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
