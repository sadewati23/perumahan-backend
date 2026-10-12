git status<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('laporans', function (Blueprint $table) {
            // Filter daftar laporan admin sering memakai jenis & perumahan_id.
            $table->index('jenis');
            $table->index('perumahan_id');
        });
    }

    public function down(): void
    {
        Schema::table('laporans', function (Blueprint $table) {
            $table->dropIndex(['jenis']);
            $table->dropIndex(['perumahan_id']);
        });
    }
};
