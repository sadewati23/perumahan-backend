<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laporans', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 24)->unique()->comment('Nomor tiket laporan, mis. LPR-20261005-A1B2');
            $table->foreignId('perumahan_id')->constrained('perumahans')->cascadeOnDelete();
            $table->foreignId('psu_perumahan_id')->nullable()->constrained('psu_perumahans')->nullOnDelete();

            // Identitas pelapor (publik, tanpa login)
            $table->string('nama_pelapor', 100);
            $table->string('kontak_pelapor', 100)->comment('No. HP / WA / email yang bisa dihubungi');

            // Isi laporan
            $table->string('judul', 150);
            $table->text('deskripsi');
            $table->enum('tingkat', ['rusak_ringan', 'rusak_berat']);

            // Penanganan oleh petugas
            $table->enum('status', ['baru', 'diproses', 'selesai', 'ditolak'])->default('baru');
            $table->text('catatan_petugas')->nullable();
            $table->foreignId('ditangani_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('ditangani_pada')->nullable();

            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporans');
    }
};
