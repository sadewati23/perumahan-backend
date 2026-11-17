/**
 * Grafik ringkasan dashboard admin.
 *
 * Data dikirim dari Blade lewat atribut data-* berisi JSON, warna diambil dari
 * variabel CSS --chart-* (Chart.js tidak bisa membaca var() langsung).
 * Chart.js dimuat dari CDN di halaman dashboard; bila gagal dimuat, seluruh
 * grafik dilewati tanpa error.
 */

const bacaJson = (nilai, cadangan) => {
    if (!nilai) return cadangan;

    try {
        return JSON.parse(nilai) ?? cadangan;
    } catch {
        return cadangan;
    }
};

const keAngka = (daftar) => (Array.isArray(daftar) ? daftar.map((n) => Number(n) || 0) : []);

export function initGrafikDashboard() {
    const kanvas = document.querySelectorAll('canvas[data-grafik]');
    if (kanvas.length === 0 || typeof window.Chart === 'undefined') return;

    const gaya = getComputedStyle(document.documentElement);
    const warnaDari = (nama, cadangan) => (gaya.getPropertyValue(nama) || '').trim() || cadangan;

    const warna = {
        utama: warnaDari('--chart-1', '#1d4ed8'),
        muda: warnaDari('--chart-2', '#93c5fd'),
        hijau: warnaDari('--chart-3', '#10b981'),
        kuning: warnaDari('--chart-4', '#f59e0b'),
        merah: warnaDari('--chart-5', '#f43f5e'),
        biru: warnaDari('--chart-6', '#0ea5e9'),
        garis: warnaDari('--chart-garis', '#cbd2cb'),
        teks: warnaDari('--chart-teks', '#3f4a44'),
    };

    const palet = [warna.hijau, warna.kuning, warna.merah, warna.biru, warna.muda, warna.utama];

    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        window.Chart.defaults.animation = false;
    }

    const dasar = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                labels: {
                    color: warna.teks,
                    boxWidth: 12,
                    boxHeight: 12,
                    usePointStyle: true,
                    padding: 16,
                },
            },
            tooltip: {
                backgroundColor: '#16211c',
                titleColor: '#f8fafc',
                bodyColor: '#e2e8f0',
                padding: 10,
                cornerRadius: 8,
            },
        },
    };

    const sumbuX = {
        border: { display: false },
        grid: { display: false },
        ticks: { color: warna.teks },
    };

    const sumbuY = {
        beginAtZero: true,
        border: { display: false },
        grid: { color: warna.garis, drawTicks: false },
        ticks: { color: warna.teks, precision: 0 },
    };

    kanvas.forEach((el) => {
        const jenis = el.dataset.grafik;
        const kategori = bacaJson(el.dataset.kategori, []);
        const seriA = keAngka(bacaJson(el.dataset.seriA, []));
        const seriB = keAngka(bacaJson(el.dataset.seriB, []));

        if (jenis === 'tahun') {
            new window.Chart(el, {
                type: 'bar',
                data: {
                    labels: kategori.map(String),
                    datasets: [
                        {
                            label: 'Perumahan selesai',
                            data: seriA,
                            backgroundColor: warna.utama,
                            borderRadius: 6,
                            maxBarThickness: 26,
                        },
                        {
                            label: 'Unit rumah',
                            data: seriB,
                            backgroundColor: warna.muda,
                            borderRadius: 6,
                            maxBarThickness: 26,
                        },
                    ],
                },
                options: {
                    ...dasar,
                    scales: { x: sumbuX, y: sumbuY },
                },
            });

            return;
        }

        if (jenis === 'kondisi') {
            const warnaKustom = bacaJson(el.dataset.warna, [])
                .map((nama) => warnaDari(nama, ''))
                .filter((nilai) => nilai !== '');

            new window.Chart(el, {
                type: 'doughnut',
                data: {
                    labels: kategori.map(String),
                    datasets: [
                        {
                            data: seriA,
                            backgroundColor: warnaKustom.length > 0 ? warnaKustom : palet,
                            borderColor: '#ffffff',
                            borderWidth: 2,
                        },
                    ],
                },
                options: {
                    ...dasar,
                    cutout: '62%',
                    plugins: {
                        ...dasar.plugins,
                        legend: { display: false },
                    },
                },
            });

            return;
        }

        if (jenis === 'jenis') {
            new window.Chart(el, {
                type: 'bar',
                data: {
                    labels: kategori.map(String),
                    datasets: [
                        {
                            label: 'Jumlah entri',
                            data: seriA,
                            backgroundColor: warna.hijau,
                            borderRadius: 6,
                            maxBarThickness: 22,
                        },
                    ],
                },
                options: {
                    ...dasar,
                    indexAxis: 'y',
                    plugins: {
                        ...dasar.plugins,
                        legend: { display: false },
                    },
                    scales: {
                        x: { ...sumbuY, beginAtZero: true },
                        y: { ...sumbuX, ticks: { color: warna.teks } },
                    },
                },
            });
        }
    });
}
