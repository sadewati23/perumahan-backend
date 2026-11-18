<?php

namespace Tests\Feature\Keamanan;

use App\Http\Middleware\Admin\EnsureUserHasRole;
use App\Http\Middleware\Admin\EnsureUserIsActive;
use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Laporan;
use App\Models\Perumahan;
use App\Models\User;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Regresi otorisasi rute area admin.
 *
 * Tujuan: mencegah Vertical Privilege Escalation akibat rute admin baru
 * yang lupa diberi middleware. Tiga lapis penjagaan:
 *
 * 1. Setiap rute ber-URI "admin" wajib memuat `auth` + `active`.
 * 2. Rute admin di luar DAFTAR PUTIH petugas wajib memuat `role:admin`.
 *    Route admin baru akan membuat test ini GAGAL sampai developer secara
 *    sadar memutuskan: masukkan ke daftar putih (memang boleh untuk petugas)
 *    atau tambahkan middleware `role:admin`.
 * 3. Perilaku: tamu diarahkan ke login, petugas mendapat 403. Route yang
 *    memakai route model binding diberi record nyata agar 404 (binding)
 *    tidak menutupi hasil middleware role.
 *
 * Catatan CSRF: Laravel menonaktifkan verifikasi CSRF saat runningUnitTests,
 * jadi POST/PUT/DELETE di sini langsung menguji middleware otorisasi —
 * sama seperti test admin lain yang sudah ada.
 */
class OtorisasiRouteAdminTest extends TestCase
{
    use RefreshDatabase;

    /**
     * DAFTAR PUTIH: rute admin yang memang boleh diakses petugas, diambil
     * dari kondisi routes/admin.php saat test ini ditulis.
     *
     * Semua rute admin di luar daftar ini WAJIB `role:admin`. Menambah rute
     * admin baru tanpa memutuskan salah satu (daftar putih / role:admin)
     * akan menggagalkan test ini dengan sengaja.
     *
     * Sengaja TIDAK memuat: perumahan.destroy, laporan.destroy,
     * kecamatan.destroy, kelurahan.destroy, dan seluruh `users.*`.
     */
    private const DAFTAR_PUTIH_PETUGAS = [
        // Dashboard & konfirmasi password & logout.
        'dashboard',
        'password.confirm',
        'password.confirm.store',
        'logout',

        // Perumahan: baca + tulis, tanpa hapus.
        'perumahan.index',
        'perumahan.create',
        'perumahan.store',
        'perumahan.show',
        'perumahan.edit',
        'perumahan.update',
        'perumahan.peta',
        'perumahan.geojson',
        'perumahan.import',
        'perumahan.import.template-csv',
        'perumahan.import.template-xlsx',
        'perumahan.import.simpan',

        // Laporan: baca + tindak lanjut + foto, tanpa hapus laporan.
        'laporan.index',
        'laporan.cetak-rekap',
        'laporan.cetak-rekap.word',
        'laporan.cetak',
        'laporan.word',
        'laporan.show',
        'laporan.update',
        'laporan.foto',
        'laporan.foto-perbaikan.store',
        'laporan.foto-perbaikan.destroy',

        // Wilayah: lihat + tambah/ubah, tanpa hapus.
        'wilayah.index',
        'kecamatan.store',
        'kecamatan.update',
        'kelurahan.store',
        'kelurahan.update',
    ];

    /**
     * Rute admin tanpa nama: `Route::get('/', fn () => redirect(...))` di
     * routes/admin.php. Kuncinya memakai URI karena tidak punya nama.
     */
    private const RUTE_TANPA_NAMA_DIIZINKAN = [
        'admin',
    ];

    // ------------------------------------------------------------------
    // Helper
    // ------------------------------------------------------------------

    /**
     * Semua rute ber-URI berawalan "admin", termasuk /admin itu sendiri.
     *
     * @return list<Route>
     */
    private function ruteAdmin(): array
    {
        $hasil = [];

        foreach (app('router')->getRoutes() as $route) {
            if (str_starts_with($route->uri(), 'admin')) {
                $hasil[] = $route;
            }
        }

        return $hasil;
    }

    /**
     * Urai middleware rute menjadi nama kelas (alias & grup sudah diurai).
     *
     * @return list<string>
     */
    private function middlewareTerurai(Route $route): array
    {
        return app('router')->gatherRouteMiddleware($route);
    }

