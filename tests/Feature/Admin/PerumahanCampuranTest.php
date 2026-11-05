<?php

namespace Tests\Feature\Admin;

use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Perumahan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PerumahanCampuranTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    private function wilayah(): Kelurahan
    {
        $kecamatan = Kecamatan::create(['nama' => 'Kesambi', 'kabupaten_kota' => 'Kota Cirebon']);

        return Kelurahan::create(['kecamatan_id' => $kecamatan->id, 'nama' => 'Drajat']);
    }

    private function payload(Kelurahan $kelurahan, array $tambahan = []): array
    {
        return array_merge([
            'nama' => 'Perumahan Campuran Uji',
            'kelurahan_id' => $kelurahan->id,
            'luas_lahan' => 10000,
            'jumlah_unit' => 80,
            'status' => 'campuran',
        ], $tambahan);
    }

    public function test_campuran_dapat_dibuat_ditampilkan_dan_difilter(): void
    {
        $kelurahan = $this->wilayah();

        $this->actingAs($this->admin())
            ->post(route('perumahan.store'), $this->payload($kelurahan))
            ->assertRedirect();

        $perumahan = Perumahan::firstOrFail();
        $this->assertSame('campuran', $perumahan->status);
        $this->assertSame('Campuran', $perumahan->label_status);

        $this->get(route('perumahan.show', $perumahan))
            ->assertOk()
            ->assertSee('Campuran');
        $this->get(route('publik.perumahan.show', $perumahan))
            ->assertOk()
            ->assertSee('Campuran');
        $this->get(route('publik.perumahan.index', ['status' => 'campuran']))
            ->assertOk()
            ->assertSee($perumahan->nama);
    }

    public function test_campuran_dapat_digunakan_saat_memperbarui_perumahan(): void
    {
        $kelurahan = $this->wilayah();
        $perumahan = Perumahan::create([
            'nama' => 'Perumahan Lama',
            'kelurahan_id' => $kelurahan->id,
            'luas_lahan' => 5000,
            'jumlah_unit' => 40,
            'status' => 'komersil',
        ]);

        $this->actingAs($this->admin())
            ->put(route('perumahan.update', $perumahan), $this->payload($kelurahan, [
                'nama' => 'Perumahan Diperbarui Campuran',
            ]))
            ->assertRedirect(route('perumahan.show', $perumahan));

        $this->assertSame('campuran', $perumahan->fresh()->status);
    }

    public function test_status_perumahan_tidak_valid_tetap_ditolak(): void
    {
        $kelurahan = $this->wilayah();

        $this->actingAs($this->admin())
            ->post(route('perumahan.store'), $this->payload($kelurahan, ['status' => 'tidak_valid']))
            ->assertSessionHasErrors('status');

        $this->assertSame(0, Perumahan::count());
    }
}
