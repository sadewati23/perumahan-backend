<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('perumahans', function (Blueprint $table) {
            $table->json('batas_polygon')->nullable()->after('longitude')->comment('Batas area perumahan (array koordinat [lat,lng])');
        });
    }

    public function down(): void
    {
        Schema::table('perumahans', function (Blueprint $table) {
            $table->dropColumn('batas_polygon');
        });
    }
};
