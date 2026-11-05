/**
 * Entri JavaScript untuk AREA ADMIN (layout layouts/admin dan semua
 * admin/auth/*): dashboard, perumahan, laporan, wilayah, pengguna,
 * login, lupa/reset/konfirmasi password.
 *
 * Modul umum dipakai kedua area; modul khusus publik tidak dimuat di sini.
 */
import { initFlashMessages } from './umum/flash';
import { initConfirmDialog } from './admin/confirm-dialog';
import { initSubmitGuard } from './umum/form-submit';
import { initFormFields } from './umum/form-fields';
import { initHitungNaik } from './umum/hitung-naik';
import { initSidebar } from './admin/sidebar';
import { initBarisDinamis } from './admin/baris-dinamis';

document.addEventListener('DOMContentLoaded', () => {
    // --- Sidebar admin (mobile) + baris dinamis (PSU & tipe unit) ---
    initSidebar();
    initBarisDinamis();

    // --- Fondasi UI/UX: flash, dialog konfirmasi, anti klik ganda ---
    initFlashMessages();
    initConfirmDialog();
    initSubmitGuard();

    // --- Fase 2 (formulir): aria field, counter, toggle password, guard ---
    initFormFields();

    // --- Animasi & util ---
    initHitungNaik();
});
