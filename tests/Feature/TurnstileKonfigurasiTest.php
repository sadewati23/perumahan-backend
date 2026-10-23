<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Perumahan;
use App\Models\User;
use Tests\TestCase;

class TurnstileKonfigurasiTest extends TestCase
{
    use RefreshDatabase;

    public function test_kunci_uji_mengenali_pola_resmi_dan_menolak_kunci_asli(): void
    {
        foreach ([
            '1x00000000000000000000AA',
            '2x00000000000000000000AB',
            '3x00000000000000000000FF',
            '1x00000000000000000000BB',
            '1x0000000000000000000000000000000AA',
        ] as $key) {
            $this->assertTrue(\App\Support\TurnstileKonfigurasi::kunciUji($key));
        }

        $this->assertFalse(\App\Support\TurnstileKonfigurasi::kunciUji('0x4AAAAAAAcontohkuncibukanuji1'));
        $this->assertFalse(\App\Support\TurnstileKonfigurasi::kunciUji(''));
        $this->assertFalse(\App\Support\TurnstileKonfigurasi::kunciUji(null));
    }

    public function test_produksi_dengan_kunci_uji_menyembunyikan_widget_dan_mencatat_sekali(): void
    {
        $this->buatPerumahan();
        $this->app['env'] = 'production';
        config([
            'services.turnstile.site_key' => '1x00000000000000000000AA',
            'services.turnstile.secret' => '1x0000000000000000000000000000000AA',
        ]);
        Log::spy();

        $this->get(route('lapor.create'))
            ->assertOk()
            ->assertDontSee('cf-turnstile')
            ->assertDontSee('challenges.cloudflare.com');
        $this->get(route('cek-laporan.index'))
            ->assertOk()
            ->assertDontSee('cf-turnstile')
            ->assertDontSee('challenges.cloudflare.com');

        $this->post(route('lapor.store'), [
            '_token' => csrf_token(),
            'jenis' => 'pengaduan',
            'nama_pelapor' => 'Penguji',
            'judul' => 'Pengaduan uji',
            'deskripsi' => 'Isi pengaduan uji tanpa token.',
        ])->assertRedirect(route('lapor.create'));

        $this->get(route('lapor.create'));
        Log::shouldHaveReceived('critical')
            ->with('turnstile.kunci_uji_di_produksi', ['akibat' => 'CAPTCHA dinonaktifkan'])
            ->once();
    }

    public function test_non_produksi_dengan_kunci_uji_tetap_menampilkan_widget_dan_mewajibkan_token(): void
    {
        $this->buatPerumahan();
        config([
            'services.turnstile.site_key' => '1x00000000000000000000AA',
            'services.turnstile.secret' => '1x0000000000000000000000000000000AA',
        ]);

        $this->get(route('lapor.create'))->assertOk()->assertSee('cf-turnstile');
        $this->post(route('lapor.store'), [
            '_token' => csrf_token(),
            'jenis' => 'pengaduan',
            'nama_pelapor' => 'Penguji',
            'judul' => 'Pengaduan uji',
            'deskripsi' => 'Isi pengaduan uji tanpa token.',
        ])->assertSessionHasErrors('cf-turnstile-response');
    }

    public function test_produksi_dengan_kunci_asli_menampilkan_widget_dan_memverifikasi_token(): void
    {
        $this->buatPerumahan();
        $this->app['env'] = 'production';
        config([
            'services.turnstile.site_key' => '0x4AAAAAAAsitekunciujiasli',
            'services.turnstile.secret' => '0x4AAAAAAAsecretkunciujiasli',
        ]);
        Http::fakeSequence()
            ->push(['success' => false])
            ->push(['success' => true]);

        $this->get(route('lapor.create'))->assertOk()->assertSee('cf-turnstile');
        $data = [
            '_token' => csrf_token(),
            'jenis' => 'pengaduan',
            'nama_pelapor' => 'Penguji',
            'judul' => 'Pengaduan uji',
            'deskripsi' => 'Isi pengaduan uji dengan token.',
            'cf-turnstile-response' => 'token-gagal-lalu-sukses',
        ];
        $this->post(route('lapor.store'), $data)->assertSessionHasErrors('cf-turnstile-response');
        $this->post(route('lapor.store'), $data)->assertRedirect(route('lapor.create'));
    }

    public function test_banner_hanya_tampil_untuk_admin_dalam_produksi_tanpa_perlindungan(): void
    {
        $pesan = 'Perlindungan CAPTCHA pada form laporan belum aktif';
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $petugas = User::factory()->create(['role' => 'petugas', 'is_active' => true]);

        $this->app['env'] = 'production';
        config(['services.turnstile.site_key' => null, 'services.turnstile.secret' => null]);
        $this->actingAs($admin)->get(route('dashboard'))->assertSee($pesan);
        $this->actingAs($petugas)->get(route('dashboard'))->assertDontSee($pesan);
        Auth::logout();
        $this->get(route('dashboard'))->assertRedirect(route('login'));

        $this->app['env'] = 'testing';
        $this->actingAs($admin)->get(route('dashboard'))->assertDontSee($pesan);

        $this->app['env'] = 'production';
        config([
            'services.turnstile.site_key' => '0x4AAAAAAAsitekunciujiasli',
            'services.turnstile.secret' => '0x4AAAAAAAsecretkunciujiasli',
        ]);
        $this->actingAs($admin)->get(route('dashboard'))->assertDontSee($pesan);
    }

    private function buatPerumahan(): void
    {
        $kecamatan = Kecamatan::create(['nama' => 'Kecamatan Uji', 'kabupaten_kota' => 'Kota Uji']);
        $kelurahan = Kelurahan::create(['kecamatan_id' => $kecamatan->id, 'nama' => 'Kelurahan Uji']);
        Perumahan::create([
            'nama' => 'Perumahan Uji',
            'kelurahan_id' => $kelurahan->id,
            'luas_lahan' => 1000,
            'jumlah_unit' => 10,
            'status' => 'komersil',
        ]);
    }
}
