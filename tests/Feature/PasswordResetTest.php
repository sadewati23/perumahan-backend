<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_tamu_bisa_membuka_form_lupa_password(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Lupa Password');
    }

    public function test_tautan_lupa_password_tampil_di_halaman_login(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Lupa password?');
    }

    public function test_email_tidak_terdaftar_ditolak(): void
    {
        $this->from(route('password.request'))
            ->post(route('password.email'), ['email' => 'tidakada@perumahan.test'])
            ->assertRedirect(route('password.request'))
            ->assertSessionHasErrors('email');
    }

    public function test_email_valid_mengirim_tautan_reset(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email])
            ->assertRedirect()
            ->assertSessionHas('status');

        // Notifikasi kustom berbahasa Indonesia yang dikirim ke pelapor.
        Notification::assertSentTo($user, ResetPasswordNotification::class);
    }

    public function test_tamu_bisa_membuka_form_reset_dari_tautan_email(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->get(route('password.reset', ['token' => $token, 'email' => $user->email]))
            ->assertOk()
            ->assertSee('Reset Password');
    }

    public function test_admin_bisa_reset_password_melalui_tautan_email(): void
    {
        Notification::fake();
        $user = User::factory()->create(['password' => bcrypt('password-lama')]);

        // Kirim tautan reset — sekaligus mencatat token di DB.
        $this->post(route('password.email'), ['email' => $user->email])->assertRedirect();

        // Ambil token nyata dari notifikasi yang terkirim (bukan dobel create).
        $token = '';
        Notification::assertSentTo($user, ResetPasswordNotification::class, function (ResetPasswordNotification $notif) use (&$token) {
            $token = $notif->token;

            return true;
        });

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'password-baru-123',
            'password_confirmation' => 'password-baru-123',
        ])->assertRedirect(route('login'))
            ->assertSessionHas('success');

        // Password lama tidak bisa lagi, password baru bisa dipakai login.
        $this->assertCredentials(['email' => $user->email, 'password' => 'password-baru-123']);
        $this->assertInvalidCredentials(['email' => $user->email, 'password' => 'password-lama']);
    }

    public function test_token_invalid_tidak_bisa_dipakai(): void
    {
        $user = User::factory()->create();

        // Token yang tidak pernah dibuat = tidak valid.
        $this->post(route('password.update'), [
            'token' => 'token-ngawur',
            'email' => $user->email,
            'password' => 'password-baru-123',
            'password_confirmation' => 'password-baru-123',
        ])->assertRedirect()
            ->assertSessionHasErrors('email');

        $this->assertCredentials(['email' => $user->email, 'password' => 'password']);
    }

    public function test_konfirmasi_password_tidak_cocok_ditolak(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'password-baru-123',
            'password_confirmation' => 'beda-eh',
        ])->assertRedirect()
            ->assertSessionHasErrors('password');

        $this->assertCredentials(['email' => $user->email, 'password' => 'password']);
    }

    public function test_reset_berhasil_menghapus_token_dan_admin_bisa_login_langsung(): void
    {
        Notification::fake();
        $user = User::factory()->create();

        $this->post(route('password.email'), ['email' => $user->email])->assertRedirect();

        $token = '';
        Notification::assertSentTo($user, ResetPasswordNotification::class, function (ResetPasswordNotification $notif) use (&$token) {
            $token = $notif->token;

            return true;
        });

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => $user->email,
            'password' => 'password-baru-123',
            'password_confirmation' => 'password-baru-123',
        ])->assertRedirect(route('login'));

        // Token sekali pakai: tabel token harus kosong setelah reset sukses.
        $this->assertDatabaseCount('password_reset_tokens', 0);

        // Sesi baru: login langsung dengan password baru.
        $this->post(route('login.attempt'), [
            'email' => $user->email,
            'password' => 'password-baru-123',
        ])->assertRedirect(route('dashboard'));
    }
}
