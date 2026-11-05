/**
 * Munculkan section saat masuk viewport agar halaman terasa lebih hidup —
 * dipindah apa adanya dari kode inline app.js lama.
 *
 * Dipakai: halaman publik (publik/beranda, publik/index, publik/show).
 */
export function initRevealScroll() {
    if (!window.matchMedia('(prefers-reduced-motion: no-preference)').matches) {
        return;
    }

    const revealItems = document.querySelectorAll('.reveal-scroll');
    if ('IntersectionObserver' in window) {
        const revealObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) return;
                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            });
        }, { threshold: 0.12 });
        revealItems.forEach((item) => revealObserver.observe(item));
    } else {
        revealItems.forEach((item) => item.classList.add('is-visible'));
    }
}
