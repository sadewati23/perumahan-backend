<?php

namespace Tests\Feature;

use App\Models\Laporan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class KeamananLaporanTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Payload minimal valid untuk jenis pengaduan (tanpa wilayah & foto).
     */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'jenis' => 'pengaduan',
            'nama_pelapor' => 'Budi Santoso',
            'judul' => 'Judul laporan uji keamanan',
            'deskripsi' => 'Uraian laporan untuk pengujian keamanan form publik.',
        ], $overrides);
    }

    public function test_honeypot_terisi_tak_menyimpan_laporan(): void
    {
        // Bot mengisi semua input termasuk field tersembunyi — harus diblokir.
        $this->post(route('lapor.store'), $this->payload(['jawaban_cadangan' => 'isi-bot']))
            ->assertRedirect(route('lapor.create'))
            ->assertSessionHas('success', 'Laporan berhasil dikirim.');

        $this->assertSame(0, Laporan::count());
    }

    public function test_turnstile_gagal_menolak_kiriman(): void
    {
        config(['services.turnstile.secret' => 'x']);
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => false])]);

        $this->post(route('lapor.store'), $this->payload(['cf-turnstile-response' => 'token-usulan-bot']))
            ->assertRedirect()
            ->assertSessionHasErrors('cf-turnstile-response');

        $this->assertSame(0, Laporan::count());
    }

    public function test_turnstile_sukses_laporan_tersimpan(): void
    {
        config(['services.turnstile.secret' => 'x']);
        Http::fake(['challenges.cloudflare.com/*' => Http::response(['success' => true])]);

        $this->post(route('lapor.store'), $this->payload(['cf-turnstile-response' => 'token-valid']))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Laporan::count());
        $this->assertSame('pengaduan', Laporan::query()->first()->jenis);
    }

    public function test_titik_gps_yang_valid_tersimpan(): void
    {
        $this->post(route('lapor.store'), $this->payload([
            'latitude' => -6.7320000,
            'longitude' => 108.5523000,
        ]))->assertRedirect()->assertSessionHasNoErrors();

        $laporan = Laporan::query()->firstOrFail();
        $this->assertSame(-6.7320000, (float) $laporan->latitude);
        $this->assertSame(108.5523000, (float) $laporan->longitude);
        $this->assertStringContainsString('google.com/maps?q=', (string) $laporan->tautan_peta);
    }

    public function test_koordinat_tak_valid_ditolak(): void
    {
        // Hanya satu dari dua koordinat terisi -> pasangan GPS dianggap tak lengkap.
        $this->post(route('lapor.store'), $this->payload(['latitude' => '-6.73']))
            ->assertRedirect()
            ->assertSessionHasErrors('latitude');

        // Nilai di luar rentang koordinat dunia -> ditolak rule between.
        $this->post(route('lapor.store'), $this->payload([
            'latitude' => '200.5',
            'longitude' => '108.55',
        ]))->assertRedirect()
            ->assertSessionHasErrors('latitude');

        $this->assertSame(0, Laporan::count());
    }

    public function test_exif_foto_dibuang_saat_dengan_reencode(): void
    {
        Storage::fake('public');

        // JPEG valid dari GD (sebagai gambar HP sintetik).
        $img = imagecreatetruecolor(20, 20);
        imagealphablending($img, false);
        imagefilledrectangle($img, 0, 0, 19, 19, imagecolorallocate($img, 180, 190, 200));
        ob_start();
        imagejpeg($img, null, 90);
        $jpeg = (string) ob_get_clean();
        imagedestroy($img);

        // Sisipkan segmen EXIF APP1 berisi metadata GPS palsu tepat setelah SOI 2-byte.
        $payload = "Exif\0\0GPSLatitude-RAHASIA";
        $jpegDenganExif = substr($jpeg, 0, 2)
            . "\xFF\xE1".pack('n', 2 + strlen($payload)).$payload
            . substr($jpeg, 2);

        // Uji sanity sebelum upload: payload tempel benar-benar ada di file.
        $this->assertStringContainsString('GPSLatitude-RAHASIA', $jpegDenganExif);

        $file = UploadedFile::fake()->createWithContent('bukti.jpg', $jpegDenganExif);

        $this->post(route('lapor.store'), $this->payload(['foto' => [$file]]))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $laporan = Laporan::query()->firstOrFail();
        $foto = $laporan->fotos()->firstOrFail();

        // Ekstensi mengikuti tipe terdeteksi getimagesize (JPEG -> .jpg).
        $this->assertStringEndsWith('.jpg', $foto->path);
        $this->assertStringStartsWith('laporan/', $foto->path);

        // Isi file tersimpan adalah hasil encode ulang PHP: EXIF/GPS asli harus hilang.
        $isi = Storage::disk('public')->get($foto->path);
        $this->assertStringNotContainsString('GPSLatitude-RAHASIA', $isi);
        $this->assertStringNotContainsString('Exif', $isi);
    }
}
