<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tipe_units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('perumahan_id')->constrained('perumahans')->cascadeOnDelete();
            $table->string('tipe', 50);
            $table->unsignedInteger('jumlah_unit')->default(0);
            $table->decimal('luas_tanah', 10, 2)->nullable()->comment('m2');
            $table->decimal('luas_bangunan', 10, 2)->nullable()->comment('m2');
            $table->decimal('harga', 15, 2)->nullable()->comment('rupiah');
            $table->text('keterangan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tipe_units');
    }
};
