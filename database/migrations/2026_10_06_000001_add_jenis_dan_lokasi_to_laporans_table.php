<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporans', function (Blueprint $table) {
            // Pengaduan & bangunan liar bisa berada di luar perumahan → perumahan opsional.
            $table->foreignId('perumahan_id')->nullable()->change();

            // Jenis laporan: satu tabel untuk semua jenis laporan warga.
            $table->enum('jenis', ['kerusakan', 'pengaduan', 'bangunan_liar'])
                ->default('kerusakan')
                ->after('kode')
                ->comment('kerusakan = kerusakan sarana-prasarana; pengaduan = aduan/saran teks; bangunan_liar = laporan bangunan tanpa izin');

            // Bangunan liar bisa berada di luar area perumahan → kelurahan opsional.
            $table->foreignId('kelurahan_id')
                ->nullable()
                ->after('perumahan_id')
                ->constrained('kelurahans')
                ->nullOnDelete();

            // Lokasi teks bebas (wajib untuk bangunan liar).
            $table->string('lokasi_teks', 255)
                ->nullable()
                ->after('kelurahan_id')
                ->comment('Uraian lokasi kejadian, mis. nama gang/blok/titik pencacah');

            // Kapan kejadian ditemukan/terjadi (opsional, khusus bangunan liar).
            $table->date('tanggal_kejadian')
                ->nullable()
                ->after('lokasi_teks')
                ->comment('Tanggal kejadian/mulai ditemukan, khusus laporan bangunan liar');

            // Tingkat kerusakan hanya relevan untuk jenis "kerusakan".
            $table->enum('tingkat', ['rusak_ringan', 'rusak_berat'])->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('laporans', function (Blueprint $table) {
            $table->foreignId('perumahan_id')->change();
            $table->dropConstrainedForeignId('kelurahan_id');
            $table->dropColumn(['jenis', 'lokasi_teks', 'tanggal_kejadian']);
            $table->enum('tingkat', ['rusak_ringan', 'rusak_berat'])->nullable(false)->change();
        });
    }
};
