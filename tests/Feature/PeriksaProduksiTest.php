<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class PeriksaProduksiTest extends TestCase
{
    use RefreshDatabase;

    private ?string $temporaryPublicPath = null;

    protected function tearDown(): void
    {
        if ($this->temporaryPublicPath !== null) {
            File::deleteDirectory($this->temporaryPublicPath);
            $this->app->usePublicPath(base_path('public'));
        }

        parent::tearDown();
    }

    public function test_konfigurasi_sehat_lulus_dengan_public_path_sementara(): void
    {
        $this->konfigurasiSehat();
        $this->siapkanPublicPathSementara();

        $this->assertSame(0, Artisan::call('produksi:periksa'));
        $this->assertStringContainsString('Ringkasan:', Artisan::output());
    }

    public function test_app_debug_true_menghasilkan_satu_temuan_kritis(): void
    {
        $this->konfigurasiSehat(['app.debug' => true]);
        $this->siapkanPublicPathSementara();

        $this->assertSame(1, Artisan::call('produksi:periksa'));
        $this->assertStringContainsString('APP_DEBUG', Artisan::output());
    }

    public function test_trusted_proxy_wildcard_menghasilkan_temuan_kritis(): void
    {
        $this->konfigurasiSehat(['trustedproxy.proxies' => ['*']]);
        $this->siapkanPublicPathSementara();

        $this->assertSame(1, Artisan::call('produksi:periksa'));
        $this->assertStringContainsString('TRUSTED_PROXIES', Artisan::output());
    }

    public function test_kunci_uji_turnstile_menghasilkan_temuan_kritis(): void
    {
        $this->konfigurasiSehat([
            'services.turnstile.site_key' => '1x00000000000000000000AA',
            'services.turnstile.secret' => '1x0000000000000000000000000000000AA',
        ]);
        $this->siapkanPublicPathSementara();

        $this->assertSame(1, Artisan::call('produksi:periksa'));
        $this->assertStringContainsString('kunci uji', Artisan::output());
    }

    public function test_secret_terisi_tanpa_site_key_menghasilkan_temuan_kritis(): void
    {
        $this->konfigurasiSehat([
            'services.turnstile.site_key' => null,
            'services.turnstile.secret' => '0x4AAAAAAAsecretkunciujiasli',
        ]);
        $this->siapkanPublicPathSementara();

        $this->assertSame(1, Artisan::call('produksi:periksa'));
        $this->assertStringContainsString('site key kosong', Artisan::output());
    }

    public function test_akun_bawaan_menghasilkan_temuan_kritis(): void
    {
        $this->konfigurasiSehat();
        $this->siapkanPublicPathSementara();
        User::factory()->create(['email' => 'admin@perumahan.test']);

        $this->assertSame(1, Artisan::call('produksi:periksa'));
        $this->assertStringContainsString('Akun bawaan', Artisan::output());
    }

    public function test_peringatan_tanpa_kritis_tidak_menggagalkan_perintah(): void
    {
        $this->konfigurasiSehat([
            'mail.default' => 'log',
            'laporan.hapus_otomatis_sejak' => null,
        ]);
        $this->siapkanPublicPathSementara();

        $this->assertSame(0, Artisan::call('produksi:periksa'));
        $this->assertStringContainsString('PERINGATAN', Artisan::output());
    }

    public function test_output_tidak_memuat_nilai_kunci_yang_diperiksa(): void
    {
        $siteKey = '1x00000000000000000000AA';
        $secret = '1x0000000000000000000000000000000AA';
        $this->konfigurasiSehat([
            'services.turnstile.site_key' => $siteKey,
            'services.turnstile.secret' => $secret,
        ]);
        $this->siapkanPublicPathSementara();

        Artisan::call('produksi:periksa');
        $output = Artisan::output();
        $this->assertStringNotContainsString($siteKey, $output);
        $this->assertStringNotContainsString($secret, $output);
    }

    public function test_database_tidak_terjangkau_menjadi_peringatan_dan_tidak_crash(): void
    {
        $this->konfigurasiSehat(['database.default' => 'koneksi-tidak-ada']);
        $this->siapkanPublicPathSementara();

        try {
            $this->assertSame(0, Artisan::call('produksi:periksa'));
            $this->assertStringContainsString('tidak dapat diperiksa', Artisan::output());
        } finally {
            config(['database.default' => 'sqlite']);
        }
    }

    public function test_config_admin_tidak_ada_tetap_menyelesaikan_pemeriksaan(): void
    {
        $this->konfigurasiSehat();
        $this->siapkanPublicPathSementara();

        $this->assertSame(0, Artisan::call('produksi:periksa'));
        $this->assertStringNotContainsString('Alamat login admin', Artisan::output());
    }

    private function konfigurasiSehat(array $overrides = []): void
    {
        config(array_merge([
            'app.env' => 'production',
            'app.debug' => false,
            'app.key' => 'testing-application-key',
            'app.url' => 'https://contoh.test',
            'app.timezone' => 'Asia/Jakarta',
            'session.secure' => true,
            'trustedproxy.proxies' => null,
            'mail.default' => 'smtp',
            'services.turnstile.site_key' => '0x4AAAAAAAsitekunciujiasli',
            'services.turnstile.secret' => '0x4AAAAAAAsecretkunciujiasli',
            'laporan.hapus_otomatis' => true,
            'laporan.hapus_otomatis_sejak' => '2026-01-01',
        ], $overrides));
    }

    private function siapkanPublicPathSementara(): void
    {
        $this->temporaryPublicPath = storage_path('framework/testing/public-produksi-'.uniqid('', true));
        File::makeDirectory($this->temporaryPublicPath.'/build', 0755, true);
        File::makeDirectory($this->temporaryPublicPath.'/storage', 0755, true);
        File::put($this->temporaryPublicPath.'/build/manifest.json', '{}');
        $this->app->usePublicPath($this->temporaryPublicPath);
    }
}
