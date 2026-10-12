<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class AkunNonaktifTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_nonaktif_langsung_dikeluarkan_dari_sesi_aktif(): void
    {
        $user = User::factory()->create(['role' => 'petugas', 'is_active' => true]);

        $this->actingAs($user)->get('/admin/dashboard')->assertOk();

        $user->update(['is_active' => false]);

        $this->actingAs($user->fresh())
            ->get('/admin/dashboard')
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_user_nonaktif_tidak_bisa_login(): void
    {
        $user = User::factory()->create([
            'role' => 'petugas',
            'is_active' => false,
            'password' => 'rahasia-123',
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'rahasia-123',
        ])->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_user_aktif_tetap_bisa_login(): void
    {
        $user = User::factory()->create([
            'role' => 'petugas',
            'is_active' => true,
            'password' => 'rahasia-123',
        ]);

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'rahasia-123',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_menonaktifkan_user_mencabut_sesinya(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'is_active' => true]);
        $petugas = User::factory()->create(['role' => 'petugas', 'is_active' => true]);

        DB::table('sessions')->insert([
            'id' => 'sesi-petugas',
            'user_id' => $petugas->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'phpunit',
            'payload' => '',
            'last_activity' => now()->timestamp,
        ]);

        $this->actingAs($admin)->put(route('users.update', $petugas), [
            'name' => $petugas->name,
            'email' => $petugas->email,
            'role' => 'petugas',
            // is_active tidak dikirim => false
        ])->assertSessionHas('success');

        $this->assertDatabaseMissing('sessions', ['id' => 'sesi-petugas']);
    }
}