<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporans', function (Blueprint $table): void {
            $table->text('deskripsi_aksi_selesai')->nullable()
                ->after('catatan_petugas')
                ->comment('Ringkasan aksi yang selesai dilakukan terhadap laporan.');
            $table->timestamp('selesai_pada')->nullable()
                ->after('deskripsi_aksi_selesai')
                ->comment('Kapan laporan ditandai selesai pertama kali.');
        });
    }

    public function down(): void
    {
        Schema::table('laporans', function (Blueprint $table): void {
            $table->dropColumn(['deskripsi_aksi_selesai', 'selesai_pada']);
        });
    }
};
