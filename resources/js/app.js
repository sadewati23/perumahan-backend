import './bootstrap';
import { initFlashMessages } from './flash';
import { initConfirmDialog } from './confirm-dialog';
import { initSubmitGuard } from './form-submit';
import { initFormFields } from './form-fields';
import { initFotoUpload } from './foto-upload';
import { initCopyTicket } from './copy-ticket';

/**
 * Baris dinamis (PSU & tipe unit) + helper kecil untuk halaman perumahan.
 */
document.addEventListener('DOMContentLoaded', () => {
    // --- Sidebar admin (mobile) ---
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');

    const setSidebar = (open) => {
        if (!sidebar) return;
        sidebar.classList.toggle('-translate-x-full', !open);
        backdrop?.classList.toggle('hidden', !open);
        document.body.classList.toggle('overflow-hidden', open);
        document.querySelectorAll('[data-toggle-sidebar]').forEach((btn) => {
            btn.setAttribute('aria-expanded', String(open));
        });
    };

    document.querySelectorAll('[data-toggle-sidebar]').forEach((btn) => {
        btn.setAttribute('aria-expanded', 'false');
        btn.addEventListener('click', () => setSidebar(sidebar?.classList.contains('-translate-x-full')));
    });
    backdrop?.addEventListener('click', () => setSidebar(false));

    // Tutup sidebar otomatis setelah memilih menu di layar kecil.
    if (window.matchMedia('(max-width: 1023px)').matches) {
        sidebar?.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => setSidebar(false));
        });
    }

    // --- Baris dinamis ---
    document.querySelectorAll('[data-dynamic-rows]').forEach((container) => {
        const tbody = container.querySelector('tbody');
        const template = document.getElementById(container.dataset.template);
        const addButton = container.querySelector('[data-add-row]');

        const nextIndex = () => container.querySelectorAll('tbody tr').length;

        const bindRemove = (row) => {
            row.querySelectorAll('[data-remove-row]').forEach((btn) => {
                btn.addEventListener('click', () => row.remove());
            });
        };

        tbody?.querySelectorAll('tr').forEach(bindRemove);

        addButton?.addEventListener('click', () => {
            if (!template) return;
            const html = template.innerHTML.replaceAll('__INDEX__', String(nextIndex() + Date.now() % 100000));
            const wrapper = document.createElement('tbody');
            wrapper.innerHTML = html.trim();
            const row = wrapper.firstElementChild;
            if (!row) return;
            tbody.appendChild(row);
            bindRemove(row);
        });
    });

    // --- Total unit dari rincian tipe ---
    const totalInfo = document.getElementById('total-unit-tipe');
    const hitungTotal = () => {
        if (!totalInfo) return;
        let total = 0;
        document.querySelectorAll('input[name^="tipe_units"][name$="[jumlah_unit]"]').forEach((input) => {
            total += parseInt(input.value || '0', 10) || 0;
        });
        totalInfo.querySelector('span').textContent = new Intl.NumberFormat('id-ID').format(total);
    };

    document.querySelectorAll('input[name^="tipe_units"][name$="[jumlah_unit]"]').forEach((input) => {
        input.addEventListener('input', hitungTotal);
    });
    document.querySelectorAll('[data-dynamic-rows]').forEach((container) => {
        container.addEventListener('click', () => setTimeout(hitungTotal, 0));
    });
    hitungTotal();

    // --- Input angka: format ribuan sederhana untuk harga ---
    document.querySelectorAll('[data-format-ribuan]').forEach((input) => {
        input.addEventListener('blur', () => {
            const value = parseFloat(String(input.value).replace(/\./g, '').replace(',', '.'));
            input.value = Number.isFinite(value) ? value : '';
        });
    });

    // --- Fondasi UI/UX: flash, dialog konfirmasi, anti klik ganda ---
    initFlashMessages();
    initConfirmDialog();
    initSubmitGuard();

    // --- Fase 2 (formulir): aria field, counter, toggle password, guard belum disimpan, unggah foto ---
    initFormFields();
    initFotoUpload();
    initCopyTicket();
});
