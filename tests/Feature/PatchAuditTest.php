<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\ResetPasswordNotification;
use App\Support\HostTepercaya;
use App\Support\OriginAplikasi;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\PerumahanSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Notification;
use ReflectionMethod;
use Tests\TestCase;

class PatchAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_origin_reset_produksi_tidak_mengikuti_host_request(): void
    {
        $this->app['env'] = 'production';
        config(['app.env' => 'production', 'app.url' => 'https://dprkp.contoh.go.id']);
        $url = app('url');
        $url->setRequest(Request::create('http://evil.test/lupa-password'));

        $notification = new ResetPasswordNotification('token-uji');
        $method = new ReflectionMethod($notification, 'resetUrl');
        $method->setAccessible(true);
        $resetUrl = $method->invoke($notification, User::factory()->make(['email' => 'uji@example.test']));

        $this->assertStringStartsWith('https://dprkp.contoh.go.id/reset-password/', $resetUrl);
        $this->assertStringNotContainsString('evil.test', $resetUrl);
    }

    public function test_origin_lokal_tetap_mengikuti_host_request(): void
    {
        $this->app['env'] = 'local';
        config(['app.env' => 'local', 'app.url' => 'http://localhost']);
        app('url')->setRequest(Request::create('http://evil.test/lupa-password'));

        $notification = new ResetPasswordNotification('token-uji');
        $method = new ReflectionMethod($notification, 'resetUrl');
        $method->setAccessible(true);
        $resetUrl = $method->invoke($notification, User::factory()->make(['email' => 'uji@example.test']));

        $this->assertStringStartsWith('http://evil.test/reset-password/', $resetUrl);
    }

    public function test_host_tepercaya_hanya_aktif_di_produksi(): void
    {
        $this->app['env'] = 'local';
        config(['app.env' => 'local', 'app.url' => 'https://dprkp.contoh.go.id']);
        $this->assertSame([], HostTepercaya::daftar());

        $this->app['env'] = 'production';
        config([
            'app.env' => 'production',
            'app.url' => 'https://DPRKP.contoh.go.id:443/path',
            'app.trusted_hosts' => [' www.contoh.go.id ', 'www.contoh.go.id'],
        ]);

        $this->assertSame([
            '^dprkp\\.contoh\\.go\\.id$',
            '^www\\.contoh\\.go\\.id$',
        ], HostTepercaya::daftar());
    }

    public function test_seeder_data_contoh_tidak_menulis_di_produksi(): void
    {
        $this->app['env'] = 'production';
        config(['app.env' => 'production']);

        (new UserSeeder)->run();
        (new PerumahanSeeder)->run();

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('perumahans', 0);
    }

    public function test_database_seeder_produksi_hanya_menjalankan_wilayah(): void
    {
        $this->app['env'] = 'production';
        config(['app.env' => 'production']);
        (new DatabaseSeeder)->run();

        $this->assertDatabaseCount('users', 0);
        $this->assertDatabaseCount('perumahans', 0);
    }

    public function test_admin_buat_menggunakan_password_interaktif_dan_tidak_mencetaknya(): void
    {
        $this->artisan('admin:buat', ['username' => 'adminproduksi', 'email' => 'admin@contoh.go.id'])
            ->expectsQuestion('Password administrator', 'Rahasia-Uji-12345')
            ->expectsQuestion('Konfirmasi password administrator', 'Rahasia-Uji-12345')
            ->assertExitCode(0)
            ->expectsOutputToContain('admin@contoh.go.id')
            ->doesntExpectOutput('Rahasia-Uji-12345');

        $this->assertDatabaseHas('users', ['email' => 'admin@contoh.go.id', 'role' => 'admin', 'is_active' => true]);
        $this->assertTrue(password_verify('Rahasia-Uji-12345', User::where('email', 'admin@contoh.go.id')->value('password')));
    }

    public function test_login_akun_bawaan_ditolak_di_produksi(): void
    {
        $this->app['env'] = 'production';
        config(['app.env' => 'production']);
        User::factory()->create(['email' => 'admin@perumahan.test', 'password' => 'password']);

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->post(route('login.attempt'), ['username' => 'admin', 'password' => 'password'])
            ->assertSessionHasErrors('username');
        $this->assertGuest();
    }

    public function test_max_input_vars_menjadi_peringatan_di_bawah_tiga_ribu(): void
    {
        Artisan::call('produksi:periksa');
        $this->assertStringContainsString('max_input_vars', Artisan::output());
    }
}
