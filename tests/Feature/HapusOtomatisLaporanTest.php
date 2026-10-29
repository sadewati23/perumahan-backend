<?php

namespace Tests\Feature;

use App\Models\Laporan;
use App\Models\LaporanFoto;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Mockery;
use Tests\TestCase;

class HapusOtomatisLaporanTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void { Carbon::setTestNow(); config(['laporan.hapus_otomatis' => true, 'laporan.hapus_otomatis_hari' => 3, 'laporan.hapus_otomatis_sejak' => null]); parent::tearDown(); }

    private function laporan(array $data = []): Laporan
    {
        return Laporan::create(array_merge(['kode' => Laporan::buatKode('pengaduan'), 'jenis' => 'pengaduan', 'nama_pelapor' => 'Rahasia', 'kontak_pelapor' => '08123', 'judul' => 'Judul publik', 'deskripsi' => 'Deskripsi rahasia lengkap', 'status' => 'baru'], $data));
    }

    private function foto(Laporan $laporan, string $path, string $tipe): void
    {
        LaporanFoto::create(['laporan_id' => $laporan->id, 'path' => $path, 'tipe' => $tipe]);
        Storage::disk('public')->put($path, 'foto');
    }

    public function test_01_perintah_menghapus_laporan_dan_semua_foto(): void
    {
        Storage::fake('public'); Carbon::setTestNow('2026-10-21 12:00'); config(['laporan.hapus_otomatis_sejak' => '2026-10-01']);
        $l = $this->laporan(['status' => 'selesai', 'selesai_pada' => now()->subDays(3)->subMinute()]); $this->foto($l, 'a.jpg', 'laporan'); $this->foto($l, 'b.jpg', 'perbaikan');
        $lain = $this->laporan(); $this->foto($lain, 'lain.jpg', 'laporan');
        Artisan::call('laporan:hapus-kedaluwarsa');
        $this->assertDatabaseMissing('laporans', ['id' => $l->id]); $this->assertDatabaseCount('laporan_fotos', 1); Storage::disk('public')->assertMissing('a.jpg'); Storage::disk('public')->assertMissing('b.jpg'); Storage::disk('public')->assertExists('lain.jpg');
    }

    public function test_02_tidak_menghapus_status_tidak_selesai_ditahan_atau_null(): void
    {
        Carbon::setTestNow('2026-10-21 12:00');
        foreach (['diproses', 'baru', 'ditolak'] as $status) $this->laporan(['status' => $status, 'selesai_pada' => now()->subDays(30)]);
        $this->laporan(['status' => 'selesai', 'selesai_pada' => now()->subDays(2)->subHours(23)]);
        $this->laporan(['status' => 'selesai', 'selesai_pada' => now()->subDays(4), 'jangan_hapus_otomatis' => true]);
        $this->laporan(['status' => 'selesai', 'selesai_pada' => null]);
        Artisan::call('laporan:hapus-kedaluwarsa'); $this->assertSame(6, Laporan::count());
    }

    public function test_03_pengaman_data_lama_sejak(): void
    {
        config(['laporan.hapus_otomatis_sejak' => '2026-10-20']); Carbon::setTestNow('2026-10-25');
        $lama = $this->laporan(['status' => 'selesai', 'selesai_pada' => '2026-10-10']); $baru = $this->laporan(['status' => 'selesai', 'selesai_pada' => '2026-10-21']);
        $this->assertNull($lama->jatuh_tempo_hapus); Artisan::call('laporan:hapus-kedaluwarsa'); $this->assertDatabaseHas('laporans', ['id' => $lama->id]); $this->assertDatabaseMissing('laporans', ['id' => $baru->id]);
    }

    public function test_04_nilai_hari_invalid_jatuh_ke_default_tiga(): void
    {
        Carbon::setTestNow('2026-10-25');
        foreach ([0, '', -4, 'abc'] as $nilai) { config(['laporan.hapus_otomatis_hari' => 3]); $l = $this->laporan(['status' => 'selesai', 'selesai_pada' => now()->subDays(2)]); $this->assertSame(3, config('laporan.hapus_otomatis_hari')); $this->assertDatabaseHas('laporans', ['id' => $l->id]); }
        config(['laporan.hapus_otomatis_hari' => 3]); $l = $this->laporan(['status' => 'selesai', 'selesai_pada' => now()->subDays(3)->subMinute()]); Artisan::call('laporan:hapus-kedaluwarsa'); $this->assertDatabaseMissing('laporans', ['id' => $l->id]);
    }

    public function test_05_dry_run_dan_maks(): void
    {
        config(['laporan.hapus_otomatis_sejak' => null]); Carbon::setTestNow('2026-10-21'); foreach (range(1, 3) as $i) $this->laporan(['status' => 'selesai', 'selesai_pada' => now()->subDays(4)]);
        Artisan::call('laporan:hapus-kedaluwarsa', ['--dry-run' => true]); $this->assertSame(3, Laporan::count()); $this->assertStringContainsString('3 laporan', Artisan::output());
        Artisan::call('laporan:hapus-kedaluwarsa', ['--maks' => 2]); $this->assertSame(1, Laporan::count()); Artisan::call('laporan:hapus-kedaluwarsa'); $this->assertSame(0, Laporan::count());
    }

    public function test_06_fitur_nonaktif_tidak_menghapus_dan_tidak_membatasi_pencarian(): void
    {
        config(['laporan.hapus_otomatis' => false]); $l = $this->laporan(['status' => 'selesai', 'selesai_pada' => now()->subDays(30)]); Artisan::call('laporan:hapus-kedaluwarsa'); $this->assertDatabaseHas('laporans', ['id' => $l->id]); $this->assertTrue(Laporan::dapatDicari()->whereKey($l)->exists());
    }

    public function test_07_file_gagal_dicatat_dan_laporan_lain_tetap_diproses(): void
    {
        Log::spy(); $l = $this->laporan(['status' => 'selesai', 'selesai_pada' => now()->subDays(4)]); $this->foto($l, 'gagal.jpg', 'laporan'); $lain = $this->laporan(['status' => 'selesai', 'selesai_pada' => now()->subDays(4)]);
        $disk = Mockery::mock(); $disk->shouldReceive('delete')->andThrow(new \RuntimeException('gagal')); Storage::shouldReceive('disk')->andReturn($disk); $l->hapusPermanen(); $lain->hapusPermanen(); Log::shouldHaveReceived('warning')->with('laporan.file_yatim', ['path' => ['gagal.jpg']]); $this->assertDatabaseMissing('laporans', ['id' => $l->id]);
    }

    public function test_08_update_selesai_mengisi_tanggal_dan_pesan_dan_tahan_menghilangkan(): void
    {
        $user = User::factory()->create(['role' => 'petugas', 'is_active' => true]); $l = $this->laporan(); $r = $this->actingAs($user)->put(route('laporan.update', $l), ['status' => 'selesai']); $r->assertSessionHas('success', fn ($v) => str_contains($v, 'dihapus otomatis pada')); $l->refresh(); $r = $this->actingAs($user)->put(route('laporan.update', $l), ['status' => 'selesai', 'jangan_hapus_otomatis' => 1]); $r->assertSessionMissing('errors'); $this->assertStringNotContainsString('dihapus otomatis pada', session('success'));
    }

    public function test_09_pencarian_mengikuti_batas_dan_status(): void
    {
        config(['laporan.hapus_otomatis_sejak' => null]); Carbon::setTestNow('2026-10-21'); $aman = $this->laporan(['status' => 'selesai', 'selesai_pada' => now()->subDays(2)]); $jatuh = $this->laporan(['status' => 'selesai', 'selesai_pada' => now()->subDays(3)->subMinute()]); $tahan = $this->laporan(['status' => 'selesai', 'selesai_pada' => now()->subDays(30), 'jangan_hapus_otomatis' => 1]); $proses = $this->laporan(['status' => 'diproses', 'created_at' => now()->subDays(30)]);
        $this->post(route('cek-laporan.cari'), ['kode' => $aman->kode])->assertOk(); $this->post(route('cek-laporan.cari'), ['kode' => $jatuh->kode])->assertSessionHasErrors('kode'); $this->post(route('cek-laporan.cari'), ['kode' => $tahan->kode])->assertOk(); $this->post(route('cek-laporan.cari'), ['kode' => $proses->kode])->assertOk();
    }

    public function test_10_hasil_publik_tidak_memuat_data_rahasia_dan_header(): void
    {
        $l = $this->laporan(); $r = $this->post(route('cek-laporan.cari'), ['kode' => $l->kode]); $r->assertOk()->assertHeader('X-Robots-Tag', 'noindex, nofollow')->assertHeader('Referrer-Policy', 'no-referrer')->assertDontSee('Rahasia')->assertDontSee('08123')->assertDontSee('Deskripsi rahasia'); $this->assertStringContainsString('no-store', $r->headers->get('Cache-Control'));
    }

    public function test_11_normalisasi_input_honeypot_turnstile_dan_throttle(): void
    {
        $l = $this->laporan(); $this->post(route('cek-laporan.cari'), ['kode' => ' '.strtolower($l->kode).' '])->assertOk(); $this->post(route('cek-laporan.cari'), ['kode' => ['x']])->assertSessionHasErrors('kode'); $this->post(route('cek-laporan.cari'), ['kode' => $l->kode, 'jawaban_cadangan' => 'bot'])->assertSessionHasErrors('kode');
        config(['services.turnstile.secret' => 'x']); \Illuminate\Support\Facades\Http::fake(['challenges.cloudflare.com/*' => ['success' => false]]); $this->post(route('cek-laporan.cari'), ['kode' => $l->kode, 'cf-turnstile-response' => 'x'])->assertSessionHasErrors('cf-turnstile-response');
        config(['services.turnstile.secret' => null]); for ($i = 0; $i < 6; $i++) $this->post(route('cek-laporan.cari'), ['kode' => $l->kode]); $this->post(route('cek-laporan.cari'), ['kode' => $l->kode])->assertStatus(429);
    }

    public function test_12_kode_baru_unik_dan_kode_lama_tetap_ditemukan(): void
    {
        $codes = collect(range(1, 200))->map(fn () => Laporan::buatKode())->unique(); $this->assertCount(200, $codes); $this->assertMatchesRegularExpression('/^(LPR|PGN|BLR)-\d{8}-[A-HJKMNP-TV-Z2-9]{8}$/', $codes->first()); $l = $this->laporan(['kode' => 'LPR-20200101-A1B2']); $this->post(route('cek-laporan.cari'), ['kode' => $l->kode])->assertOk();
    }

    public function test_13_tampilan_admin_cetak_dan_scheduler(): void
    {
        config(['laporan.hapus_otomatis_sejak' => null]); Carbon::setTestNow('2026-10-21'); $user = User::factory()->create(['role' => 'petugas', 'is_active' => true]); $l = $this->laporan(['status' => 'selesai', 'selesai_pada' => now()->subDays(2)]); $this->actingAs($user)->get(route('laporan.show', $l))->assertSee('akan dihapus otomatis'); $this->actingAs($user)->get(route('laporan.index'))->assertSee('Dihapus'); $this->actingAs($user)->get(route('laporan.cetak', $l))->assertSee('Simpan hasil cetak'); Artisan::call('schedule:list'); $this->assertStringContainsString('laporan:hapus-kedaluwarsa', Artisan::output());
    }

    public function test_14_destroy_manual_menghapus_semua_file(): void
    {
        Storage::fake('public'); $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]); $l = $this->laporan(); $this->foto($l, 'manual.jpg', 'laporan'); $this->withSession(['auth.password_confirmed_at' => time()])->actingAs($admin)->delete(route('laporan.destroy', $l))->assertRedirect(route('laporan.index')); $this->assertDatabaseMissing('laporans', ['id' => $l->id]); Storage::disk('public')->assertMissing('manual.jpg');
    }

    public function test_peringatan_sejak_kosong_muncul_di_dry_run_dan_normal(): void
    {
        config(['laporan.hapus_otomatis_sejak' => null]); $l = $this->laporan(['status' => 'selesai', 'selesai_pada' => now()->subDays(4)]); Artisan::call('laporan:hapus-kedaluwarsa', ['--dry-run' => true]); $this->assertStringContainsString('LAPORAN_HAPUS_OTOMATIS_SEJAK kosong', Artisan::output()); Log::spy(); Artisan::call('laporan:hapus-kedaluwarsa'); Log::shouldHaveReceived('warning')->with('laporan.hapus_otomatis_tanpa_batas_lama'); $this->assertDatabaseMissing('laporans', ['id' => $l->id]);
    }
}
