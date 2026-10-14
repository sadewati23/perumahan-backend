<?php

namespace App\Providers;

use App\Models\Laporan;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Jumlah laporan berstatus "baru" (semua jenis) untuk badge sidebar area admin.
        // Di-cache singkat agar tidak menambah satu query count() di setiap halaman admin;
        // cache di-reset di LaporanAdminController setiap status laporan berubah.
        View::composer('layouts.app', function ($view): void {
            $view->with('jumlahLaporanBaru', Cache::remember(
                Laporan::CACHE_BAIK_BARU,
                now()->addMinutes(5),
                fn () => Laporan::where('status', 'baru')->count()
            ));
        });

        // Form lapor publik rawan spam: batasi per menit (mencegah burst)
        // dan per hari (mencegah kiriman massal dari satu IP), dihitung per IP.
        RateLimiter::for('lapor', fn (Request $request) => [
            Limit::perMinute(10)->by($request->ip()),
            Limit::perDay(30)->by($request->ip()),
        ]);
    }
}
