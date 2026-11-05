/**
 * Entri JavaScript untuk halaman PUBLIK (layout layouts/publik):
 * beranda, daftar, detail, peta, lapor, cek laporan.
 *
 * Modul umum dipakai kedua area; modul khusus publik tidak dimuat di admin
 * dan modul khusus admin (sidebar, baris dinamis, konfirmasi hapus) tidak
 * dimuat di sini.
 */
import { initFlashMessages } from './umum/flash';
import { initSubmitGuard } from './umum/form-submit';
import { initFormFields } from './umum/form-fields';
import { initFotoUpload } from './publik/foto-upload';
import { initCopyTicket } from './publik/copy-ticket';
import { initHitungNaik } from './umum/hitung-naik';
import { initRevealScroll } from './publik/reveal-scroll';

document.addEventListener('DOMContentLoaded', () => {
    // --- Fondasi UI/UX: flash, anti klik ganda ---
    initFlashMessages();
    initSubmitGuard();

    // --- Fase 2 (formulir): aria field, counter, unggah foto, tiket salin ---
    initFormFields();
    initFotoUpload();
    initCopyTicket();

    // --- Animasi & util ---
    initRevealScroll();
    initHitungNaik();
});
