/**
 * Panel filter daftar perumahan yang bisa dilipat di layar kecil.
 *
 * Panel dirender sebagai <details open> agar tetap terbuka tanpa JavaScript
 * (progressive enhancement). Modul ini hanya menutupnya di HP ketika belum
 * ada filter aktif, dan membukanya kembali di layar >= md atau saat filter
 * aktif — sehingga hasil filter selalu terlihat konteksnya.
 */
export function initFilterLipat() {
    const panel = document.querySelector('[data-filter-lipat]');
    if (!panel || !('open' in panel)) return;

    const layarLebar = window.matchMedia('(min-width: 768px)');

    const atur = () => {
        const adaFilterAktif = panel.dataset.filterAktif === '1';

        if (layarLebar.matches || adaFilterAktif) {
            panel.setAttribute('open', '');
        } else {
            panel.removeAttribute('open');
        }
    };

    atur();

    if (typeof layarLebar.addEventListener === 'function') {
        layarLebar.addEventListener('change', atur);
    } else {
        // Peramban lama (Safari < 14) hanya mendukung addListener.
        layarLebar.addListener(atur);
    }
}
