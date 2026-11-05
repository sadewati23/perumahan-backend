<?php

namespace Tests\Feature\Konfigurasi;

use Tests\TestCase;

class KonfigurasiContohTest extends TestCase
{
    private function isi(string $path): string
    {
        return file_get_contents(base_path($path));
    }

    private function envMemiliki(string $isi, string $nama): bool
    {
        return preg_match('/^\s*#?\s*'.preg_quote($nama, '/').'=/m', $isi) === 1;
    }

    public function test_env_contoh_tidak_memakai_kunci_uji_turnstile(): void
    {
        $env = $this->isi('.env.example');

        $this->assertMatchesRegularExpression('/^TURNSTILE_SITE_KEY=\s*$/m', $env);
        $this->assertMatchesRegularExpression('/^TURNSTILE_SECRET_KEY=\s*$/m', $env);
    }

    public function test_semua_variabel_konfigurasi_didokumentasikan_di_env_contoh(): void
    {
        $env = $this->isi('.env.example');
        $files = ['config/peta.php', 'config/trustedproxy.php'];
        foreach (['config/admin.php', 'config/laporan.php'] as $optional) {
            if (file_exists(base_path($optional))) {
                $files[] = $optional;
            }
        }

        foreach ($files as $file) {
            if (! file_exists(base_path($file))) {
                continue;
            }

            preg_match_all('/env\(\s*[\'\"]([A-Z][A-Z0-9_]*)[\'\"]/', $this->isi($file), $matches);
            foreach (array_unique($matches[1]) as $nama) {
                $this->assertTrue($this->envMemiliki($env, $nama), "Variabel {$nama} dari {$file} belum ada di .env.example.");
            }
        }

        foreach (['TURNSTILE_SITE_KEY', 'TURNSTILE_SECRET_KEY'] as $nama) {
            $this->assertTrue($this->envMemiliki($env, $nama), "Variabel {$nama} dari config/services.php belum ada di .env.example.");
        }
    }

    public function test_readme_mengarah_ke_hosting_dan_melarang_seeder_produksi(): void
    {
        $readme = $this->isi('README.md');

        $this->assertStringContainsString('HOSTING.md', $readme);
        $this->assertStringNotContainsString('db:seed --class=DatabaseSeeder --force', $readme);
        $this->assertStringContainsString('WilayahSeeder', $readme);
        $this->assertStringContainsString('STORAGE_URL=/storage', $readme);
    }

    public function test_hosting_ada_di_root_project(): void
    {
        $this->assertFileExists(base_path('HOSTING.md'));
    }

    public function test_konfigurasi_laporan_wajib_memiliki_baris_env_kosong(): void
    {
        if (! file_exists(base_path('config/laporan.php'))) {
            $this->markTestSkipped('config/laporan.php tidak ada.');
        }

        $env = $this->isi('.env.example');
        foreach (['LAPORAN_HAPUS_OTOMATIS', 'LAPORAN_HAPUS_OTOMATIS_HARI'] as $nama) {
            $this->assertMatchesRegularExpression('/^'.preg_quote($nama, '/').'=/m', $env);
        }
        $this->assertMatchesRegularExpression('/^LAPORAN_HAPUS_OTOMATIS_SEJAK=\s*$/m', $env);
    }

    public function test_konfigurasi_admin_bersyarat_memiliki_env_dan_readme(): void
    {
        if (! file_exists(base_path('config/admin.php'))) {
            $this->markTestSkipped('config/admin.php tidak ada.');
        }

        $env = $this->isi('.env.example');
        $this->assertMatchesRegularExpression('/^ADMIN_LOGIN_PATH=login\s*$/m', $env);
        $this->assertMatchesRegularExpression('/^ADMIN_LOGIN_TERSEMBUNYI=false\s*$/m', $env);
    }
}
