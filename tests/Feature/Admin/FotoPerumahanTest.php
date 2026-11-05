<?php

namespace Tests\Feature\Admin;

use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Perumahan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class FotoPerumahanTest extends TestCase
{
    use RefreshDatabase;

    private function wilayah(): Kelurahan
    {
        $kecamatan = Kecamatan::create([
            'nama' => 'Coblong',
            'kabupaten_kota' => 'Kota Bandung',
        ]);

        return Kelurahan::create([
            'kecamatan_id' => $kecamatan->id,
            'nama' => 'Dago',
        ]);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    private function payload(Kelurahan $kelurahan, array $extra = []): array
    {
        return array_merge([
            'nama' => 'Perumahan Foto Uji',
            'kelurahan_id' => $kelurahan->id,
            'luas_lahan' => 5000,
            'jumlah_unit' => 20,
            'status' => 'komersil',
        ], $extra);
    }

    public function test_store_foto_disimpan_dan_tampil_di_halaman_publik(): void
    {
        Storage::fake('public');
        $kelurahan = $this->wilayah();

        $this->actingAs($this->admin())->post(route('perumahan.store'), $this->payload($kelurahan, [
            'foto' => UploadedFile::fake()->image('rumah.jpg', 800, 600),
        ]))->assertRedirect();

        $perumahan = Perumahan::firstOrFail();
        $this->assertStringStartsWith('perumahan/', $perumahan->foto_path);
        Storage::disk('public')->assertExists($perumahan->foto_path);

        foreach ([route('publik.beranda'), route('publik.perumahan.index'), route('publik.perumahan.show', $perumahan)] as $url) {
            $this->get($url)->assertSuccessful()->assertSee('<img', false)->assertSee($perumahan->foto_url, false);
        }
    }

    public function test_store_tanpa_foto_memakai_placeholder(): void
    {
        Storage::fake('public');
        $kelurahan = $this->wilayah();

        $this->actingAs($this->admin())->post(route('perumahan.store'), $this->payload($kelurahan));

        $this->assertNull(Perumahan::firstOrFail()->foto_path);
        $this->get(route('publik.perumahan.index'))->assertSee('data:image/svg+xml', false);
    }

    public function test_exif_foto_perumahan_dibuang(): void
    {
        Storage::fake('public');
        $img = imagecreatetruecolor(20, 20);
        imagefilledrectangle($img, 0, 0, 19, 19, imagecolorallocate($img, 180, 190, 200));
        ob_start();
        imagejpeg($img, null, 90);
        $jpeg = (string) ob_get_clean();
        imagedestroy($img);

        $payload = "Exif\0\0GPSLatitude-RAHASIA";
        $jpegDenganExif = substr($jpeg, 0, 2)
            . "\xFF\xE1".pack('n', 2 + strlen($payload)).$payload
            . substr($jpeg, 2);

        $kelurahan = $this->wilayah();
        $this->actingAs($this->admin())->post(route('perumahan.store'), $this->payload($kelurahan, [
            'foto' => UploadedFile::fake()->createWithContent('rumah.jpg', $jpegDenganExif),
        ]))->assertRedirect();

        $perumahan = Perumahan::firstOrFail();
        $isi = Storage::disk('public')->get($perumahan->foto_path);
        $this->assertStringNotContainsString('GPSLatitude-RAHASIA', $isi);
    }

    public function test_update_foto_mengganti_file_dan_tanpa_foto_mempertahankan_file_lama(): void
    {
        Storage::fake('public');
        $kelurahan = $this->wilayah();
        $this->actingAs($this->admin())->post(route('perumahan.store'), $this->payload($kelurahan, [
            'foto' => UploadedFile::fake()->image('lama.jpg', 800, 600),
        ]));
        $perumahan = Perumahan::firstOrFail();
        $fotoLama = $perumahan->foto_path;

        $this->actingAs($this->admin())->put(route('perumahan.update', $perumahan), $this->payload($kelurahan, [
            'foto' => UploadedFile::fake()->image('baru.jpg', 700, 500),
        ]))->assertRedirect();
        $fotoBaru = $perumahan->fresh()->foto_path;
        Storage::disk('public')->assertMissing($fotoLama);
        Storage::disk('public')->assertExists($fotoBaru);

        $this->actingAs($this->admin())->put(route('perumahan.update', $perumahan), $this->payload($kelurahan))
            ->assertRedirect();
        $this->assertSame($fotoBaru, $perumahan->fresh()->foto_path);
        Storage::disk('public')->assertExists($fotoBaru);
    }

    public function test_foto_tidak_valid_ditolak(): void
    {
        Storage::fake('public');
        $kelurahan = $this->wilayah();
        $aktor = $this->admin();

        foreach ([
            UploadedFile::fake()->create('dokumen.pdf', 100, 'application/pdf'),
            UploadedFile::fake()->create('besar.jpg', 6000, 'image/jpeg'),
            UploadedFile::fake()->image('lebar.jpg', 6001, 10),
        ] as $foto) {
            $this->actingAs($aktor)->post(route('perumahan.store'), $this->payload($kelurahan, ['foto' => $foto]))
                ->assertSessionHasErrors('foto');
        }

        $this->assertSame(0, Perumahan::count());
    }

    public function test_url_foto_mengikuti_konfigurasi_relatif(): void
    {
        config(['filesystems.disks.public.url' => '/perumahan-backend/public/storage']);
        // Storage::fake menerima URL secara eksplisit karena fake local disk tidak
        // menyalin opsi URL dari konfigurasi disk pada Laravel versi ini.
        Storage::fake('public', ['url' => config('filesystems.disks.public.url')]);
        $kelurahan = $this->wilayah();

        $this->actingAs($this->admin())->post(route('perumahan.store'), $this->payload($kelurahan, [
            'foto' => UploadedFile::fake()->image('rumah.jpg', 800, 600),
        ]));
        $perumahan = Perumahan::firstOrFail();

        $this->assertSame('/perumahan-backend/public/storage/'.$perumahan->foto_path, $perumahan->foto_url);
    }

    public function test_form_memperingatkan_bila_storage_link_tidak_ada(): void
    {
        $temporary = storage_path('framework/testing/zz-uji-public-'.uniqid());
        mkdir($temporary, 0777, true);

        try {
            $this->withoutVite();
            $this->app->usePublicPath($temporary);
            $this->actingAs($this->admin())->get(route('perumahan.create'))
                ->assertSee('public/storage')->assertSee('belum dibuat');

            mkdir($temporary.'/storage');
            $this->actingAs($this->admin())->get(route('perumahan.create'))
                ->assertDontSee('Tautan <code>public/storage</code>', false);
        } finally {
            @rmdir($temporary.'/storage');
            @rmdir($temporary);
            $this->app->usePublicPath(base_path('public'));
        }
    }
}
