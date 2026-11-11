<?php

namespace Tests\Feature\Admin;

use App\Models\Laporan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class CacheAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_dan_auth_mengirim_no_store_sedangkan_publik_tidak(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);

        foreach ([route('dashboard'), route('perumahan.index')] as $url) {
            $this->actingAs($admin)->get($url)->assertHeader('Cache-Control', 'no-store, private');
        }
        Auth::logout();
        $this->get(route('login'))->assertHeader('Cache-Control', 'no-store, private');
        $this->actingAs($admin)->get(route('password.confirm'))->assertHeader('Cache-Control', 'no-store, private');

        foreach ([route('publik.beranda'), route('publik.perumahan.index'), route('publik.peta')] as $url) {
            $this->assertStringNotContainsString('no-store', strtolower((string) $this->get($url)->headers->get('Cache-Control')));
        }
    }

    public function test_hasil_cek_laporan_tetap_no_store_private(): void
    {
        $laporan = Laporan::create([
            'kode' => Laporan::buatKode(), 'jenis' => Laporan::JENIS_PENGADUAN,
            'judul' => 'Laporan Cache', 'deskripsi' => 'Uji', 'nama_pelapor' => 'Warga', 'status' => 'baru',
        ]);

        $this->post(route('cek-laporan.cari'), ['kode' => $laporan->kode])
            ->assertOk()->assertHeader('Cache-Control', 'no-store, private');
    }
}
