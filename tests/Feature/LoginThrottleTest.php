<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LoginThrottleTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_diblokir_setelah_lima_percobaan_gagal_beruntun(): void
    {
        $user = User::factory()->create();

        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.attempt'), [
                'email' => $user->email,
                'password' => 'password-salah',
            ])->assertSessionHasErrors('email');
        }

        // Percobaan ke-6 diblokir throttle (walau kredensialnya benar),
        // dengan pesan throttle berbahasa Indonesia — bukan pesan kredensial salah.
        $this->post(route('login.attempt'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertSessionHasErrors('email');

        $this->assertStringContainsString(
            'Terlalu banyak percobaan login',
            (string) session('errors')->first('email'),
        );

        $this->assertGuest();
    }

    public function test_login_berhasil_menghapus_penghitung_percobaan(): void
    {
        $user = User::factory()->create(['is_active' => true]);

        $this->post(route('login.attempt'), [
            'email' => $user->email,
            'password' => 'password-salah',
        ])->assertSessionHasErrors('email');

        $this->post(route('login.attempt'), [
            'email' => $user->email,
            'password' => 'password',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);

        // Keluar dulu — route login hanya untuk tamu (middleware guest).
        $this->post(route('logout'));

        // Setelah login sukses hitungan bersih: satu percobaan gagal
        // berikutnya tetap dapat pesan kredensial salah, bukan throttle.
        $this->post(route('login.attempt'), [
            'email' => $user->email,
            'password' => 'password-salah',
        ])->assertSessionHasErrors('email');

        $this->assertStringContainsString(
            'Email atau password',
            (string) session('errors')->first('email'),
        );
    }

    public function test_header_x_forwarded_for_epals_tidak_melewati_throttle(): void
    {
        $user = User::factory()->create();

        // Luluskan 5 percobaan gagal dengan header X-Forwarded-For palsu berbeda-beda:
        // tanpa proxy terpercaya, header ini HARUS diabaikan ($request->ip() tetap sama).
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('login.attempt'), [
                'email' => $user->email,
                'password' => 'password-salah',
            ], [
                'X-Forwarded-For' => '11.22.33.'.$i,
            ])->assertSessionHasErrors('email');
        }

        // Percobaan ke-6 dengan header palsu LAIN tetap terkena throttle
        // — kalau throttle bisa dilewati header, pesan akan 'kredensial salah'.
        $this->post(route('login.attempt'), [
            'email' => $user->email,
            'password' => 'password',
        ], [
            'X-Forwarded-For' => '99.88.77.66',
        ])->assertSessionHasErrors('email');

        $this->assertStringContainsString(
            'Terlalu banyak percobaan login',
            (string) session('errors')->first('email'),
        );

        $this->assertGuest();
    }

    public function test_throttle_per_email_menahan_brute_force_dari_ip_yang_berganti(): void
    {
        $user = User::factory()->create();

        // Luluskan 5 percobaan gagal semuanya dengan IP yang beda-beda
        // (meniru penyerang memutar IP) — key per email tanpa IP menangkapnya.
        for ($i = 0; $i < 5; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.'.$i])
                ->post(route('login.attempt'), [
                    'email' => $user->email,
                    'password' => 'password-salah',
                ])->assertSessionHasErrors('email');
        }

        // Percobaan ke-6 dari IP baru juga diblokir karena sudah 5 gagal untuk email ini.
        $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.99'])
            ->post(route('login.attempt'), [
                'email' => $user->email,
                'password' => 'password',
            ])->assertSessionHasErrors('email');

        $this->assertStringContainsString(
            'Terlalu banyak percobaan login',
            (string) session('errors')->first('email'),
        );
    }

    public function test_reset_password_dibatasi_lima_permenit(): void
    {
        $user = User::factory()->create();

        // Request tautan reset 5x boleh; ke-6 diblokir throttle:5,1 middleware.
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('password.email'), ['email' => $user->email])
                ->assertRedirect();
        }

        $response = $this->post(route('password.email'), ['email' => $user->email]);

        $this->assertSame(429, $response->status());
    }
}
