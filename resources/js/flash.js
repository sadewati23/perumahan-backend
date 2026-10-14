/**
 * Pesan flash yang bisa ditutup: tombol X menghapus elemen,
 * pesan sukses (data-flash-auto) hilang otomatis setelah ±5 detik,
 * pesan error tetap tampil sampai ditutup.
 */

const AUTO_DISMISS_MS = 5000;

export function initFlashMessages() {
    document.querySelectorAll('[data-flash]').forEach((el) => {
        const dismiss = () => el.remove();

        el.querySelector('[data-flash-dismiss]')?.addEventListener('click', dismiss);

        if (el.hasAttribute('data-flash-auto')) {
            setTimeout(dismiss, AUTO_DISMISS_MS);
        }
    });
}
