/**
 * Form filter daftar perumahan terkirim otomatis saat:
 * - dropdown (kecamatan/status) berubah, atau
 * - pencarian teks berhenti diketik selama 400 ms.
 *
 * Tombol "Terapkan Filter" tetap ada sebagai cadangan apabila JS tidak aktif.
 * Pengiriman hanya terjadi bila nilai form benar-benar berubah dari nilai awal,
 * sehingga memilih kembali nilailama tidak membuat halaman terisi ulang sia-sia.
 */
export function initFilterOtomatis() {
    const form = document.querySelector('[data-filter-otomatis]');
    if (!form) return;

    let pengaturwaktu = null;
    const nilaiAwal = new FormData(form);

    const samaDenganAwal = () => {
        const sekarang = new FormData(form);
        if (nilaiAwal.size !== sekarang.size) return false;
        for (const [kunci, nilai] of nilaiAwal) {
            if (!sekarang.has(kunci) || sekarang.get(kunci) !== nilai) return false;
        }
        return true;
    };

    const kirim = () => {
        window.clearTimeout(pengaturwaktu);
        if (form.dataset.mengirim === '1' || samaDenganAwal()) return;
        form.dataset.mengirim = '1';
        form.submit();
    };

    form.querySelectorAll('select').forEach((select) => {
        select.addEventListener('change', kirim);
    });

    const carian = form.querySelector('input[name="q"]');
    if (carian) {
        carian.addEventListener('input', () => {
            window.clearTimeout(pengaturwaktu);
            pengaturwaktu = window.setTimeout(kirim, 400);
        });
    }
}
