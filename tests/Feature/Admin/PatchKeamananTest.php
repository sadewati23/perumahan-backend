<?php

namespace Tests\Feature\Admin;

use App\Models\Laporan;
use App\Models\LaporanFoto;
use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Support\SesiPengguna;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PatchKeamananTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
    }

    private const STATUS_LUPA = 'Jika email terdaftar, tautan reset password telah dikirim. Periksa kotak masuk atau folder spam.';

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin', 'is_active' => true]);
    }

    private function userPayload(string $password = 'Rahasia-Uji-12345'): array
    {
        return [
            'name' => 'Pengguna Uji',
            'username' => 'penggunauji'.uniqid(),
            'email' => 'pengguna-'.uniqid().'@perumahan.test',
            'role' => 'petugas',
            'jabatan' => 'Petugas Uji',
            'password' => $password,
            'password_confirmation' => $password,
        ];
    }

    private function insertSession(User $user, string $id): void
    {
        DB::table('sessions')->updateOrInsert(['id' => $id], [
            'user_id' => $user->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'payload' => '',
            'last_activity' => now()->timestamp,
        ]);
    }

    private function laporanDenganFoto(): Laporan
    {
        $laporan = Laporan::create([
            'kode' => Laporan::buatKode('pengaduan'),
            'jenis' => 'pengaduan',
            'nama_pelapor' => 'Warga Uji',
            'judul' => 'Laporan Foto Uji',
            'deskripsi' => 'Deskripsi laporan uji.',
            'status' => 'selesai',
            'deskripsi_aksi_selesai' => 'Tindak lanjut aman.',
        ]);

        LaporanFoto::create([
            'laporan_id' => $laporan->id,
            'path' => 'laporan/bukti.jpg',
            'tipe' => LaporanFoto::TIPE_LAPORAN,
        ]);
        LaporanFoto::create([
            'laporan_id' => $laporan->id,
            'path' => 'laporan/perbaikan.jpg',
            'tipe' => LaporanFoto::TIPE_PERBAIKAN,
            'keterangan' => 'Keterangan foto rahasia uji.',
        ]);

        return $laporan;
    }

    public function test_kebijakan_password_menolak_password_lemah_dan_menerima_password_kuat(): void
    {
        config(['app.locale' => 'id', 'app.fallback_locale' => 'en']);
        $admin = $this->admin();

        foreach (['123456', 'password', 'Rahasia1', 'rahasiauji12345'] as $password) {
            $this->withSession(['auth.password_confirmed_at' => time()])->actingAs($admin)->post(route('users.store'), $this->userPayload($password))
                ->assertSessionHasErrors('password');
        }

        $payload = $this->userPayload();
        $this->withSession(['auth.password_confirmed_at' => time()])->actingAs($admin)->post(route('users.store'), $payload)
            ->assertSessionHasNoErrors();
        $user = User::where('email', $payload['email'])->firstOrFail();

        foreach (['123456', 'password', 'Rahasia1', 'rahasiauji12345'] as $password) {
            $update = $this->userPayload($password);
            $update['email'] = $user->email;
            $this->withSession(['auth.password_confirmed_at' => time()])->actingAs($admin)->put(route('users.update', $user), $update)
                ->assertSessionHasErrors('password');
        }

        $update = $this->userPayload();
        $update['email'] = $user->email;
        $update['is_active'] = true;
        $this->withSession(['auth.password_confirmed_at' => time()])->actingAs($admin)->put(route('users.update', $user), $update)
            ->assertSessionHasNoErrors();

        auth()->logout();
        foreach (['123456', 'password', 'Rahasia1', 'rahasiauji12345'] as $password) {
            $token = Password::createToken($user);
            $this->post(route('password.update'), [
                'token' => $token,
                'email' => $user->email,
                'password' => $password,
                'password_confirmation' => $password,
            ])->assertSessionHasErrors('password');
        }

        $token = Password::createToken($user);
        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'Rahasia-Uji-12345',
            'password_confirmation' => 'Rahasia-Uji-12345',
        ])->assertRedirect(route('login'));
    }

    public function test_password_kosong_saat_ubah_tidak_mengubah_password_dan_pesan_indonesia(): void
    {
        config(['app.locale' => 'id', 'app.fallback_locale' => 'en']);
        $admin = $this->admin();
        $user = User::factory()->create(['password' => 'Password-Lama-123']);

        $payload = $this->userPayload();
        $payload['email'] = $user->email;
        $payload['password'] = '';
        $payload['password_confirmation'] = '';
        $this->withSession(['auth.password_confirmed_at' => time()])->actingAs($admin)->put(route('users.update', $user), $payload)
            ->assertSessionHasNoErrors();
        $this->assertTrue(Hash::check('Password-Lama-123', $user->refresh()->password));

        $this->withSession(['auth.password_confirmed_at' => time()])->actingAs($admin)->post(route('users.store'), $this->userPayload('rahasiauji12345'))
            ->assertSessionHasErrors('password');
        $this->assertStringContainsString('huruf besar dan huruf kecil', (string) session('errors')->first('password'));
    }

    public function test_lupa_password_menyamarkan_keberadaan_email_dan_menolak_format_invalid(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $unknown = $this->post(route('password.email'), ['email' => 'tidakada@perumahan.test']);
        $known = $this->post(route('password.email'), ['email' => $user->email]);

        $this->assertSame(self::STATUS_LUPA, $unknown->getSession()->get('status'));
        $this->assertSame(self::STATUS_LUPA, $known->getSession()->get('status'));
        $known->assertSessionHasNoErrors();
        Notification::assertSentTo($user, ResetPasswordNotification::class);

        $this->post(route('password.email'), ['email' => 'bukan-email'])
            ->assertSessionHasErrors('email');
    }

    public function test_lupa_password_dibatasi_setelah_lima_permintaan(): void
    {
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('password.email'), ['email' => 'tidakada@perumahan.test'])
                ->assertRedirect();
        }

        $this->post(route('password.email'), ['email' => 'tidakada@perumahan.test'])
            ->assertStatus(429);
    }

    public function test_pencabutan_sesi_password_dan_nonaktif(): void
    {
        $admin = $this->admin();
        $petugas = User::factory()->create(['is_active' => true]);
        $remember = $petugas->remember_token;
        $this->insertSession($petugas, 'sesi-password');

        $payload = $this->userPayload();
        $payload['email'] = $petugas->email;
        $payload['is_active'] = true;
        $this->withSession(['auth.password_confirmed_at' => time()])->actingAs($admin)->put(route('users.update', $petugas), $payload)->assertSessionHas('success');
        $this->assertDatabaseMissing('sessions', ['id' => 'sesi-password']);
        $this->assertNotSame($remember, $petugas->refresh()->remember_token);

        $this->insertSession($petugas, 'sesi-nama');
        $payload['name'] = 'Nama Baru';
        $payload['password'] = '';
        $payload['password_confirmation'] = '';
        $payload['is_active'] = true;
        $this->withSession(['auth.password_confirmed_at' => time()])->actingAs($admin)->put(route('users.update', $petugas), $payload)->assertSessionHas('success');
        $this->assertDatabaseHas('sessions', ['id' => 'sesi-nama']);

        $this->insertSession($petugas, 'sesi-nonaktif');
        $payload['is_active'] = false;
        $this->withSession(['auth.password_confirmed_at' => time()])->actingAs($admin)->put(route('users.update', $petugas), $payload)->assertSessionHas('success');
        $this->assertDatabaseMissing('sessions', ['id' => 'sesi-nonaktif']);
    }

    public function test_admin_mengubah_password_sendiri_tidak_mencabut_sesi_sendiri(): void
    {
        $admin = $this->admin();
        $this->insertSession($admin, 'sesi-admin-sendiri');
        SesiPengguna::cabut($admin, 'sesi-admin-sendiri');
        $this->assertDatabaseHas('sessions', ['id' => 'sesi-admin-sendiri', 'user_id' => $admin->id]);

        $this->actingAs($admin)->get(route('dashboard'))->assertOk();
        $payload = $this->userPayload();
        $payload['email'] = $admin->email;
        $payload['role'] = 'admin';
        $payload['is_active'] = true;

        $this->withSession(['auth.password_confirmed_at' => time()])->actingAs($admin)->put(route('users.update', $admin), $payload)->assertSessionHas('success');
        $this->assertAuthenticatedAs($admin);
    }

    public function test_reset_password_mencabut_semua_sesi(): void
    {
        Notification::fake();
        $user = User::factory()->create();
        $this->insertSession($user, 'sesi-reset-1');
        $this->insertSession($user, 'sesi-reset-2');
        $this->post(route('password.email'), ['email' => $user->email]);
        $token = '';
        Notification::assertSentTo($user, ResetPasswordNotification::class, function ($notification) use (&$token): bool {
            $token = $notification->token;
            return true;
        });

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'Rahasia-Uji-12345',
            'password_confirmation' => 'Rahasia-Uji-12345',
        ])->assertRedirect(route('login'));
        $this->assertDatabaseMissing('sessions', ['user_id' => $user->id]);
    }

    public function test_form_validasi_bawaan_memakai_fallback_en_dan_tidak_memuat_kunci_mentah(): void
    {
        config(['app.locale' => 'id', 'app.fallback_locale' => 'en']);
        $this->assertSame('en', config('app.fallback_locale'));
        $admin = $this->admin();

        $this->withSession(['auth.password_confirmed_at' => time()])->actingAs($admin)->post(route('users.store'), [])
            ->assertSessionHasErrors('name');
        $this->assertStringNotContainsString('validation.', (string) session('errors')->first('name'));

        auth()->logout();
        $this->post(route('password.email'), [])
            ->assertSessionHasErrors('email');
        $this->assertStringNotContainsString('validation.', (string) session('errors')->first('email'));
    }

    public function test_header_keamanan_berlaku_di_halaman_normal_404_dan_admin(): void
    {
        foreach (['/', route('login'), route('cek-laporan.index'), '/path-tidak-ada'] as $path) {
            $response = $this->get($path);
            foreach (['X-Content-Type-Options', 'X-Frame-Options', 'Referrer-Policy', 'Permissions-Policy'] as $header) {
                $response->assertHeader($header);
            }
            $response->assertHeaderMissing('Content-Security-Policy');
        }

        $this->actingAs($this->admin())->get(route('dashboard'))
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeader('Permissions-Policy', 'camera=(), microphone=(), payment=()');
    }

    public function test_hasil_cek_laporan_mempertahankan_no_referrer(): void
    {
        $laporan = $this->laporanDenganFoto();
        $this->post(route('cek-laporan.cari'), ['kode' => $laporan->kode])
            ->assertOk()
            ->assertHeader('Referrer-Policy', 'no-referrer');
    }

    public function test_hsts_hanya_https_production(): void
    {
        $this->get('/')->assertHeaderMissing('Strict-Transport-Security');

        $this->app['env'] = 'production';
        config(['app.url' => 'https://perumahan.test']);
        $this->get('https://perumahan.test/')->assertHeader('Strict-Transport-Security', 'max-age=31536000');
        $this->get('http://perumahan.test/')->assertHeaderMissing('Strict-Transport-Security');
    }

    public function test_status_publik_tidak_merender_foto_atau_keterangan_foto(): void
    {
        $laporan = $this->laporanDenganFoto();
        $this->post(route('cek-laporan.cari'), ['kode' => $laporan->kode])
            ->assertOk()
            ->assertDontSee('<img', false)
            ->assertDontSee('/storage/', false)
            ->assertDontSee('Keterangan foto rahasia uji.', false);
    }
}
