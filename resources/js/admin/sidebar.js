/**
 * Sidebar admin untuk layar kecil (drawer + backdrop), dipindah apa adanya
 * dari kode inline app.js lama.
 *
 * Dipakai: layouts/admin (#sidebar, #sidebar-backdrop, [data-toggle-sidebar]).
 */
export function initSidebar() {
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
}
