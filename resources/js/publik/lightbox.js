/**
 * Lightbox foto perumahan (halaman detail).
 * Elemen [data-lightbox="<url-foto>"] membuka <dialog id="lightbox-foto">.
 * Basis animasi pop & backdrop sudah ada di app.css (dialog[open]).
 * Tanpa JavaScript, tautan tetap membuka foto di tab baru.
 */
export function initLightbox() {
    const dialog = document.getElementById('lightbox-foto');
    if (!dialog || typeof dialog.showModal !== 'function') return;

    const gambar = dialog.querySelector('[data-lightbox-gambar]');
    let pemicuTerakhir = null;

    document.querySelectorAll('[data-lightbox]').forEach((pemicu) => {
        pemicu.addEventListener('click', (klik) => {
            klik.preventDefault();
            const url = pemicu.dataset.lightbox;
            if (gambar && url) gambar.src = url;
            pemicuTerakhir = pemicu;
            dialog.showModal();
        });
    });

    const tutup = () => dialog.close();

    dialog.querySelectorAll('[data-lightbox-tutup]').forEach((tombol) => {
        tombol.addEventListener('click', tutup);
    });

    // Klik area gelap di luar gambar juga menutup.
    dialog.addEventListener('click', (klik) => {
        if (klik.target === dialog) tutup();
    });

    // Kosongkan sumber agar tidak memuat ulang saat dibuka lagi, lalu
    // kembalikan fokus ke foto pemicu untuk pengguna keyboard.
    dialog.addEventListener('close', () => {
        if (gambar) gambar.src = '';
        if (pemicuTerakhir) {
            pemicuTerakhir.focus();
            pemicuTerakhir = null;
        }
    });
}
