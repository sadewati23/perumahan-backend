<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // MySQL/MariaDB perlu memperluas daftar enum yang ada. SQLite memakai
        // rebuild tabel saat tipe kolom diubah agar CHECK enum lama ikut hilang.
        $driver = Schema::getConnection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement("ALTER TABLE perumahans MODIFY status ENUM('subsidi', 'komersil', 'campuran') NOT NULL DEFAULT 'komersil'");
        } elseif (Schema::getConnection()->getDriverName() === 'sqlite') {
            Schema::table('perumahans', function (Blueprint $table): void {
                $table->string('status')->default('komersil')->change();
            });
        }
    }

    public function down(): void
    {
        // Tidak menghapus nilai campuran agar rollback tidak merusak data yang sudah
        // memakai kategori baru. Rollback migration ini sengaja tidak-op.
    }
};
