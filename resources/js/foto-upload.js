/**
 * Unggah foto untuk form lapor (dan area unggah serupa):
 *  - area klik/seret-lepas (drag & drop), tetap memakai <input type="file">
 *    asli sehingga nama field (foto[]) & aturan server tidak berubah;
 *  - pratinjau thumbnail + tombol hapus per foto;
 *  - validasi sisi klien (jumlah, tipe, ukuran) yang meniru aturan server,
 *    dengan pesan berbahasa Indonesia.
 *
 * Markup: elemen [data-foto-upload] berisi:
 *  - input[type=file] asli (name sesuai server, mis. foto[]);
 *  - [data-foto-buka]  : tombol/harea klik & target drop;
 *  - [data-foto-daftar]: daftar pratinjau;
 *  - [data-foto-pesan] : pesan validasi (aria-live="polite").
 */

export function initFotoUpload() {
    document.querySelectorAll('[data-foto-upload]').forEach((root) => {
        if (root.dataset.fotoReady) return;
        root.dataset.fotoReady = 'true';

        const input = root.querySelector('input[type=file]');
        const buka = root.querySelector('[data-foto-buka]');
        const daftar = root.querySelector('[data-foto-daftar]');
        const pesan = root.querySelector('[data-foto-pesan]');
        if (!input || !buka) return;

        const maksJumlah = Number(root.dataset.maks ?? 3);
        const maksUkuranKb = Number(root.dataset.maksUkuran ?? 2048);
        const minJumlah = Number(root.dataset.min ?? 0);
        const tipeIzinkan = (root.dataset.tipe ?? '')
            .split(',')
            .map((tipe) => tipe.trim())
            .filter(Boolean);

        let berkasSaatIni = [];

        const kabari = (teks, baik = false) => {
            if (!pesan) return;
            pesan.textContent = teks;
            pesan.classList.toggle('text-danger-600', teks !== '' && !baik);
            pesan.classList.toggle('text-success-700', baik);
        };

        const deskripsiUkuran = (bait) => (bait / 1024 >= 512)
            ? `${(bait / (1024 * 1024)).toFixed(1)} MB`
            : `${Math.ceil(bait / 1024)} KB`;

        const alasanTolak = (berkas) => {
            if (tipeIzinkan.length && !tipeIzinkan.includes(berkas.type)) {
                return 'tipe file tidak didukung (harus JPG, PNG, atau WEBP)';
            }
            if (berkas.size > maksUkuranKb * 1024) {
                return `ukuran melebihi maksimal ${maksUkuranKb / 1024} MB`;
            }
            return null;
        };

        // Satu sumber kebenaran: berkasSaatIni -> input.files (untuk submit form).
        const pakaiBerkas = (berkas) => {
            berkasSaatIni = [...berkas];
            const kotak = new DataTransfer();
            berkasSaatIni.forEach((berkas) => kotak.items.add(berkas));
            input.files = kotak.files;
        };

        const renderDaftar = () => {
            if (!daftar) return;
            daftar.replaceChildren();

            berkasSaatIni.forEach((berkas, index) => {
                const item = document.createElement('li');
                item.className = 'flex items-center gap-3 rounded-lg border border-slate-200 bg-white p-2';

                const gambar = document.createElement('img');
                gambar.src = URL.createObjectURL(berkas);
                gambar.alt = `Pratinjau foto ${berkas.name}`;
                gambar.width = 48;
                gambar.height = 48;
                gambar.loading = 'lazy';
                gambar.className = 'h-12 w-12 shrink-0 rounded-md border border-slate-200 object-cover';

                const sendi = document.createElement('div');
                sendi.className = 'min-w-0 flex-1';
                const nama = document.createElement('p');
                nama.className = 'truncate text-sm font-medium text-slate-700';
                nama.textContent = berkas.name;
                const ukuran = document.createElement('p');
                ukuran.className = 'text-xs text-slate-500';
                ukuran.textContent = deskripsiUkuran(berkas.size);
                sendi.append(nama, ukuran);

                const hapus = document.createElement('button');
                hapus.type = 'button';
                hapus.className = 'rounded-md px-3 py-2 text-xs font-medium text-danger-600 transition hover:bg-danger-50';
                hapus.textContent = 'Hapus';
                hapus.setAttribute('aria-label', `Hapus foto ${berkas.name}`);
                hapus.addEventListener('click', () => {
                    const sisa = [...berkasSaatIni];
                    sisa.splice(index, 1);
                    pakaiBerkas(sisa);
                    renderDaftar();
                    kabari('Foto dihapus dari lampiran.');
                });

                item.append(gambar, sendi, hapus);
                daftar.appendChild(item);
            });
        };

        const segarkanPesan = (tertolak, jumlah) => {
            if (tertolak.length) {
                kabari(tertolak.slice(0, 3).join(' · '));
            } else if (jumlah > 0) {
                kabari(`${jumlah} foto dilampirkan (maksimal ${maksJumlah}).`, true);
            } else {
                kabari('');
            }
        };

        //Menambah berkas lewat drag & drop (hanya yang valid, membatasi jumlah).
        const tambahBerkas = (berkasBaru) => {
            const tertolak = [];
            const diterima = [];

            berkasBaru.forEach((berkas) => {
                const alasan = alasanTolak(berkas);
                if (alasan) tertolak.push(`${berkas.name}: ${alasan}`);
                else diterima.push(berkas);
            });

            let gabungan = [...berkasSaatIni, ...diterima];
            if (gabungan.length > maksJumlah) {
                tertolak.push(`Maksimal ${maksJumlah} foto — foto berlebih tidak dilampirkan.`);
                gabungan = gabungan.slice(0, maksJumlah);
            }

            pakaiBerkas(gabungan);
            renderDaftar();
            segarkanPesan(tertolak, gabungan.length);
        };

        // Memilih ulang lewat picker mengganti seluruh lampiran.
        input.addEventListener('change', () => {
            const terpilih = [...input.files];
            berkasSaatIni = [];
            tambahBerkas(terpilih);
        });

        buka.addEventListener('click', () => input.click());

        ['dragenter', 'dragover'].forEach((jenis) => {
            buka.addEventListener(jenis, (event) => {
                event.preventDefault();
                buka.classList.add('dropzone-aktif');
            });
        });
        ['dragleave', 'drop'].forEach((jenis) => {
            buka.addEventListener(jenis, () => buka.classList.remove('dropzone-aktif'));
        });
        buka.addEventListener('drop', (event) => {
            event.preventDefault();
            const dijatuhkan = [...(event.dataTransfer?.files ?? [])];
            if (dijatuhkan.length) tambahBerkas(dijatuhkan);
        });

        // Pengecekan jumlah minimal pada submit — hanya untuk jenis laporan
        // yang dituntut (mis. bangunan liar wajib 1 foto) bila ada pilihan jenis.
        const form = root.closest('form');
        const minJenis = root.dataset.minJenis ?? null;

        if (form && minJumlah > 0) {
            form.addEventListener('submit', (event) => {
                if (minJenis) {
                    const jenisCocok = form.querySelector(`input[name="jenis"][value="${minJenis}"]:checked`);
                    if (!jenisCocok) return;
                }

                const kurang = minJumlah - berkasSaatIni.length;
                if (kurang <= 0) return;

                event.preventDefault();
                kabari(kurang === 1
                    ? 'Wajib melampirkan minimal 1 foto — foto belum ada.'
                    : `Wajib melampirkan minimal ${minJumlah} foto — kurang ${kurang} foto.`);
                buka.scrollIntoView({ block: 'center', behavior: 'smooth' });
                buka.focus();
            }, true);
        }
    });
}
