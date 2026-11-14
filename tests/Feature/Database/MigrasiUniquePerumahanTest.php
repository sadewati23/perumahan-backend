<?php

namespace Tests\Feature\Database;

use App\Models\Kecamatan;
use App\Models\Kelurahan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class MigrasiUniquePerumahanTest extends TestCase
{
    use RefreshDatabase;

    private const INDEX = 'perumahans_nama_kelurahan_unique';

    private function migration(): Migration
    {
        return require database_path('migrations/2026_11_12_000001_add_unique_nama_kelurahan_to_perumahans_table.php');
    }

    private function wilayah(): Kelurahan
    {
        $kecamatan = Kecamatan::create(['nama' => 'Coblong', 'kabupaten_kota' => 'Kota Bandung']);

        return Kelurahan::create(['kecamatan_id' => $kecamatan->id, 'nama' => 'Dago']);
    }

    public function test_migration_gagal_dengan_pesan_jelas_bila_ada_duplikat(): void
    {
        $kelurahan = $this->wilayah();

        // Migration sudah diterapkan oleh RefreshDatabase; lepas index dulu agar
        // baris duplikat bisa disisipkan (kondisi database produksi saat ini).
        Schema::table('perumahans', fn (Blueprint $table) => $table->dropUnique(self::INDEX));

        DB::table('perumahans')->insert([
            ['nama' => 'Perumahan Kembar', 'kelurahan_id' => $kelurahan->id],
            ['nama' => 'Perumahan Kembar', 'kelurahan_id' => $kelurahan->id],
        ]);

        $galat = null;

        try {
            $this->migration()->up();
        } catch (RuntimeException $exception) {
            $galat = $exception;
        }

        $this->assertNotNull($galat, 'Migration seharusnya berhenti karena masih ada duplikat.');
        $this->assertStringContainsString('perumahan:cek-duplikat', $galat->getMessage());
        $this->assertStringContainsString('Perumahan Kembar', $galat->getMessage());

        // Tidak ada perubahan skema maupun data.
        $this->assertFalse(
            collect(Schema::getIndexes('perumahans'))->pluck('name')->contains(self::INDEX),
        );
        $this->assertSame(2, DB::table('perumahans')->count());
    }

    public function test_migration_membuat_unique_index_bila_tidak_ada_duplikat(): void
    {
        $this->wilayah();

        Schema::table('perumahans', fn (Blueprint $table) => $table->dropUnique(self::INDEX));

        $this->migration()->up();

        $index = collect(Schema::getIndexes('perumahans'))
            ->firstWhere('name', self::INDEX);

        $this->assertNotNull($index);
        $this->assertTrue((bool) $index['unique']);
        $this->assertSame(['nama', 'kelurahan_id'], $index['columns']);
    }
}
