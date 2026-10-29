const durasi = 900;

export function initHitungNaik() {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

    const items = document.querySelectorAll('[data-hitung]');
    const mulai = (el) => {
        const mentah = el.dataset.hitung ?? '';
        if (mentah.trim() === '') return;
        const nilai = Number(mentah);
        const desimal = Number(el.dataset.desimal ?? 0);
        if (!Number.isFinite(nilai) || !Number.isInteger(desimal) || desimal < 0 || desimal > 2) return;

        const teksAsli = el.textContent;
        const formatter = new Intl.NumberFormat('id-ID', {
            minimumFractionDigits: desimal,
            maximumFractionDigits: desimal,
        });
        const awal = performance.now();
        const easeOutCubic = (waktu) => 1 - ((1 - waktu) ** 3);

        const frame = (sekarang) => {
            const progres = Math.min((sekarang - awal) / durasi, 1);
            el.textContent = formatter.format(nilai * easeOutCubic(progres));
            if (progres < 1) {
                window.requestAnimationFrame(frame);
            } else {
                el.textContent = teksAsli;
            }
        };

        window.requestAnimationFrame(frame);
    };

    if (!('IntersectionObserver' in window)) {
        items.forEach(mulai);
        return;
    }

    const observer = new IntersectionObserver((entries, instance) => {
        entries.forEach((entry) => {
            if (!entry.isIntersecting) return;
            mulai(entry.target);
            instance.unobserve(entry.target);
        });
    }, { threshold: .4 });
    items.forEach((item) => observer.observe(item));
}
