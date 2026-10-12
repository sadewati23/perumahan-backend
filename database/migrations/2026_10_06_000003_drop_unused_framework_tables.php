<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Hemat storage: hapus tabel bawaan Laravel yang tidak dipakai aplikasi.
     *
     * - personal_access_tokens (Sanctum): tidak ada model yang memakai HasApiTokens,
     *   aplikasi murni autentikasi session web (route api.php tidak digunakan).
     * - password_reset_tokens: fitur lupa password tidak ada di aplikasi.
     *
     * Tabel framework lain (sessions, cache*, jobs*, dst.) TIDAK boleh dihapus
     * karena .env memakai SESSION_DRIVER=database, CACHE_STORE=database,
     * dan QUEUE_CONNECTION=database.
     */
    public function up(): void
    {
        Schema::dropIfExists('personal_access_tokens');
        Schema::dropIfExists('password_reset_tokens');
    }

    public function down(): void
    {
        // Skema sengaja tidak direplikasi di sini: tabel sumber tetap ada di
        // migration pembuatnya (0001_01_01_000000 & 2026_09_22_065906),
        // jadi `migrate:fresh` tetap menghasilkan database yang benar.
        // Kembalikan tabel hanya bila fitur API token / reset password
        // benar-benar dibutuhkan.
    }
};
