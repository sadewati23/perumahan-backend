<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporan_fotos', function (Blueprint $table): void {
            // 'laporan' = foto bukti dari pelapor warga, 'perbaikan' = foto hasil kerja petugas.
            $table->string('tipe', 20)->default('laporan')->index()->after('path');
            $table->string('keterangan')->nullable()->after('deskripsi_aksi_selesai');
            $table->foreignId('diunggah_oleh')->nullable()->after('keterangan')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('laporan_fotos', function (Blueprint $table): void {
            $table->dropForeign(['diunggah_oleh']);
            $table->dropColumn(['tipe', 'keterangan', 'diunggah_oleh']);
        });
    }
};
