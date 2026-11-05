<?php

namespace Tests\Feature\Konfigurasi;

use App\Models\Laporan;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ZonaWaktuTest extends TestCase
{
    use RefreshDatabase;

    public function test_timezone_dan_bahasa_indonesia_digunakan(): void
    {
        $this->assertSame('Asia/Jakarta', config('app.timezone'));
        $this->assertSame('Oktober', Carbon::create(2026, 10, 19)->translatedFormat('F'));
    }

    public function test_kode_laporan_memakai_tanggal_wib(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 10, 19, 5, 0, 0, 'Asia/Jakarta'));

        try {
            $this->assertStringContainsString('20261019', Laporan::buatKode());
        } finally {
            Carbon::setTestNow();
        }
    }
}
