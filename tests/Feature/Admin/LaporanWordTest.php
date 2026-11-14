<?php

namespace Tests\Feature\Admin;

use App\Models\Laporan;
use App\Models\LaporanFoto;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LaporanWordTest extends TestCase
{
    use RefreshDatabase;

    private function laporan(): Laporan
    {
        return Laporan::create([
            'kode' => Laporan::buatKode('pengaduan'),
            'jenis' => 'pengaduan',
            'nama_pelapor' => 'Warga Uji',
            'judul' => 'Laporan Word Uji',
            'deskripsi' => 'Deskripsi laporan Word.',
            'status' => 'baru',
        ]);
    }

    public function test_admin_dapat_mengunduh_laporan_word(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['role' => 'petugas']);
        $laporan = $this->laporan();
        Storage::disk('local')->putFileAs('laporan', UploadedFile::fake()->image('uji.jpg'), 'uji.jpg');
        LaporanFoto::create([
            'laporan_id' => $laporan->id,
            'path' => 'laporan/uji.jpg',
            'tipe' => LaporanFoto::TIPE_LAPORAN,
        ]);

        $this->actingAs($user)->get(route('laporan.word', $laporan))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/msword; charset=UTF-8')
            ->assertHeader('Content-Disposition', 'attachment; filename="laporan-'.$laporan->kode.'.doc"')
            ->assertSee('Laporan Word Uji')
            ->assertSee('data:image/jpeg;base64,', false)
            // Ukuran kertas A4 untuk Microsoft Word (section Word, bukan @page biasa).
            ->assertSee('@page WordSection1', false)
            ->assertSee('size: 595.3pt 841.9pt', false)
            ->assertSee('mso-page-orientation: portrait', false)
            ->assertDontSee('tombol-cetak');
    }

    public function test_admin_dapat_mengunduh_rekap_word_dengan_filter(): void
    {
        $user = User::factory()->create(['role' => 'petugas']);
        $laporan = $this->laporan();

        $this->actingAs($user)->get(route('laporan.cetak-rekap.word', ['status' => 'baru']))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/msword; charset=UTF-8')
            ->assertHeader('Content-Disposition', 'attachment; filename="rekap-laporan.doc"')
            ->assertSee($laporan->kode)
            // Ukuran kertas A4 landscape untuk Microsoft Word.
            ->assertSee('@page WordSection1', false)
            ->assertSee('size: 1190.6pt 841.9pt', false)
            ->assertSee('mso-page-orientation: landscape', false)
            ->assertDontSee('tombol-cetak');
    }

    public function test_halaman_cetak_browser_menggunakan_kertas_a4(): void
    {
        $user = User::factory()->create(['role' => 'petugas']);
        $laporan = $this->laporan();

        // Cetak satu laporan di browser memakai @page size: A4.
        $this->actingAs($user)->get(route('laporan.cetak', $laporan))
            ->assertOk()
            ->assertSee('@page { size: A4', false);

        // Cetak rekap di browser juga memakai @page size: A4.
        $this->actingAs($user)->get(route('laporan.cetak-rekap'))
            ->assertOk()
            ->assertSee('@page { size: A4', false);
    }
}
