<?php

namespace Tests\Feature\Publik;

use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Perumahan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TampilanFotoPerumahanTest extends TestCase
{
    use RefreshDatabase;

    private function data(): array
    {
        Storage::fake('public');
        $kecamatan = Kecamatan::create(['nama' => 'Coblong', 'kabupaten_kota' => 'Kota Bandung']);
        $kelurahan = Kelurahan::create(['kecamatan_id' => $kecamatan->id, 'nama' => 'Dago']);
        Storage::disk('public')->put('perumahan/foto-utuh.jpg', 'foto');

        $berfoto = Perumahan::create([
            'nama' => 'Perumahan Foto Utuh',
            'kelurahan_id' => $kelurahan->id,
            'luas_lahan' => 5000,
            'jumlah_unit' => 20,
            'status' => 'komersil',
            'foto_path' => 'perumahan/foto-utuh.jpg',
        ]);
        $tanpaFoto = Perumahan::create([
            'nama' => 'Perumahan Tanpa Foto',
            'kelurahan_id' => $kelurahan->id,
            'luas_lahan' => 5000,
            'jumlah_unit' => 20,
            'status' => 'subsidi',
        ]);

        return [$berfoto, $tanpaFoto];
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    public function test_foto_utama_utuh_di_daftar_dan_beranda(): void
    {
        [$berfoto] = $this->data();

        foreach ([route('publik.perumahan.index'), route('publik.beranda')] as $url) {
            $content = $this->get($url)->assertOk()->getContent();

            $this->assertStringContainsString('alt="Foto '.$berfoto->nama.'"', $content);
            $this->assertStringContainsString('object-contain', $content);
            $this->assertDoesNotMatchRegularExpression('/<img[^>]+alt="Foto '.preg_quote($berfoto->nama, '/').'"[^>]*object-cover/s', $content);
        }
    }

    public function test_detail_memuat_foto_utama_tanpa_lazy(): void
    {
        [$berfoto] = $this->data();
        $content = $this->get(route('publik.perumahan.show', $berfoto))->assertOk()->getContent();

        $this->assertStringContainsString('alt="Foto '.$berfoto->nama.'"', $content);
        $this->assertStringContainsString('object-contain', $content);
        $this->assertDoesNotMatchRegularExpression('/<img[^>]+alt="Foto '.preg_quote($berfoto->nama, '/').'"[^>]*loading="lazy"/s', $content);
    }

    public function test_placeholder_dan_latar_foto_dekoratif_tampil_benar(): void
    {
        [$berfoto, $tanpaFoto] = $this->data();
        $index = $this->get(route('publik.perumahan.index'))->assertOk()->getContent();

        $this->assertStringContainsString('alt="Ilustrasi perumahan"', $index);
        $this->assertMatchesRegularExpression('/<img[^>]+alt=""[^>]+aria-hidden="true"[^>]+class="[^"]*blur-xl/s', $index);
        $this->assertStringContainsString($tanpaFoto->nama, $index);
    }

    public function test_form_edit_memakai_komponen_foto_utuh(): void
    {
        [$berfoto] = $this->data();
        $content = $this->actingAs($this->admin())
            ->get(route('perumahan.edit', $berfoto))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('alt="Foto '.$berfoto->nama.'"', $content);
        $this->assertStringContainsString('object-contain', $content);
    }
}
