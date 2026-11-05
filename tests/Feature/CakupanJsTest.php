<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Menjaga cakupan hook JavaScript per area setelah pemisahan entri publik/admin.
 *
 * Aturan (dari inventaris Fase 0 refaktorisasi):
 *  - layouts/publik dan seluruh view publik/*          → entri publik.js
 *  - layouts/admin dan SEMUA admin/auth/*              → entri admin.js
 *  - components/, partials/, vendor/pagination dipakai KEDUA area
 *
 * Modul dipetakan dari foldernya: js/umum/* termasuk kedua entri,
 * js/admin/* hanya entri admin, js/publik/* hanya entri publik.
 * Hook = yang dicari modul lewat querySelector(All)/getElementById/closest:
 * atribut data-*, #id, .kelas, dan name^="..." (input dinamis).
 * Atribut blade yang tidak pernah dicari modul manapun tidak dihitung
 * sebagai hook (mis. data-maks="3" dibaca lewat dataset, bukan selector).
 */
class CakupanJsTest extends TestCase
{
    public function test_setiap_hook_blade_admin_tercakup_entri_admin(): void
    {
        $this->tentukanCakupan('admin');
    }

    public function test_setiap_hook_blade_publik_tercakup_entri_publik(): void
    {
        $this->tentukanCakupan('publik');
    }

    public function test_entri_publik_tidak_mengimpor_modul_admin_saja(): void
    {
        $publik = (string) file_get_contents(resource_path('js/publik.js'));

        $this->assertStringNotContainsString("'./admin/", $publik, 'Entri publik tidak boleh mengimpor modul admin-saja.');
        $this->assertStringNotContainsString("'../admin/", $publik, 'Entri publik tidak boleh mengimpor modul admin-saja.');

        // Dari sisi lain: entri admin tidak boleh memuat modul publik-saja.
        $admin = (string) file_get_contents(resource_path('js/admin.js'));
        $this->assertStringNotContainsString("'./publik/", $admin, 'Entri admin tidak boleh mengimpor modul publik-saja.');
        $this->assertStringNotContainsString("'../publik/", $admin, 'Entri admin tidak boleh mengimpor modul publik-saja.');
    }

    public function test_vite_dan_layout_merujuk_entri_yang_benar(): void
    {
        $vite = (string) file_get_contents(base_path('vite.config.js'));
        $this->assertStringContainsString("'resources/js/publik.js'", $vite);
        $this->assertStringContainsString("'resources/js/admin.js'", $vite);
        $this->assertStringContainsString("'resources/css/app.css'", $vite);

        $this->assertStringContainsString('resources/js/admin.js', $this->isiView('layouts/admin'));
        $this->assertStringContainsString('resources/js/publik.js', $this->isiView('layouts/publik'));

        foreach (glob(resource_path('views/admin/auth/*.blade.php')) ?: [] as $blade) {
            $this->assertStringContainsString(
                'resources/js/admin.js',
                (string) file_get_contents($blade),
                basename($blade).' (halaman auth memuat entri admin).'
            );
        }

        $this->assertFileDoesNotExist(resource_path('js/app.js'), 'Entri lama app.js harus sudah dihapus.');
    }

