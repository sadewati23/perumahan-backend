/**
 * Baris dinamis (PSU & tipe unit), total unit dari rincian tipe, dan
 * format ribuan untuk harga — dipindah apa adanya dari kode inline app.js lama.
 *
 * Dipakai: admin/perumahan/_form.
 */
export function initBarisDinamis() {
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
}
