/**
 * Tombol kembali ke atas di semua halaman publik.
 * Muncul setelah menggulir lebih dari ~480px; menghormati prefers-reduced-motion.
 */
export function initKembaliAtas() {
    const tombol = document.getElementById('kembali-atas');
    if (!tombol) return;

    const perbarui = () => {
        const tampil = window.scrollY > 480;
        tombol.classList.toggle('opacity-0', !tampil);
        tombol.classList.toggle('pointer-events-none', !tampil);
    };

    perbarui();
    window.addEventListener('scroll', perbarui, { passive: true });

    tombol.addEventListener('click', () => {
        const halus = window.matchMedia('(prefers-reduced-motion: no-preference)').matches;
        window.scrollTo({ top: 0, behavior: halus ? 'smooth' : 'auto' });
    });
}
