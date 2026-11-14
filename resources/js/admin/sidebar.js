/**
 * Sidebar admin untuk layar kecil (drawer + backdrop), dipindah apa adanya
 * dari kode inline app.js lama.
 *
 * Dipakai: layouts/admin (#sidebar, #sidebar-backdrop, [data-toggle-sidebar]).
 */
export function initSidebar() {
    const sidebar = document.getElementById('sidebar');
    const backdrop = document.getElementById('sidebar-backdrop');
    const konten = document.getElementById('konten-utama');
    const layarKecil = () => window.matchMedia('(max-width: 1023px)').matches;

    let pemicuTerakhir = null;

    const setSidebar = (open) => {
        if (!sidebar) return;
        sidebar.classList.toggle('-translate-x-full', !open);
        backdrop?.classList.toggle('hidden', !open);
        document.body.classList.toggle('overflow-hidden', open);
        document.querySelectorAll('[data-toggle-sidebar]').forEach((btn) => {
            btn.setAttribute('aria-expanded', String(open));
        });

        // Aksesibilitas drawer: blokir fokus ke konten utama saat drawer
        // terbuka di layar kecil, lalu kelola posisi fokus.
        if (konten) konten.inert = open && layarKecil();

        if (open) {
            pemicuTerakhir = document.activeElement;
            sidebar.querySelector('button, a, [tabindex]')?.focus({ preventScroll: true });
        } else if (pemicuTerakhir instanceof HTMLElement) {
            pemicuTerakhir.focus({ preventScroll: true });
            pemicuTerakhir = null;
        }
    };

    document.querySelectorAll('[data-toggle-sidebar]').forEach((btn) => {
        btn.setAttribute('aria-expanded', 'false');
        btn.addEventListener('click', () => setSidebar(sidebar?.classList.contains('-translate-x-full')));
    });
    backdrop?.addEventListener('click', () => setSidebar(false));

    // Escape menutup drawer (hanya saat mode drawer, bukan sidebar statis desktop).
    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape' || !sidebar || sidebar.classList.contains('-translate-x-full')) return;
        if (!layarKecil()) return;
        setSidebar(false);
    });

    // Tutup sidebar otomatis setelah memilih menu di layar kecil.
    if (window.matchMedia('(max-width: 1023px)').matches) {
        sidebar?.querySelectorAll('a').forEach((link) => {
            link.addEventListener('click', () => setSidebar(false));
        });
    }
}
