<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kelurahans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('kecamatan_id')->constrained('kecamatans')->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('kode', 20)->nullable()->unique();
            $table->string('nama');
            $table->string('kode_pos', 10)->nullable();
            $table->timestamps();

            $table->index(['kecamatan_id', 'nama']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelurahans');
    }
};
