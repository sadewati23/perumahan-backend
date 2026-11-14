<?php

namespace Tests\Feature\Admin;

use App\Models\Laporan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaporanRiwayatTest extends TestCase
{
    use RefreshDatabase;

    private function laporan(string $status = 'baru'): Laporan
    {
        return Laporan::create([
            'kode' => Laporan::buatKode(),
            'jenis' => Laporan::JENIS_PENGADUAN,
            'nama_pelapor' => 'Warga Uji',
            'judul' => 'Laporan Uji Riwayat',
            'deskripsi' => 'Uraian laporan uji riwayat status.',
            'status' => $status,
        ]);
    }

    private function petugas(string $nama = 'Petugas Riwayat'): User
    {
        return User::factory()->create([
            'name' => $nama,
            'role' => 'petugas',
            'is_active' => true,
        ]);
    }

    public function test_riwayat_tercatat_saat_status_berubah_tetapi_tidak_saat_catatan_saja(): void
    {
        $laporan = $this->laporan();
        $petugas = $this->petugas();

        $this->actingAs($petugas)
            ->put(route('laporan.update', $laporan), [
                'status' => 'diproses',
                'catatan_petugas' => 'Tim sedang mengecek lokasi.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('laporan_riwayats', [
            'laporan_id' => $laporan->id,
            'user_id' => $petugas->id,
            'status_lama' => 'baru',
            'status_baru' => 'diproses',
            'catatan' => 'Tim sedang mengecek lokasi.',
        ]);
        $this->assertSame(1, $laporan->riwayats()->count());

        // Catatan berubah, status tetap: tidak boleh menambah riwayat.
        $this->actingAs($petugas)
            ->put(route('laporan.update', $laporan), [
                'status' => 'diproses',
                'catatan_petugas' => 'Catatan diperbarui tanpa ubah status.',
            ])
            ->assertRedirect();

        $this->assertSame(1, $laporan->riwayats()->count());
        $this->assertSame('Catatan diperbarui tanpa ubah status.', $laporan->fresh()->catatan_petugas);

        // Perubahan status berikutnya menambah satu baris baru.
        $this->actingAs($petugas)
            ->put(route('laporan.update', $laporan), [
                'status' => 'selesai',
            ])
            ->assertRedirect();

        $this->assertSame(2, $laporan->riwayats()->count());

        $riwayat = $laporan->riwayats()->get();
        $this->assertSame(['baru', 'diproses'], $riwayat->pluck('status_lama')->all());
        $this->assertSame(['diproses', 'selesai'], $riwayat->pluck('status_baru')->all());
        $this->assertNull($riwayat->last()->catatan);
    }

    public function test_riwayat_tampil_kronologis_di_halaman_detail(): void
    {
        $laporan = $this->laporan();
        $petugas = $this->petugas('Petugas Tampil');

        $this->actingAs($petugas)->put(route('laporan.update', $laporan), [
            'status' => 'diproses',
            'catatan_petugas' => 'Pemeriksaan awal.',
        ]);
        $this->actingAs($petugas)->put(route('laporan.update', $laporan), [
            'status' => 'selesai',
            'catatan_petugas' => 'Perbaikan selesai.',
        ]);

        $this->actingAs($petugas)
            ->get(route('laporan.show', $laporan))
            ->assertOk()
            ->assertSee('Perubahan Status')
            ->assertSee('Baru &rarr; Diproses', false)
            ->assertSee('Diproses &rarr; Selesai', false)
            ->assertSee('Petugas Tampil')
            ->assertSee('Pemeriksaan awal.')
            ->assertSee('Perbaikan selesai.');
    }

    public function test_riwayat_ikut_terhapus_bersama_laporan_dan_user_boleh_null(): void
    {
        $laporan = $this->laporan();
        $petugas = $this->petugas();

        $this->actingAs($petugas)->put(route('laporan.update', $laporan), [
            'status' => 'ditolak',
        ]);

        $this->assertDatabaseCount('laporan_riwayats', 1);

        $petugas->delete();
        $this->assertDatabaseHas('laporan_riwayats', [
            'laporan_id' => $laporan->id,
            'user_id' => null,
        ]);

        $laporan->hapusPermanen();
        $this->assertDatabaseCount('laporan_riwayats', 0);
    }
}