    private function tentukanCakupan(string $area): void
    {
        $modul = $this->modulJs();
        $semua = array_merge($modul['publik'], $modul['admin'], $modul['umum']);

        // Token yang pernah dirujuk SETIAP modul manapun (selector/dataset):
        // hanya token ini yang dianggap hook. Selebihnya adalah konfigurasi
        // statis di blade (mis. data-kirim-mobile) yang tidak dibaca selector.
        $ratakata = function (array $grup) {
            $kamus = ['data' => [], 'id' => [], 'kelas' => [], 'nama' => []];
            foreach ($grup as $isiModul) {
                foreach ($this->hookModul($isiModul) as $hook) {
                    $jenis = strtok($hook, ':');
                    $kamus[$jenis][] = substr($hook, strlen($jenis) + 1);
                }
            }

            return array_map(fn ($d) => array_unique($d), $kamus);
        };

        [$kamusTersedia, $kamusSemua] = [
            $ratakata($area === 'admin' ? array_merge($modul['admin'], $modul['umum']) : array_merge($modul['publik'], $modul['umum'])),
            $ratakata($semua),
        ];

        $diperiksa = 0;

        foreach ($this->bladeArea($area) as $relatif => $isi) {
            foreach ($this->hookBlade($isi, $kamusTersedia['kelas']) as $hook) {
                $jenis = strtok($hook, ':');
                $token = substr($hook, strlen($jenis) + 1);

                if (! in_array($token, $kamusSemua[$jenis] ?? [], true)) {
                    continue; // tidak pernah dibaca modul manapun → bukan hook
                }

                $diperiksa++;
                $this->assertContains(
                    $token,
                    $kamusTersedia[$jenis],
                    "Blade [$relatif] memakai hook [$hook] tetapi entri $area tidak memuat modul yang mencarinya."
                );
            }
        }

        // Pastikan pemeriksaan bukan no-op: hook ikonik tiap area harus diproses.
        $ikonik = $area === 'admin'
            ? ['data:confirm', 'data:toggle-sidebar', 'data:dynamic-rows', 'data:field', 'data:flash', 'data:hitung']
            : ['data:field', 'data:flash', 'data:copy', 'data:foto-upload', 'kelas:reveal-scroll', 'data:hitung'];
        foreach ($ikonik as $hookIkonik) {
            $jenis = strtok($hookIkonik, ':');
            $token = substr($hookIkonik, strlen($jenis) + 1);
            $this->assertContains(
                $token,
                $kamusTersedia[$jenis],
                "Modul $area seharusnya tetap menangani hook [$hookIkonik]."
            );
        }
    }

    /**
     * Modul JS per area: ['publik' => [isi], 'admin' => [isi], 'umum' => [isi]].
     *
     * @return array<string, list<string>>
     */
    private function modulJs(): array
    {
        $hasil = ['publik' => [], 'admin' => [], 'umum' => []];

        $berkas = array_merge(
            glob(resource_path('js/*.js')) ?: [],
            glob(resource_path('js/*/*.js')) ?: []
        );

        foreach ($berkas as $jalur) {
            $nama = str_replace('\\', '/', $jalur);

            if (str_ends_with($nama, '/js/bootstrap.js')) {
                continue; // tidak diimpor entri manapun
            }

            $relatif = str_replace(resource_path('js/'), '', $nama);
            $area = match (true) {
                str_starts_with($relatif, 'umum/') => 'umum',
                str_starts_with($relatif, 'admin/') || $relatif === 'admin.js' => 'admin',
                str_starts_with($relatif, 'publik/') || $relatif === 'publik.js' => 'publik',
                default => 'umum',
            };

            $hasil[$area][] = (string) file_get_contents($jalur);
        }

        return $hasil;
    }