    /**
     * Cek apakah daftar middleware memuat kelas tertentu, beserta parameternya.
     *
     * @param  list<string>  $middleware
     */
    private function memuat(array $middleware, string $kelas, ?string $parameter = null): bool
    {
        foreach ($middleware as $entri) {
            [$nama, $param] = array_pad(explode(':', $entri, 2), 2, null);

            if ($nama === $kelas && ($parameter === null || $param === $parameter)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Kunci rute untuk daftar putih: nama rute, atau URI bila tanpa nama.
     */
    private function kunciRute(Route $route): string
    {
        return $route->getName() ?? $route->uri();
    }

    /**
     * Method HTTP efektif (lewati HEAD yang menempel pada GET).
     */
    private function methodEfektif(Route $route): string
    {
        foreach ($route->methods() as $method) {
            if ($method !== 'HEAD') {
                return $method;
            }
        }

        return 'GET';
    }

    /**
     * Record nyata untuk route model binding. Hanya User yang punya factory;
     * model lain dibuat langsung seperti test admin lain di repo ini.
     *
     * @return array<string, mixed>
     */
    private function recordNyata(): array
    {
        $kecamatan = Kecamatan::create(['nama' => 'Kecamatan Uji Otorisasi']);
        $kelurahan = Kelurahan::create([
            'kecamatan_id' => $kecamatan->id,
            'nama' => 'Kelurahan Uji Otorisasi',
        ]);
        $perumahan = Perumahan::create([
            'nama' => 'Perumahan Uji Otorisasi',
            'kelurahan_id' => $kelurahan->id,
            'luas_lahan' => 10000,
            'jumlah_unit' => 80,
            'status' => 'komersil',
        ]);
        $laporan = Laporan::create([
            'kode' => Laporan::buatKode('pengaduan'),
            'jenis' => 'pengaduan',
            'judul' => 'Laporan Uji Otorisasi',
            'deskripsi' => 'Deskripsi laporan uji otorisasi.',
            'nama_pelapor' => 'Warga Uji',
            'status' => 'baru',
        ]);

        return [
            'perumahan' => $perumahan,
            'laporan' => $laporan,
            'kecamatan' => $kecamatan,
            'kelurahan' => $kelurahan,
            'user' => User::factory()->create(['role' => 'petugas', 'is_active' => true]),
        ];
    }

    /**
     * Susun parameter URI dari record nyata sesuai nama parameter rute.
     *
     * @param  array<string, mixed>  $record
     * @return array<string, mixed>
     */
    private function parameterRoute(Route $route, array $record): array
    {
        $parameter = [];

        foreach ($route->parameterNames() as $nama) {
            $this->assertArrayHasKey(
                $nama,
                $record,
                "Parameter rute {{$nama}} pada {$this->kunciRute($route)} belum dipetakan ke record nyata."
            );

            $parameter[$nama] = $record[$nama];
        }

        return $parameter;
    }

    // ------------------------------------------------------------------
    // 1. Semua rute admin wajib auth + active
    // ------------------------------------------------------------------

    public function test_semua_rute_admin_wajib_memuat_auth_dan_active(): void
    {
        $rute = $this->ruteAdmin();

        // Penjaga agar koleksi rute tidak kosong karena salah konfigurasi.
        $this->assertGreaterThanOrEqual(30, count($rute), 'Rute admin tidak terdeteksi dari router.');

        foreach ($rute as $route) {
            $middleware = $this->middlewareTerurai($route);
            $label = $this->kunciRute($route).' ['.$route->uri().']';

            $this->assertTrue(
                $this->memuat($middleware, Authenticate::class),
                "Rute admin {$label} tidak memuat middleware auth."
            );

            $this->assertTrue(
                $this->memuat($middleware, EnsureUserIsActive::class),
                "Rute admin {$label} tidak memuat middleware active (akun nonaktif harus ditolak)."
            );
        }
    }

    // ------------------------------------------------------------------
    // 2. Rute di luar daftar putih wajib role:admin
    // ------------------------------------------------------------------

    public function test_rute_admin_di_luar_daftar_putih_wajib_role_admin(): void
    {
        $rute = $this->ruteAdmin();

        // Daftar putih tidak boleh basi: setiap nama yang diizinkan harus
        // benar-benar ada di router (kecuali kunci URI untuk rute tanpa nama).
        $namaRute = [];
        foreach ($rute as $route) {
            if ($route->getName() !== null) {
                $namaRute[] = $route->getName();
            }
        }

        foreach (self::DAFTAR_PUTIH_PETUGAS as $nama) {
            $this->assertContains(
                $nama,
                $namaRute,
                "Daftar putih memuat rute '{$nama}' yang sudah tidak ada — perbarui daftar putih."
            );
        }

        foreach ($rute as $route) {
            $kunci = $this->kunciRute($route);

            if (in_array($kunci, self::DAFTAR_PUTIH_PETUGAS, true)) {
                continue;
            }

            if (in_array($kunci, self::RUTE_TANPA_NAMA_DIIZINKAN, true)) {
                continue;
            }

            $middleware = $this->middlewareTerurai($route);

            $this->assertTrue(
                $this->memuat($middleware, EnsureUserHasRole::class, 'admin'),
                "Rute admin '{$kunci}' ({$route->uri()}) tidak memuat role:admin dan tidak ada di "
                .'DAFTAR_PUTIH_PETUGAS. Tambahkan middleware role:admin, atau masukkan ke daftar '
                .'putih bila memang boleh diakses petugas.'
            );
        }
    }

    // ------------------------------------------------------------------
    // 3. Perilaku: tamu -> login, petugas -> 403
    // ------------------------------------------------------------------

    public function test_rute_role_admin_menolak_tamu_dan_petugas(): void
    {
        $record = $this->recordNyata();
        $petugas = User::factory()->create(['role' => 'petugas', 'is_active' => true]);

        // Kumpulkan hanya rute yang benar-benar dilindungi role:admin.
        $ruteRoleAdmin = [];
        foreach ($this->ruteAdmin() as $route) {
            if ($this->memuat($this->middlewareTerurai($route), EnsureUserHasRole::class, 'admin')) {
                $ruteRoleAdmin[] = $route;
            }
        }

        $this->assertNotEmpty($ruteRoleAdmin, 'Tidak ada rute role:admin yang terdeteksi.');

        // Pass 1 — tamu: harus diarahkan ke login untuk SETIAP method.
        foreach ($ruteRoleAdmin as $route) {
            $method = $this->methodEfektif($route);
            $url = route($route->getName(), $this->parameterRoute($route, $record));

            $response = $this->call($method, $url);

            $response->assertRedirect(
                route('login'),
                "Tamu tidak diarahkan ke login pada {$method} {$url}."
            );
        }

        // Pass 2 — petugas: harus 403 (middleware role berjalan sebelum
        // controller, jadi aksi destruktif tidak benar-benar dieksekusi).
        foreach ($ruteRoleAdmin as $route) {
            $method = $this->methodEfektif($route);
            $url = route($route->getName(), $this->parameterRoute($route, $record));

            $response = $this->actingAs($petugas)->call($method, $url);

            $response->assertForbidden(
                "Petugas tidak mendapat 403 pada {$method} {$url} — kemungkinan eskalasi vertikal."
            );
        }

        // Bukti tambahan: percobaan petugas tidak mengubah data apa pun.
        $this->assertDatabaseHas('perumahans', ['id' => $record['perumahan']->id]);
        $this->assertDatabaseHas('laporans', ['id' => $record['laporan']->id]);
        $this->assertDatabaseHas('kecamatans', ['id' => $record['kecamatan']->id]);
        $this->assertDatabaseHas('kelurahans', ['id' => $record['kelurahan']->id]);
        $this->assertDatabaseHas('users', ['id' => $record['user']->id]);
    }

    // ------------------------------------------------------------------
    // 4. Penurunan role langsung berlaku pada request berikutnya
    // ------------------------------------------------------------------

    public function test_admin_yang_diturunkan_menjadi_petugas_langsung_tertolak(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        // Login nyata lewat form (password factory: "password").
        $this->post(route('login.attempt'), [
            'username' => $admin->username,
            'password' => 'password',
        ])->assertRedirect();

        $this->get(route('users.index'))->assertOk();

        // Turunkan role langsung di database — sesi hanya menyimpan identitas,
        // bukan peran. Request berikutnya harus membaca role terbaru dari DB.
        DB::table('users')->where('id', $admin->id)->update(['role' => 'petugas']);

        // Dalam satu proses PHPUnit, instance guard masih menyimpan model user
        // dari request sebelumnya. forgetGuards() mensimulasikan request HTTP
        // baru: guard dibuat ulang, user di-resolve lewat sesi (hanya id) lalu
        // dibaca dari database, sehingga role terbaru ikut terbaca.
        $this->app['auth']->forgetGuards();

        $this->get(route('users.index'))->assertForbidden();
        $this->get(route('dashboard'))->assertOk();
    }
}
