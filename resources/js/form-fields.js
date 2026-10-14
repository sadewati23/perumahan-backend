/**
 * Pola umum formulir fase 2 (UI/UX):
 *  1. Wire aria untuk field ber-wrapper x-field: aria-invalid & aria-describedby
 *     pada kontrol, lalu scroll + fokus ke field error pertama.
 *  2. Penghitung karakter otomatis untuk textarea ber-atribut maxlength.
 *  3. Tombol tampilkan/sembunyikan password ([data-password-toggle]).
 *  4. Peringatan "perubahan belum disimpan" saat meninggalkan halaman
 *     (form ber-atribut [data-unsaved-guard]).
 */

const GERAK = () => window.matchMedia('(prefers-reduced-motion: reduced)').matches;

// ---------------------------------------------------------------- x-field ---
function wireAria() {
    document.querySelectorAll('[data-field]').forEach((field) => {
        const nama = field.dataset.field;
        if (!nama) return;

        const kontrol = field.querySelector('input:not([type=hidden]), select, textarea');
        if (!kontrol) return;

        const deskripsi = [];
        if (kontrol.getAttribute('aria-describedby')) {
            deskripsi.push(kontrol.getAttribute('aria-describedby'));
        }

        // Slug yang sama dengan komponen x-field: titik/kurung diganti '-' agar
        // field ber-key array (mis. "psu.0.jenis") punya id psu-0-jenis-petunjuk.
        const slug = nama.replace(/[^A-Za-z0-9_-]+/g, '-');

        const petunjuk = field.querySelector('#' + CSS.escape(slug + '-petunjuk'));
        if (petunjuk) deskripsi.push(petunjuk.id);

        const keterangan = field.querySelector('#' + CSS.escape(slug + '-pesan-error'));
        if (keterangan) deskripsi.push(keterangan.id);

        if (deskripsi.length) {
            kontrol.setAttribute('aria-describedby', deskripsi.join(' '));
        }

        if (field.hasAttribute('data-invalid')) {
            kontrol.setAttribute('aria-invalid', 'true');
        }
    });
}

function fokusErrorPertama() {
    const fieldError = document.querySelector('[data-field][data-invalid]');
    if (!fieldError) return;

    const kontrol = fieldError.querySelector('input:not([type=hidden]), input[type=hidden], select, textarea');
    if (!kontrol) return;

    kontrol.scrollIntoView({ block: 'center', behavior: GERAK() ? 'auto' : 'smooth' });
    kontrol.focus({ preventScroll: true });
}

// ------------------------------------------------- penghitung karakter ------
function initCounter() {
    document.querySelectorAll('textarea[maxlength]').forEach((textarea) => {
        if (textarea.dataset.counterReady) return;
        textarea.dataset.counterReady = 'true';

        const penuh = Number(textarea.getAttribute('maxlength')) || 0;
        if (!penuh) return;

        const counter = document.createElement('p');
        counter.className = 'text-right text-xs text-slate-500';
        counter.setAttribute('aria-live', 'polite');
        textarea.insertAdjacentElement('afterend', counter);

        const segarkan = () => {
            const sisa = textarea.value.length;
            counter.textContent = `${sisa} / ${penuh} karakter`;
            counter.classList.toggle('text-danger-600', sisa >= penuh);
        };

        textarea.addEventListener('input', segarkan);
        segarkan();
    });
}

// -------------------------------------------------- toggle password ---------
function initPasswordToggle() {
    document.querySelectorAll('[data-password-toggle]').forEach((tombol) => {
        if (tombol.dataset.toggleReady) return;
        tombol.dataset.toggleReady = 'true';

        const target = document.getElementById(tombol.getAttribute('aria-controls') ?? tombol.dataset.passwordTarget);
        if (!target) return;

        const teks = tombol.querySelector('[data-password-text]');

        const nilai = () => target.type === 'password';
        tombol.setAttribute('aria-pressed', 'false');

        tombol.addEventListener('click', () => {
            const tampil = nilai();
            target.type = tampil ? 'text' : 'password';
            tombol.setAttribute('aria-pressed', String(tampil));
            tombol.setAttribute(
                'aria-label',
                tampil ? 'Sembunyikan password' : 'Tampilkan password',
            );
            if (teks) teks.textContent = tampil ? 'Sembunyikan' : 'Tampilkan';
        });

        // Kembali ke mode tersembunyi bila form di-reset bfcache (tombol Back).
        window.addEventListener('pageshow', () => {
            target.type = 'password';
            tombol.setAttribute('aria-pressed', 'false');
            tombol.setAttribute('aria-label', 'Tampilkan password');
            if (teks) teks.textContent = 'Tampilkan';
        });
    });
}

// ------------------------------------------ peringatan belum disimpan -------
function initUnsavedGuard() {
    document.querySelectorAll('form[data-unsaved-guard]').forEach((form) => {
        if (form.dataset.unsavedReady) return;
        form.dataset.unsavedReady = 'true';

        let kotor = false;
        let mengirim = false;

        const tandaiKotor = () => { kotor = true; };
        form.addEventListener('input', tandaiKotor);
        form.addEventListener('change', (event) => {
            // Jangan hitung klik tombol/label yang tidak punya nilai terkirim.
            if (event.target.matches('button, [type=button], [type=submit]')) return;
            tandaiKotor();
        });

        form.addEventListener('submit', () => {
            mengirim = true;
        });

        window.addEventListener('beforeunload', (event) => {
            if (!kotor || mengirim) return;
            event.preventDefault();
            event.returnValue = form.dataset.unsavedGuard;
            return event.returnValue;
        });
    });
}

export function initFormFields() {
    wireAria();
    initCounter();
    initPasswordToggle();
    initUnsavedGuard();
    fokusErrorPertama();
}