    /**
     * @return list<string>
     */
    private function hookModul(string $isi): array
    {
        $hooks = [];

        preg_match_all('/\b(?:querySelector(?:All)?|closest)\(\s*([\'"])(.*?)\1/s', $isi, $selector);
        preg_match_all('/\bgetElementById\(\s*([\'"])(.*?)\1/s', $isi, $byId);
        preg_match_all('/\bdataset\.([a-zA-Z][a-zA-Z0-9_]+)/', $isi, $dataset);

        foreach ($selector[2] ?? [] as $s) {
            if (preg_match('/data-([a-z0-9-]+)/', $s, $m) === 1) {
                // Selector 'form[data-confirm]': cukup nama atribut pertama yang dicari.
                $hooks[] = 'data:'.$m[1];
            } elseif ($s !== '' && $s[0] === '#') {
                $hooks[] = 'id:'.substr($s, 1);
            } elseif ($s !== '' && $s[0] === '.') {
                $hooks[] = 'kelas:'.substr($s, 1);
            } elseif (preg_match('/name\^=\s*[\'"]?([a-z0-9_-]+)/i', $s, $m) === 1) {
                $hooks[] = 'nama:'.$m[1];
            }
        }

        // Dynamic getElementById (dsb) dilewatkan; hanya literal penuh.
        foreach ($byId[2] ?? [] as $id) {
            if (preg_match('/^[a-zA-Z0-9_-]+$/', $id) === 1) {
                $hooks[] = 'id:'.$id;
            }
        }

        foreach ($dataset[1] ?? [] as $properti) {
            $hooks[] = 'data:'.strtolower((string) preg_replace('/(?<!^)[A-Z]/', '-$0', $properti));
        }

        return array_values(array_unique($hooks));
    }

    /**
     * Hook pada isi Blade: atribut data-*, id="...", name^="...", dan
     * kelas yang benar-benar dicari beberapa modul (mis. .reveal-scroll).
     *
     * @param  list<string>  $kelasDicari
     * @return list<string>
     */
    private function hookBlade(string $isi, array $kelasDicari = []): array
    {
        $hooks = [];
        $tanpaSkrip = preg_replace('/<script\b[^>]*>.*?<\/script>/s', '', $isi) ?: $isi;

        preg_match_all('/data-([a-z0-9-]+)/', $tanpaSkrip, $data);
        $hooks = array_merge($hooks, array_map(fn ($d) => 'data:'.$d, array_unique($data[1] ?? [])));

        preg_match_all('/id="([a-zA-Z0-9_-]+)"/', $tanpaSkrip, $ids);
        $hooks = array_merge($hooks, array_map(fn ($i) => 'id:'.$i, array_unique($ids[1] ?? [])));

        preg_match_all('/name\^="([^"\{]+)"/', $tanpaSkrip, $names);
        $hooks = array_merge($hooks, array_map(fn ($n) => 'nama:'.rtrim($n, '\'"/'), array_unique($names[1] ?? [])));

        preg_match_all('/class="([^"\{]+)"/', $tanpaSkrip, $kelas);
        $dipakai = array_flip($kelasDicari);
        foreach ($kelas[1] ?? [] as $barisKelas) {
            foreach (preg_split('/\s+/', $barisKelas) ?: [] as $kelasTunggal) {
                $kelasTunggal = trim($kelasTunggal);
                if ($kelasTunggal !== '' && isset($dipakai[$kelasTunggal])) {
                    $hooks[] = 'kelas:'.$kelasTunggal;
                }
            }
        }

        return array_values(array_unique($hooks));
    }

    /**
     * Isi Blade milik suatu area (layout + seluruh view folder area itu).
     *
     * @return array<string, string>
     */
    private function bladeArea(string $area): array
    {
        $hasil = [];

        if ($area === 'admin') {
            $hasil['layouts/admin'] = $this->isiView('layouts/admin');
            foreach (glob(resource_path('views/admin/**/*.blade.php')) ?: [] as $blade) {
                $relatif = str_replace('\\', '/', $blade);
                $hasil[str_replace(resource_path('views/'), '', $relatif)] = (string) file_get_contents($blade);
            }
        }

        if ($area === 'publik') {
            $hasil['layouts/publik'] = $this->isiView('layouts/publik');
            foreach (glob(resource_path('views/publik/**/*.blade.php')) ?: [] as $blade) {
                $deretan = str_replace('\\', '/', $blade);
                $hasil[str_replace(resource_path('views/'), '', $deretan)] = (string) file_get_contents($blade);
            }
        }

        return $hasil;
    }

    private function isiView(string $nama): string
    {
        return (string) file_get_contents(resource_path('views/'.$nama.'.blade.php'));
    }
}
