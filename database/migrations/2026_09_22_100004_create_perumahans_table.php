<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('perumahans', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('pengembang')->nullable();

            // Lokasi
            $table->foreignId('kelurahan_id')->constrained('kelurahans')->cascadeOnUpdate()->restrictOnDelete();
            $table->string('alamat')->nullable();
            $table->string('rt', 5)->nullable();
            $table->string('rw', 5)->nullable();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Luasan & unit
            $table->decimal('luas_lahan', 12, 2)->default(0)->comment('m2');
            $table->unsignedInteger('jumlah_unit')->default(0);
            $table->enum('status', ['subsidi', 'komersil'])->default('komersil');

            // Waktu pembangunan
            $table->year('tahun_pembuatan')->nullable();
            $table->year('tahun_selesai')->nullable();

            // RTH & koefisien
            $table->unsignedInteger('jumlah_rth')->default(0)->comment('jumlah titik/lokasi RTH');
            $table->decimal('luas_rth', 12, 2)->default(0)->comment('m2');
            $table->decimal('kdb', 5, 2)->nullable()->comment('Koefisien Dasar Bangunan (%)');
            $table->decimal('kdh', 5, 2)->nullable()->comment('Koefisien Dasar Hijau (%)');

            // Lahan pemakaman
            $table->boolean('ada_lahan_pemakaman')->default(false);
            $table->decimal('luas_lahan_pemakaman', 12, 2)->nullable()->comment('m2');
            $table->string('lokasi_lahan_pemakaman')->nullable();

            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'tahun_selesai']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('perumahans');
    }
};
