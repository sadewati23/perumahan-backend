/**
 * Dialog konfirmasi native <dialog> untuk form[data-confirm].
 * Pengganti window.confirm: punya judul, penjelasan dampak, tombol Batal /
 * "Ya, hapus", bisa ditutup dengan Esc, dan fokus kembali ke pemicu.
 */

let resolveConfirm = null;
let lastTrigger = null;

const SPINNER = '<svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path></svg>';

function ensureDialog() {
    let dialog = document.getElementById('dialog-konfirmasi');
    if (dialog) return dialog;

    dialog = document.createElement('dialog');
    dialog.id = 'dialog-konfirmasi';
    dialog.className = 'fixed m-auto w-[min(92vw,28rem)] rounded-modal bg-white p-6 shadow-pop backdrop:bg-slate-900/50';
    dialog.setAttribute('aria-labelledby', 'dialog-konfirmasi-judul');
    dialog.innerHTML = `
        <h2 id="dialog-konfirmasi-judul" class="flex items-center gap-3 text-base font-semibold text-slate-900">
            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-pill bg-danger-100 text-danger-600">
                <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v4m0 4h.01M10.3 3.9L2.5 17a1.7 1.7 0 001.5 2.5h16a1.7 1.7 0 001.5-2.5L13.7 3.9a1.7 1.7 0 00-3 0z"/>
                </svg>
            </span>
            Konfirmasi tindakan
        </h2>
        <p data-confirm-message class="mt-3 text-sm text-slate-700"></p>
        <p class="mt-1 text-sm text-slate-500">Tindakan ini bersifat permanen dan tidak dapat dikembalikan.</p>
        <div class="mt-5 flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
            <button type="button" data-confirm-action="cancel"
                    class="inline-flex min-h-11 items-center justify-center gap-2 rounded-control px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-100 md:min-h-0">
                Batal
            </button>
            <button type="button" data-confirm-action="confirm"
                    class="inline-flex min-h-11 items-center justify-center gap-2 rounded-control bg-danger-600 px-4 py-2 text-sm font-semibold text-white shadow-card transition hover:bg-danger-700 md:min-h-0">
                Ya, hapus
            </button>
        </div>
    `;

    dialog.addEventListener('click', (event) => {
        const action = event.target.closest('[data-confirm-action]')?.dataset.confirmAction;
        if (action) {
            dialog.close(action === 'confirm' ? 'ok' : '');
        } else if (event.target === dialog) {
            // Klik pada backdrop menutup dialog seperti Esc.
            dialog.close('');
        }
    });

    dialog.addEventListener('close', () => {
        const result = dialog.returnValue === 'ok';
        if (resolveConfirm) {
            resolveConfirm(result);
            resolveConfirm = null;
        }
        if (lastTrigger) {
            lastTrigger.focus();
            lastTrigger = null;
        }
        // Pulihkan tombol konfirmasi untuk pemakaian berikutnya.
        const confirmBtn = dialog.querySelector('[data-confirm-action="confirm"]');
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = 'Ya, hapus';
    });

    document.body.appendChild(dialog);
    return dialog;
}

export function initConfirmDialog() {
    const dialog = ensureDialog();

    document.querySelectorAll('form[data-confirm]').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (form.dataset.confirming) return;
            event.preventDefault();

            dialog.querySelector('[data-confirm-message]').textContent =
                form.dataset.confirm || 'Data yang dipilih akan dihapus.';
            lastTrigger = event.submitter || document.activeElement;
            dialog.showModal();

            resolveConfirm = (ok) => {
                if (!ok) return;
                form.dataset.confirming = 'true';
                const confirmBtn = dialog.querySelector('[data-confirm-action="confirm"]');
                confirmBtn.disabled = true;
                confirmBtn.innerHTML = `${SPINNER}<span>Memproses…</span>`;
                // Bypass event "submit" agar tidak memicu dialog lagi.
                form.submit();
            };
        });
    });
}
