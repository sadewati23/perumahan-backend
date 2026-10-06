<?php

namespace App\Providers;

use App\Models\Laporan;
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
        View::composer('layouts.app', function ($view): void {
            $view->with('jumlahLaporanBaru', Laporan::where('status', 'baru')->count());
        });
    }
}
