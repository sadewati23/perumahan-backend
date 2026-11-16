<?php

namespace Tests\Feature\Konfigurasi;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnimasiTest extends TestCase
{
    use RefreshDatabase;

    public function test_css_memiliki_guard_reduced_motion_spinner_dan_print(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));

        $this->assertStringContainsString('@media (prefers-reduced-motion: reduce)', $css);
        $this->assertStringContainsString('.animate-spin', $css);
        $this->assertStringContainsString('@media print', $css);
    }

    public function test_keyframe_baru_hanya_mengubah_transform_dan_opacity(): void
    {
        $css = file_get_contents(resource_path('css/app.css'));
        preg_match_all('/@keyframes\s+([\w-]+)\s*\{([^}]*)\}/s', $css, $matches, PREG_SET_ORDER);

        $lama = ['kilau-teks', 'naik-masuk', 'batang-tumbuh', 'kartu-muncul'];
        $terlarang = ['color', 'background', 'border', 'shadow', 'fill', 'stroke', 'filter'];

        foreach ($matches as [$blok, $nama, $isi]) {
            if (in_array($nama, $lama, true)) continue;

            foreach ($terlarang as $properti) {
                $this->assertDoesNotMatchRegularExpression('/\b'.preg_quote($properti, '/').'\b/i', $isi, $nama);
            }
            preg_match_all('/([a-z-]+)\s*:/i', $isi, $properties);
            foreach ($properties[1] as $property) {
                $this->assertContains($property, ['transform', 'opacity', 'translate', 'scale'], $nama);
            }
        }
    }

    public function test_hapus_laporan_dashboard_memakai_form_konfirmasi_global(): void
    {
        $html = file_get_contents(resource_path('views/admin/dashboard.blade.php'));

        $this->assertStringContainsString('method="POST" action="{{ route(\'laporan.destroy\', $laporan) }}"', $html);
        $this->assertStringContainsString('@method(\'DELETE\')', $html);
        $this->assertStringContainsString('data-confirm="Hapus laporan', $html);
        $this->assertStringNotContainsString('data-action="', $html);
    }

    public function test_hapus_laporan_admin_memakai_form_konfirmasi_global(): void
    {
        $html = file_get_contents(resource_path('views/admin/laporan/index.blade.php'));

        $this->assertSame(2, substr_count($html, 'method="POST" action="{{ route(\'laporan.destroy\', $laporan) }}"'));
        $this->assertSame(2, substr_count($html, '@method(\'DELETE\')'));
        $this->assertStringNotContainsString('data-action="', $html);
    }

    public function test_modul_javascript_umpan_balik_terhubung(): void
    {
        $publik = file_get_contents(resource_path('js/publik.js'));
        $admin = file_get_contents(resource_path('js/admin.js'));
        $flash = file_get_contents(resource_path('js/umum/flash.js'));

        $this->assertStringContainsString("from './umum/flash'", $publik);
        $this->assertStringContainsString("from './umum/flash'", $admin);
        $this->assertStringContainsString('toast-keluar', $flash);
        $this->assertStringContainsString('prefers-reduced-motion', $flash);

        // Animasi dekoratif (hitung naik & reveal saat menggulir) sengaja
        // dihapus; angka dirender langsung dari server.
        $this->assertStringNotContainsString('hitung-naik', $publik);
        $this->assertStringNotContainsString('reveal-scroll', $publik);
        $this->assertFileDoesNotExist(resource_path('js/umum/hitung-naik.js'));
        $this->assertFileDoesNotExist(resource_path('js/publik/reveal-scroll.js'));
    }
}
