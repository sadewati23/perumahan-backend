<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tipe_units', function (Blueprint $table) {
            $table->dropColumn('harga');
        });
    }

    public function down(): void
    {
        Schema::table('tipe_units', function (Blueprint $table) {
            $table->decimal('harga', 15, 2)->nullable()->after('luas_bangunan')->comment('rupiah');
        });
    }
};
