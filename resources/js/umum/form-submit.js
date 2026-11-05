/**
 * Anti klik ganda: saat form disubmit, semua tombol submit dinonaktifkan,
 * diberi spinner + teks "Menyimpan…" (atau data-guard-label bila ada),
 * dan dipulihkan saat pageshow (tombol Back / bfcache).
 */

const LABEL_BAWAAN = 'Menyimpan\u2026';

function simpanHtml(btn) {
    if (btn.__guardHtml === undefined) {
        btn.__guardHtml = btn.innerHTML;
        btn.__guardDisabled = btn.disabled;
    }
}

function pulihkanButton(btn) {
    if (btn.__guardHtml === undefined) return;
    btn.innerHTML = btn.__guardHtml;
    btn.disabled = btn.__guardDisabled || false;
    delete btn.__guardHtml;
    delete btn.__guardDisabled;
}

export function initSubmitGuard() {
    document.querySelectorAll('form').forEach((form) => {
        form.addEventListener('submit', (event) => {
            // Jangan mengunci bila listener lain (mis. dialog konfirmasi) membatalkan submit.
            if (event.defaultPrevented || form.dataset.guarded) return;

            const tombol = [event.submitter, ...form.querySelectorAll('button[type="submit"]')]
                .filter((btn, index, semua) => btn && semua.indexOf(btn) === index);
            if (!tombol.length) return;

            form.dataset.guarded = 'true';

            tombol.forEach((btn) => {
                simpanHtml(btn);
                btn.disabled = true;
                btn.setAttribute('aria-busy', 'true');
                btn.innerHTML = `<svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg><span>${btn.dataset.guardLabel || LABEL_BAWAAN}</span>`;
            });
        });
    });

    window.addEventListener('pageshow', (event) => {
        if (!event.persisted) return;
        pulihkanSemua();
    });

    window.addEventListener('pagehide', () => {
        // Bfcache menyimpan DOM yang sudah terkunci; tandai agar pageshow memulihkan.
    });
}

function pulihkanSemua() {
    document.querySelectorAll('form[data-guarded]').forEach((form) => {
        form.querySelectorAll('button[type="submit"]').forEach(pulihkanButton);
        form.removeAttribute('data-guarded');
    });
}
