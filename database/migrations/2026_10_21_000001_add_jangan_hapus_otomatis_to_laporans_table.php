<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('laporans', function (Blueprint $table): void {
            $table->boolean('jangan_hapus_otomatis')->default(false)->after('selesai_pada');
        });
    }

    public function down(): void
    {
        Schema::table('laporans', fn (Blueprint $table) => $table->dropColumn('jangan_hapus_otomatis'));
    }
};
