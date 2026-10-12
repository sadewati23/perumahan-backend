<?php

namespace Tests\Feature;

use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Laporan;
use App\Models\Perumahan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerumahanTest extends TestCase
{
    use RefreshDatabase;

    private function petugas(): User
    {
        return User::factory()->create(['role' => 'petugas', 'is_active' => true]);
    }

    private function wilayah(): Kelurahan
    {
        $kecamatan = Kecamatan::create(['nama' => 'Coblong', 'kabupaten_kota' => 'Kota Bandung']);

        return Kelurahan::create(['kecamatan_id' => $kecamatan->id, 'nama' => 'Dago']);
    }

    public function test_tamu_tidak_bisa_membuka_data_perumahan(): void
    {
        $this->get(route('perumahan.index'))->assertRedirect(route('login'));
    }

    public function test_petugas_bisa_menyimpan_perumahan_beserta_psu_dan_tipe_unit(): void
    {
        $kelurahan = $this->wilayah();

        $response = $this->actingAs($this->petugas())->post(route('perumahan.store'), [
            'nama' => 'Perumahan Uji Coba',
            'pengembang' => 'PT Uji',
            'kelurahan_id' => $kelurahan->id,
            'alamat' => 'Jl. Uji No. 1',
            'rt' => '001',
            'rw' => '002',
            'latitude' => -6.7320,
            'longitude' => 108.5523,
            'luas_lahan' => 20000,
            'jumlah_unit' => 100,
            'status' => 'subsidi',
            'tahun_pembuatan' => 2020,
            'tahun_selesai' => 2022,
            'jumlah_rth' => 2,
            'luas_rth' => 2000,
            'kdb' => 60,
            'kdh' => 30,
            'ada_lahan_pemakaman' => 1,
            'luas_lahan_pemakaman' => 1000,
            'lokasi_lahan_pemakaman' => 'Blok A',
            'psu' => [
                ['jenis' => 'Jalan Lingkungan', 'jumlah' => 1500, 'satuan' => 'meter', 'kondisi' => 'baik'],
            ],
            'tipe_units' => [
                ['tipe' => 'Tipe 30', 'jumlah_unit' => 60, 'luas_tanah' => 60, 'luas_bangunan' => 30],
                ['tipe' => 'Tipe 36', 'jumlah_unit' => 40, 'luas_tanah' => 72, 'luas_bangunan' => 36],
            ],
        ]);

        $perumahan = Perumahan::first();

        $response->assertRedirect(route('perumahan.show', $perumahan));
        $this->assertSame('Perumahan Uji Coba', $perumahan->nama);
        $this->assertSame('subsidi', $perumahan->status);
        $this->assertTrue($perumahan->ada_lahan_pemakaman);
        $this->assertCount(1, $perumahan->psu);
        $this->assertCount(2, $perumahan->tipeUnits);
        $this->assertSame(100, $perumahan->total_unit_tipe);
    }

    public function test_edit_perumahan_tidak_memutus_relasi_laporan_ke_psu(): void
    {
        $kelurahan = $this->wilayah();
        $perumahan = Perumahan::create([
            'nama' => 'Perumahan PSU Terlink',
            'kelurahan_id' => $kelurahan->id,
            'luas_lahan' => 5000,
            'jumlah_unit' => 50,
            'status' => 'komersil',
        ]);

        $psuJalan = $perumahan->psu()->create([
            'jenis' => 'Jalan Lingkungan', 'jumlah' => 1500, 'satuan' => 'meter', 'kondisi' => 'baik',
        ]);
        $psuDrainase = $perumahan->psu()->create([
            'jenis' => 'Saluran Drainase', 'jumlah' => 300, 'satuan' => 'meter', 'kondisi' => 'rusak_ringan',
        ]);

        $laporan = Laporan::create([
            'kode' => 'LPR-UJI-0001',
            'jenis' => 'kerusakan',
            'perumahan_id' => $perumahan->id,
            'psu_perumahan_id' => $psuJalan->id,
            'nama_pelapor' => 'Warga Uji',
            'judul' => 'Jalan berlubang',
            'deskripsi' => 'Jalan blok C berlubang.',
            'tingkat' => 'rusak_ringan',
            'status' => 'baru',
        ]);

        // Edit: PSU jalan tetap (di-update), PSU drainase dihapus dari form,
        // dan satu PSU baru ditambahkan.
        $this->actingAs($this->petugas())
            ->put(route('perumahan.update', $perumahan), [
                'nama' => 'Perumahan PSU Terlink',
                'kelurahan_id' => $kelurahan->id,
                'luas_lahan' => 5000,
                'jumlah_unit' => 50,
                'status' => 'komersil',
                'psu' => [
                    ['id' => $psuJalan->id, 'jenis' => 'Jalan Lingkungan', 'jumlah' => 1600, 'satuan' => 'meter', 'kondisi' => 'baik'],
                    ['jenis' => 'Penerangan Jalan Umum', 'jumlah' => 10, 'satuan' => 'titik', 'kondisi' => 'baik'],
                ],
            ])
            ->assertRedirect(route('perumahan.show', $perumahan));

        $perumahan->refresh();
        $this->assertCount(2, $perumahan->psu);
        // Baris lama dipertahankan dengan id yang sama (bukan delete-create ulang).
        $this->assertTrue($perumahan->psu->contains('id', $psuJalan->id));
        $this->assertFalse($perumahan->psu->contains('id', $psuDrainase->id));
        $this->assertSame(1600.0, (float) $psuJalan->fresh()->jumlah);

        // Relasi laporan -> PSU TIDAK terputus oleh operasi simpan.
        $this->assertSame($psuJalan->id, $laporan->fresh()->psu_perumahan_id);
    }

    public function test_validasi_menolak_tahun_selesai_lebih_awal(): void
    {
        $kelurahan = $this->wilayah();

        $this->actingAs($this->petugas())
            ->post(route('perumahan.store'), [
                'nama' => 'Perumahan Salah Tahun',
                'kelurahan_id' => $kelurahan->id,
                'luas_lahan' => 1000,
                'jumlah_unit' => 10,
                'status' => 'komersil',
                'tahun_pembuatan' => 2022,
                'tahun_selesai' => 2019,
            ])
            ->assertSessionHasErrors('tahun_selesai');

        $this->assertSame(0, Perumahan::count());
    }

    public function test_perumahan_bisa_diubah_dan_dihapus(): void
    {
        $kelurahan = $this->wilayah();
        $perumahan = Perumahan::create([
            'nama' => 'Perumahan Lama',
            'kelurahan_id' => $kelurahan->id,
            'luas_lahan' => 5000,
            'jumlah_unit' => 50,
            'status' => 'komersil',
        ]);

        $this->actingAs($this->petugas())
            ->put(route('perumahan.update', $perumahan), [
                'nama' => 'Perumahan Baru',
                'kelurahan_id' => $kelurahan->id,
                'luas_lahan' => 6000,
                'jumlah_unit' => 70,
                'status' => 'subsidi',
            ])
            ->assertRedirect(route('perumahan.show', $perumahan));

        $this->assertSame('Perumahan Baru', $perumahan->fresh()->nama);

        $this->actingAs($this->petugas())
            ->delete(route('perumahan.destroy', $perumahan))
            ->assertRedirect(route('perumahan.index'));

        // Hard delete permanen: baris hilang total dari database.
        $this->assertDatabaseMissing('perumahans', ['id' => $perumahan->id]);
        $this->assertSame(0, Perumahan::count());
    }

    public function test_hapus_perumahan_menghapus_psu_dan_tipe_unit_ikutan(): void
    {
        $kelurahan = $this->wilayah();
        $perumahan = Perumahan::create([
            'nama' => 'Perumahan Beranak',
            'kelurahan_id' => $kelurahan->id,
            'luas_lahan' => 5000,
            'jumlah_unit' => 50,
            'status' => 'komersil',
        ]);

        $psu = $perumahan->psu()->create([
            'jenis' => 'Jalan Lingkungan',
            'jumlah' => 1500,
            'satuan' => 'meter',
            'kondisi' => 'baik',
        ]);
        $tipe = $perumahan->tipeUnits()->create([
            'tipe' => 'Tipe 36',
            'jumlah_unit' => 50,
        ]);

        $this->actingAs($this->petugas())
            ->delete(route('perumahan.destroy', $perumahan))
            ->assertRedirect(route('perumahan.index'));

        // Data induk & detail ikut terhapus permanen (FK cascadeOnDelete).
        $this->assertDatabaseMissing('perumahans', ['id' => $perumahan->id]);
        $this->assertDatabaseMissing('psu_perumahans', ['id' => $psu->id]);
        $this->assertDatabaseMissing('tipe_units', ['id' => $tipe->id]);
    }

    public function test_validasi_bisnis_luas_rth_tidak_boleh_melebihi_luas_lahan(): void
    {
        $kelurahan = $this->wilayah();

        $this->actingAs($this->petugas())
            ->post(route('perumahan.store'), [
                'nama' => 'Perumahan RTH Melebihi',
                'kelurahan_id' => $kelurahan->id,
                'luas_lahan' => 1000,
                'luas_rth' => 2000,
                'jumlah_unit' => 10,
                'status' => 'komersil',
            ])
            ->assertSessionHasErrors('luas_rth');

        $this->assertSame(0, Perumahan::count());
    }

    public function test_validasi_bisnis_total_tipe_unit_harus_cocok_dengan_jumlah_unit(): void
    {
        $kelurahan = $this->wilayah();

        // Total tipe unit 90 != jumlah unit 100 -> ditolak.
        $this->actingAs($this->petugas())
            ->post(route('perumahan.store'), [
                'nama' => 'Perumahan Tipe Tidak Cocok',
                'kelurahan_id' => $kelurahan->id,
                'luas_lahan' => 5000,
                'jumlah_unit' => 100,
                'status' => 'komersil',
                'tipe_units' => [
                    ['tipe' => 'Tipe 30', 'jumlah_unit' => 50],
                    ['tipe' => 'Tipe 36', 'jumlah_unit' => 40],
                ],
            ])
            ->assertSessionHasErrors('tipe_units');

        $this->assertSame(0, Perumahan::count());

        // Total tipe unit 100 == jumlah unit 100 -> diterima.
        $this->actingAs($this->petugas())
            ->post(route('perumahan.store'), [
                'nama' => 'Perumahan Tipe Cocok',
                'kelurahan_id' => $kelurahan->id,
                'luas_lahan' => 5000,
                'jumlah_unit' => 100,
                'status' => 'komersil',
                'tipe_units' => [
                    ['tipe' => 'Tipe 30', 'jumlah_unit' => 50],
                    ['tipe' => 'Tipe 36', 'jumlah_unit' => 50],
                ],
            ])
            ->assertSessionDoesntHaveErrors('tipe_units');

        $this->assertSame(1, Perumahan::count());
    }

    public function test_validasi_koordinat_harus_dalam_batas_wilayah_peta(): void
    {
        $kelurahan = $this->wilayah();
        $batas = config('peta.batas');

        // Koordinat di luar batas wilayah kerja -> ditolak.
        $this->actingAs($this->petugas())
            ->post(route('perumahan.store'), [
                'nama' => 'Perumahan Koordinat Liar',
                'kelurahan_id' => $kelurahan->id,
                'luas_lahan' => 5000,
                'jumlah_unit' => 50,
                'status' => 'komersil',
                'latitude' => -6.9, // di luar lat_min batas
                'longitude' => 107.6, // di luar lng_min batas
            ])
            ->assertSessionHasErrors(['latitude', 'longitude']);

        $this->assertSame(0, Perumahan::count());

        // Koordinat dalam batas (pusat peta) -> diterima.
        $this->actingAs($this->petugas())
            ->post(route('perumahan.store'), [
                'nama' => 'Perumahan Koordinat Pas',
                'kelurahan_id' => $kelurahan->id,
                'luas_lahan' => 5000,
                'jumlah_unit' => 50,
                'status' => 'komersil',
                'latitude' => config('peta.latitude'),
                'longitude' => config('peta.longitude'),
            ])
            ->assertSessionDoesntHaveErrors(['latitude', 'longitude']);

        $this->assertSame(1, Perumahan::count());
    }

    public function test_geojson_menyertakan_titik_yang_punya_koordinat(): void
    {
        $kelurahan = $this->wilayah();
        Perumahan::create([
            'nama' => 'Perumahan Berkordinat',
            'kelurahan_id' => $kelurahan->id,
            'luas_lahan' => 5000,
            'jumlah_unit' => 50,
            'status' => 'komersil',
            'latitude' => -6.9,
            'longitude' => 107.6,
        ]);
        Perumahan::create([
            'nama' => 'Perumahan Tanpa Koordinat',
            'kelurahan_id' => $kelurahan->id,
            'luas_lahan' => 5000,
            'jumlah_unit' => 50,
            'status' => 'komersil',
        ]);

        $this->actingAs($this->petugas())
            ->get(route('perumahan.geojson'))
            ->assertOk()
            ->assertJsonCount(1, 'features')
            ->assertJsonPath('features.0.geometry.coordinates.0', 107.6);
    }

    public function test_hanya_admin_yang_bisa_mengakses_manajemen_pengguna(): void
    {
        $this->actingAs($this->petugas())->get(route('users.index'))->assertForbidden();

        $admin = User::factory()->create(['role' => 'admin']);
        $this->actingAs($admin)->get(route('users.index'))->assertOk();
    }

    public function test_peta_sebaran_menyediakan_fokus_kecamatan(): void
    {
        $kecamatan = Kecamatan::create(['nama' => 'Mandalasari', 'kabupaten_kota' => 'Kota Bandung']);
        $kelurahan = Kelurahan::create(['kecamatan_id' => $kecamatan->id, 'nama' => 'Cibangkong']);

        Perumahan::create([
            'nama' => 'Perumahan Peta Titik',
            'kelurahan_id' => $kelurahan->id,
            'luas_lahan' => 5000,
            'jumlah_unit' => 10,
            'status' => 'komersil',
            'latitude' => -6.9,
            'longitude' => 107.6,
        ]);

        $this->actingAs($this->petugas())
            ->get(route('perumahan.peta'))
            ->assertOk()
            ->assertSee('Fokus Kecamatan')
            ->assertSee('Mandalasari')
            // Data koordinat titik kecamatan ikut terkirim ke JS.
            ->assertSee('-6.9,107.6');

        // Parameter ?kecamatan=ID diterima dan memfokuskan peta saat dibuka.
        $this->actingAs($this->petugas())
            ->get(route('perumahan.peta', ['kecamatan' => $kecamatan->id]))
            ->assertOk()
            ->assertSee('const fokusAwal = '.$kecamatan->id.';');
    }
}
