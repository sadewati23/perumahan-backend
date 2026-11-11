<?php

namespace Tests\Feature\Publik;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AksesDasarTest extends TestCase
{
    use RefreshDatabase;

    public function test_beranda_publik_bisa_diakses_tanpa_login(): void
    {
        $this->get('/')->assertOk()->assertSee('Informasi Data Perumahan');
    }

    public function test_halaman_admin_mengarahkan_tamu_ke_login(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->get(route('perumahan.index'))->assertRedirect(route('login'));

        $this->get('/login')->assertOk()->assertSee('Masuk');
    }
}
