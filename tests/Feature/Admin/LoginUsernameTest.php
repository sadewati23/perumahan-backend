<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Support\UsernameBackfill;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class LoginUsernameTest extends TestCase
{
    use RefreshDatabase;

    private const ERROR = 'Username atau password yang Anda masukkan salah, atau akun dinonaktifkan.';

    public function test_login_menerima_username_yang_dinormalisasi(): void
    {
        $user = User::factory()->create(['username' => 'admin']);

        $this->post(route('login.attempt'), ['username' => 'ADMIN', 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);

        $this->post(route('logout'));
        $this->post(route('login.attempt'), ['username' => '  admin  ', 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
        $this->assertSame('admin', $user->refresh()->username);
    }

    public function test_email_username_tidak_diterima_dan_semua_kegagalan_memakai_pesan_sama(): void
    {
        $aktif = User::factory()->create(['username' => 'aktif']);
        $nonaktif = User::factory()->create(['username' => 'nonaktif', 'is_active' => false]);

        $pesan = [];
        foreach ([
            ['username' => $aktif->email, 'password' => 'password'],
            ['username' => 'tidakada', 'password' => 'password'],
            ['username' => 'aktif', 'password' => 'salah'],
            ['username' => $nonaktif->username, 'password' => 'password'],
        ] as $payload) {
            $this->post(route('login.attempt'), $payload)->assertSessionHasErrors('username');
            $pesan[] = (string) session('errors')->first('username');
        }

        $this->assertSame([self::ERROR, self::ERROR, self::ERROR, self::ERROR], $pesan);
        $this->assertGuest();
    }

    public function test_username_array_dan_kosong_gagal_validasi_tanpa_500(): void
    {
        $this->post(route('login.attempt'), ['username' => ['x'], 'password' => 'password'])
            ->assertSessionHasErrors('username');
        $this->post(route('login.attempt'), ['username' => '', 'password' => 'password'])
            ->assertSessionHasErrors('username');
    }

    public function test_throttle_per_username_dari_ip_berbeda_dan_ip_sama(): void
    {
        $user = User::factory()->create(['username' => 'throttle']);

        for ($i = 0; $i < 5; $i++) {
            $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.'.$i])
                ->post(route('login.attempt'), ['username' => $user->username, 'password' => 'salah'])
                ->assertSessionHasErrors('username');
        }
        $response = $this->withServerVariables(['REMOTE_ADDR' => '10.0.0.99'])
            ->post(route('login.attempt'), ['username' => $user->username, 'password' => 'password']);
        $response->assertSessionHasErrors('username');
        $this->assertStringContainsString('Terlalu banyak percobaan login', (string) session('errors')->first('username'));
    }

    public function test_login_berhasil_membersihkan_throttle(): void
    {
        $user = User::factory()->create(['username' => 'bersih']);
        $this->post(route('login.attempt'), ['username' => 'bersih', 'password' => 'salah'])
            ->assertSessionHasErrors('username');
        $this->post(route('login.attempt'), ['username' => 'bersih', 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
        $this->post(route('logout'));
        $this->post(route('login.attempt'), ['username' => 'bersih', 'password' => 'salah'])
            ->assertSessionHasErrors('username');
        $this->assertSame(self::ERROR, (string) session('errors')->first('username'));
    }

    public function test_akun_bawaan_ditolak_di_produksi_dengan_log_kritis(): void
    {
        Log::spy();
        $this->app['env'] = 'production';
        config(['app.env' => 'production']);
        $user = User::factory()->create(['username' => 'admin', 'email' => 'admin@perumahan.test']);

        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->post(route('login.attempt'), ['username' => $user->username, 'password' => 'password'])
            ->assertSessionHasErrors('username');
        Log::shouldHaveReceived('critical')->with('login.akun_bawaan_diblokir')->once();
        $this->assertGuest();
    }

    public function test_akun_bawaan_non_produksi_dan_akun_produksi_lain_tetap_bisa_login(): void
    {
        $bawaan = User::factory()->create(['username' => 'lokal', 'email' => 'admin@perumahan.test']);
        $this->post(route('login.attempt'), ['username' => 'lokal', 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
        $this->post(route('logout'));

        $this->app['env'] = 'production';
        config(['app.env' => 'production']);
        $lain = User::factory()->create(['username' => 'lain', 'email' => 'admin@example.go.id']);
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class)
            ->post(route('login.attempt'), ['username' => $lain->username, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($lain);
    }

    public function test_manajemen_pengguna_memvalidasi_username_dan_menyimpan_lowercase(): void
    {
        $admin = User::factory()->create(['username' => 'administrator', 'role' => 'admin']);
        $base = [
            'name' => 'Pengguna Baru', 'username' => 'Pengguna-Baru', 'email' => 'baru@example.test',
            'role' => 'petugas', 'password' => 'Rahasia-Uji-12345', 'password_confirmation' => 'Rahasia-Uji-12345',
        ];
        $this->withSession(['auth.password_confirmed_at' => time()])->actingAs($admin)
            ->post(route('users.store'), $base)->assertSessionHasNoErrors();
        $user = User::where('email', $base['email'])->firstOrFail();
        $this->assertSame('pengguna-baru', $user->username);

        foreach (['ab', str_repeat('a', 31), '-abc', 'abc-', 'A B', 'admin@x', 'budi_é', '😀abc'] as $invalid) {
            $payload = $base;
            $payload['username'] = $invalid;
            $payload['email'] = uniqid().'@example.test';
            $this->withSession(['auth.password_confirmed_at' => time()])->actingAs($admin)
                ->post(route('users.store'), $payload)->assertSessionHasErrors('username');
        }

        $duplicate = $base;
        $duplicate['username'] = 'PENGGUNA-BARU';
        $duplicate['email'] = 'duplikat@example.test';
        $this->withSession(['auth.password_confirmed_at' => time()])->actingAs($admin)
            ->post(route('users.store'), $duplicate)->assertSessionHasErrors('username');

        $update = $base;
        $update['username'] = $admin->username;
        $update['email'] = $user->email;
        $this->withSession(['auth.password_confirmed_at' => time()])->actingAs($admin)
            ->put(route('users.update', $user), $update)->assertSessionHasErrors('username');

        $self = $admin->toArray();
        $self['username'] = 'ADMINISTRATOR';
        $self['email'] = $admin->email;
        $self['password'] = '';
        $self['password_confirmation'] = '';
        $this->withSession(['auth.password_confirmed_at' => time()])->actingAs($admin)
            ->put(route('users.update', $admin), $self)->assertSessionHasNoErrors();
    }

    public function test_username_backfill_menghasilkan_nilai_valid_unik_dan_maksimal_30(): void
    {
        $this->assertSame('budi.santoso', UsernameBackfill::dari('budi.santoso@x.go.id', []));
        $this->assertMatchesRegularExpression('/^[a-z0-9](?:[a-z0-9._-]{1,28})[a-z0-9]$/', UsernameBackfill::dari('a@x.id', []));
        $this->assertSame('budi.tes', UsernameBackfill::dari('Budi+Tes@x.id', []));
        $this->assertLessThanOrEqual(30, strlen(UsernameBackfill::dari(str_repeat('a', 60).'@x.id', [])));
        $this->assertSame('admin', UsernameBackfill::dari('admin@perumahan.test', []));
        $this->assertSame('admin2', UsernameBackfill::dari('admin@x.test', ['admin']));
        $this->assertSame('admin3', UsernameBackfill::dari('admin@x.test', ['admin', 'admin2']));
    }

    public function test_migrasi_backfill_user_lama_secara_langsung(): void
    {
        Schema::table('users', function ($table) {
            $table->dropUnique(['username']);
            $table->dropColumn('username');
        });
        DB::table('users')->insert([
            'name' => 'Lama Satu', 'email' => 'budi.santoso@x.go.id', 'password' => 'x',
            'created_at' => now(), 'updated_at' => now(), 'role' => 'petugas', 'is_active' => true,
        ]);
        DB::table('users')->insert([
            'name' => 'Lama Dua', 'email' => 'budi.santoso@contoh.test', 'password' => 'x',
            'created_at' => now(), 'updated_at' => now(), 'role' => 'petugas', 'is_active' => true,
        ]);

        $migration = require database_path('migrations/2026_10_29_000001_add_username_to_users_table.php');
        $migration->up();

        $this->assertDatabaseHas('users', ['email' => 'budi.santoso@x.go.id', 'username' => 'budi.santoso']);
        $this->assertDatabaseHas('users', ['email' => 'budi.santoso@contoh.test', 'username' => 'budi.santoso2']);
        $this->assertFalse(Schema::getColumnType('users', 'username') === 'text');
    }

    public function test_factory_dan_seeder_menghasilkan_username(): void
    {
        $users = User::factory()->count(50)->create();
        $this->assertCount(50, $users->pluck('username')->unique());
        foreach ($users as $user) {
            $this->assertMatchesRegularExpression('/^[a-z0-9](?:[a-z0-9._-]{1,28})[a-z0-9]$/', $user->username);
        }

        (new UserSeeder)->run();
        $this->assertDatabaseHas('users', ['username' => 'admin', 'email' => 'admin@perumahan.test']);
        $this->assertDatabaseHas('users', ['username' => 'petugas', 'email' => 'petugas@perumahan.test']);
    }

    public function test_halaman_login_dan_users_menampilkan_username(): void
    {
        $this->get(route('login'))->assertOk()
            ->assertSee('name="username"', false)
            ->assertSee('autocomplete="username"', false)
            ->assertDontSee('type="email"', false);

        $admin = User::factory()->create(['username' => 'adminview', 'role' => 'admin']);
        $this->actingAs($admin)->get(route('users.index'))->assertOk()->assertSee($admin->username);
    }
}
