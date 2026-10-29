<?php

namespace Tests\Feature;

use App\Models\Kecamatan;
use App\Models\Kelurahan;
use App\Models\Laporan;
use App\Models\Perumahan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class KonfirmasiPasswordTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'admin', 'is_active' => true, 'password' => 'password',
        ]);
    }

    private function wilayah(): array
    {
        $kecamatan = Kecamatan::create(['nama' => 'Kecamatan Uji '.uniqid()]);
        $kelurahan = Kelurahan::create(['kecamatan_id' => $kecamatan->id, 'nama' => 'Kelurahan Uji '.uniqid()]);

        return [$kecamatan, $kelurahan];
    }

    private function perumahan(): Perumahan
    {
        [, $kelurahan] = $this->wilayah();

        return Perumahan::create([
            'nama' => 'Perumahan Uji', 'kelurahan_id' => $kelurahan->id,
            'luas_lahan' => 1000, 'jumlah_unit' => 10, 'status' => 'komersil',
        ]);
    }

    private function laporan(): Laporan
    {
        return Laporan::create([
            'kode' => Laporan::buatKode(), 'jenis' => Laporan::JENIS_PENGADUAN,
            'judul' => 'Laporan Uji', 'deskripsi' => 'Deskripsi Uji',
            'nama_pelapor' => 'Warga Uji', 'status' => 'baru',
        ]);
    }

    public function test_aksi_berbahaya_memerlukan_konfirmasi_dan_sesi_memungkinkannya(): void
    {
        $admin = $this->admin();

        $perumahan = $this->perumahan();
        $this->actingAs($admin)->delete(route('perumahan.destroy', $perumahan))
            ->assertRedirect(route('password.confirm'));
        $this->assertDatabaseHas('perumahans', ['id' => $perumahan->id]);
        $this->withSession(['auth.password_confirmed_at' => time()])->actingAs($admin)
            ->delete(route('perumahan.destroy', $perumahan))->assertRedirect();

        $this->withSession(['auth.password_confirmed_at' => time() - 901]);
        $laporan = $this->laporan();
        $this->actingAs($admin)->delete(route('laporan.destroy', $laporan))->assertRedirect(route('password.confirm'));
        $this->withSession(['auth.password_confirmed_at' => time()])->actingAs($admin)
            ->delete(route('laporan.destroy', $laporan))->assertRedirect();

        $this->withSession(['auth.password_confirmed_at' => time() - 901]);
        [$kecamatan] = $this->wilayah();
        $this->actingAs($admin)->delete(route('kecamatan.destroy', $kecamatan))->assertRedirect(route('password.confirm'));
        $this->withSession(['auth.password_confirmed_at' => time()])->actingAs($admin)
            ->delete(route('kecamatan.destroy', $kecamatan))->assertRedirect();

        $this->withSession(['auth.password_confirmed_at' => time() - 901]);
        [, $kelurahan] = $this->wilayah();
        $this->actingAs($admin)->delete(route('kelurahan.destroy', $kelurahan))->assertRedirect(route('password.confirm'));
        $this->withSession(['auth.password_confirmed_at' => time()])->actingAs($admin)
            ->delete(route('kelurahan.destroy', $kelurahan))->assertRedirect();

        $payload = [
            'name' => 'Pengguna Baru', 'email' => 'baru-'.uniqid().'@uji.test', 'role' => 'petugas',
            'jabatan' => 'Petugas', 'password' => 'Rahasia-Uji-12345', 'password_confirmation' => 'Rahasia-Uji-12345',
        ];
        $this->withSession(['auth.password_confirmed_at' => time() - 901])->actingAs($admin)->post(route('users.store'), $payload)->assertRedirect(route('password.confirm'));
        $this->withSession(['auth.password_confirmed_at' => time()])->actingAs($admin)
            ->post(route('users.store'), $payload)->assertRedirect();
        $user = User::where('email', $payload['email'])->firstOrFail();

        $update = $payload;
        $update['name'] = 'Nama Diubah';
        $this->withSession(['auth.password_confirmed_at' => time() - 901])->actingAs($admin)->put(route('users.update', $user), $update)->assertRedirect(route('password.confirm'));
        $this->withSession(['auth.password_confirmed_at' => time()])->actingAs($admin)
            ->put(route('users.update', $user), $update)->assertRedirect();

        $this->withSession(['auth.password_confirmed_at' => time() - 901])->actingAs($admin)->delete(route('users.destroy', $user))->assertRedirect(route('password.confirm'));
        $this->withSession(['auth.password_confirmed_at' => time()])->actingAs($admin)
            ->delete(route('users.destroy', $user))->assertRedirect();
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_konfirmasi_kedaluwarsa_dan_users_index_tidak_memerlukan_konfirmasi(): void
    {
        $admin = $this->admin();

        $this->withSession(['auth.password_confirmed_at' => time() - 901])->actingAs($admin)
            ->get(route('users.index'))->assertOk();

        $kecamatan = Kecamatan::create(['nama' => 'Kecamatan Kedaluwarsa']);
        $this->withSession(['auth.password_confirmed_at' => time() - 901])->actingAs($admin)
            ->delete(route('kecamatan.destroy', $kecamatan))->assertRedirect(route('password.confirm'));
    }

    public function test_password_salah_tidak_mengisi_sesi_dan_password_benar_mengembalikan_ke_tujuan(): void
    {
        $admin = $this->admin();
        $kecamatan = Kecamatan::create(['nama' => 'Kecamatan Alur']);
        $deleteUrl = route('kecamatan.destroy', $kecamatan);

        $this->actingAs($admin)->delete($deleteUrl)->assertRedirect(route('password.confirm'));
        $this->post(route('password.confirm.store'), ['password' => 'salah'])
            ->assertSessionHasErrors('password')->assertSessionMissing('auth.password_confirmed_at');
        $this->post(route('password.confirm.store'), ['password' => 'password'])
            ->assertRedirect($deleteUrl)->assertSessionHas('auth.password_confirmed_at');
    }

    public function test_alur_ulang_delete_setelah_konfirmasi_tanpa_jalan_pintas_sesi(): void
    {
        $admin = $this->admin();
        $kecamatan = Kecamatan::create(['nama' => 'Kecamatan Alur Utuh']);
        $deleteUrl = route('kecamatan.destroy', $kecamatan);

        $this->actingAs($admin)->delete($deleteUrl)->assertRedirect(route('password.confirm'));
        $this->post(route('password.confirm.store'), ['password' => 'password'])
            ->assertRedirect($deleteUrl)->assertSessionHas('auth.password_confirmed_at');
        $this->actingAs($admin)->delete($deleteUrl)->assertRedirect();
        $this->assertDatabaseMissing('kecamatans', ['id' => $kecamatan->id]);
    }

    public function test_form_konfirmasi_dibatasi_enam_permenit_dan_petugas_tetap_403(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('password.confirm'))->assertOk();
        for ($i = 0; $i < 6; $i++) {
            $this->post(route('password.confirm.store'), ['password' => 'salah'])->assertSessionHasErrors('password');
        }
        $this->post(route('password.confirm.store'), ['password' => 'salah'])->assertStatus(429);

        RateLimiter::clear('6|127.0.0.1');
        $petugas = User::factory()->create(['role' => 'petugas', 'is_active' => true]);
        $kecamatan = Kecamatan::create(['nama' => 'Kecamatan Petugas']);
        $this->actingAs($petugas)->delete(route('kecamatan.destroy', $kecamatan))->assertForbidden();
    }
}
