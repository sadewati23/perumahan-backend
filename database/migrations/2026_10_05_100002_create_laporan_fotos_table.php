<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('laporan_fotos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('laporan_id')->constrained('laporans')->cascadeOnDelete();
            $table->string('path')->comment('Path foto relatif disk public, mis. laporan/abc123.jpg');
            $table->text('deskripsi_aksi_selesai')->nullable()->comment('Deskripsi ringkas kegiatan/selesai yang dilakukan atas kerusakan.');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('laporan_fotos');
    }
};
