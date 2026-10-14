export function initCopyTicket() {
    document.querySelectorAll('[data-copy]').forEach((button) => {
        button.addEventListener('click', async () => {
            const value = button.dataset.copy || '';
            const status = button.parentElement?.querySelector('[data-copy-status]');
            try {
                if (navigator.clipboard?.writeText) {
                    await navigator.clipboard.writeText(value);
                } else {
                    const input = document.createElement('input');
                    input.value = value;
                    document.body.appendChild(input);
                    input.select();
                    document.execCommand('copy');
                    input.remove();
                }
                if (status) status.textContent = 'Nomor tiket disalin.';
            } catch (e) {
                if (status) status.textContent = 'Gagal menyalin. Salin manual.';
            }
        });
    });
}