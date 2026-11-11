/**
 * Navigasi bagian halaman detail perumahan.
 *
 * Tautan di dalam [data-nav-bagian] mengarah ke id bagian (Lokasi, Tahun &
 * Pemakaman, Unit, PSU, Peta). Bagian yang sedang terlihat ditandai memakai
 * IntersectionObserver dengan pita sempit di bawah header sticky, sehingga
 * sorotan berpindah saat bagian melewati area baca.
 *
 * Bagian peta berada di akhir alur (dan kolom samping memakai position sticky),
 * jadi ditandai saat pengguna mendekati dasar halaman agar tidak "menang"
 * terus selama halaman digulir.
 */
export function initNavBagian() {
    const nav = document.querySelector('[data-nav-bagian]');
    if (!nav) return;

    const tautan = Array.from(nav.querySelectorAll('a[href^="#"]'));
    const bagian = tautan
        .map((tautan) => ({ tautan, el: document.querySelector(decodeURIComponent(tautan.hash)) }))
        .filter((item) => item.el);
    if (bagian.length === 0) return;

    const tandai = (id) => {
        tautan.forEach((tautan) => {
            const aktif = tautan.hash === `#${id}`;
            tautan.classList.toggle('bg-brand-50', aktif);
            tautan.classList.toggle('text-brand-700', aktif);
            tautan.classList.toggle('text-slate-600', !aktif);
            if (aktif) tautan.setAttribute('aria-current', 'true');
            else tautan.removeAttribute('aria-current');
        });
    };

    // Bagian yang diikuti lewat IntersectionObserver (peta ditangani terpisah).
    const diamati = bagian.filter((item) => item.el.id !== 'peta');
    const peta = bagian.find((item) => item.el.id === 'peta');

    const diDasarHalaman = () => (
        window.innerHeight + window.scrollY >= document.documentElement.scrollHeight - 48
    );

    if ('IntersectionObserver' in window) {
        const pengamat = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (entry.isIntersecting) tandai(entry.target.id);
            });
            if (peta && diDasarHalaman()) tandai(peta.el.id);
        }, { rootMargin: '-140px 0px -55% 0px', threshold: 0 });

        diamati.forEach((item) => pengamat.observe(item.el));
    } else {
        // Peramban lama: tentukan bagian terakhir yang melewati garis atas.
        const perbarui = () => {
            const batas = 150;
            let terpilih = diamati[0]?.el.id ?? bagian[0].el.id;
            diamati.forEach((item) => {
                if (item.el.getBoundingClientRect().top <= batas) terpilih = item.el.id;
            });
            if (peta && diDasarHalaman()) terpilih = peta.el.id;
            tandai(terpilih);
        };

        perbarui();
        window.addEventListener('scroll', perbarui, { passive: true });
    }

    // Peta berada di akhir halaman: tandai juga saat pengguna mendekati dasar.
    if (peta) {
        window.addEventListener('scroll', () => {
            if (diDasarHalaman()) tandai(peta.el.id);
        }, { passive: true });
    }
}
