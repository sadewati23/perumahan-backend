<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporans', function (Blueprint $table) {
            $table->decimal('persentase_kerusakan', 5, 2)->nullable()
                ->after('tingkat')->comment('Perkiraan persentase kerusakan (0-100)');
            $table->decimal('panjang_kerusakan', 12, 2)->nullable()
                ->after('persentase_kerusakan')->comment('Panjang kerusakan (meter)');
        });
    }

    public function down(): void
    {
        Schema::table('laporans', function (Blueprint $table) {
            $table->dropColumn(['persentase_kerusakan', 'panjang_kerusakan']);
        });
    }
};
