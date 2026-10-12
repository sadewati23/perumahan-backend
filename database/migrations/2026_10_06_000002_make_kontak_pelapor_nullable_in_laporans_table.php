<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporans', function (Blueprint $table) {
            // Nomor HP/WA tidak lagi diminta saat warga membuat laporan,
            // tapi data lama tetap ditampilkan bila ada.
            $table->string('kontak_pelapor', 100)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('laporans', function (Blueprint $table) {
            $table->string('kontak_pelapor', 100)->change();
        });
    }
};
