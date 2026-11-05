<?php

namespace Tests\Feature\Admin;

use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Perumahan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class UploadGagalTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    private function wilayah(): Kelurahan
    {
        $kecamatan = Kecamatan::create(['nama' => 'Coblong', 'kabupaten_kota' => 'Kota Bandung']);

        return Kelurahan::create(['kecamatan_id' => $kecamatan->id, 'nama' => 'Dago']);
    }

    private function payload(Kelurahan $kelurahan, array $extra = []): array
    {
        return array_merge([
            'nama' => 'ZZ-UJI-GAGAL',
            'kelurahan_id' => $kelurahan->id,
            'luas_lahan' => 5000,
            'jumlah_unit' => 20,
            'status' => 'komersil',
        ], $extra);
    }

    public function test_upload_dengan_error_php_menampilkan_pesan_indonesia(): void
    {
        $path = storage_path('framework/testing/zz-uji-upload-error.jpg');
        file_put_contents($path, 'zz-uji');

        try {
            $foto = new UploadedFile($path, 'x.jpg', 'image/jpeg', UPLOAD_ERR_INI_SIZE, true);

            $this->actingAs($this->admin())
                ->post(route('perumahan.store'), $this->payload($this->wilayah(), ['foto' => $foto]))
                ->assertSessionHasErrors(['foto' => 'Foto gagal diunggah. Ukuran file kemungkinan melebihi batas server. Coba kecilkan fotonya.']);

            $this->assertSame(0, Perumahan::count());
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function test_post_terlalu_besar_kembali_ke_form_dengan_error_foto(): void
    {
        $this->actingAs($this->admin())
            ->call('POST', route('perumahan.store'), [], [], [], [
                'CONTENT_LENGTH' => 999999999999,
                'HTTP_REFERER' => route('perumahan.create'),
            ])
            ->assertRedirect(route('perumahan.create'))
            ->assertSessionHasErrors('foto')
            ->assertSessionHas('errors', fn ($errors) => str_contains(
                $errors->get('foto')[0],
                'Ukuran total unggahan melebihi batas server'
            ));

        $this->assertSame(0, Perumahan::count());
    }
}
