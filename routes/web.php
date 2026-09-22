<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KecamatanController;
use App\Http\Controllers\KelurahanController;
use App\Http\Controllers\PerumahanController;
use App\Http\Controllers\PublikController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Portal Publik (tanpa login)
|--------------------------------------------------------------------------
*/
Route::get('/', [PublikController::class, 'beranda'])->name('publik.beranda');
Route::get('/perumahan', [PublikController::class, 'index'])->name('publik.perumahan.index');
Route::get('/perumahan/{perumahan}', [PublikController::class, 'show'])->name('publik.perumahan.show');
Route::get('/peta', [PublikController::class, 'peta'])->name('publik.peta');
Route::get('/peta/geojson', [PublikController::class, 'geojson'])->name('publik.peta.geojson');

/*
|--------------------------------------------------------------------------
| Autentikasi
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');
});

/*
|--------------------------------------------------------------------------
| Area Admin (butuh login)
|--------------------------------------------------------------------------
*/
Route::middleware('auth')->prefix('admin')->group(function () {
    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Peta & titik GIS (didaftarkan sebelum resource agar tidak terbaca sebagai parameter)
    Route::get('/perumahan/peta', [PerumahanController::class, 'peta'])->name('perumahan.peta');
    Route::get('/perumahan/geojson', [PerumahanController::class, 'geojson'])->name('perumahan.geojson');

    Route::resource('perumahan', PerumahanController::class);

    // Master wilayah
    Route::get('/wilayah', [KecamatanController::class, 'index'])->name('wilayah.index');
    Route::post('/wilayah/kecamatan', [KecamatanController::class, 'store'])->name('kecamatan.store');
    Route::put('/wilayah/kecamatan/{kecamatan}', [KecamatanController::class, 'update'])->name('kecamatan.update');
    Route::delete('/wilayah/kecamatan/{kecamatan}', [KecamatanController::class, 'destroy'])->name('kecamatan.destroy');

    Route::post('/wilayah/kelurahan', [KelurahanController::class, 'store'])->name('kelurahan.store');
    Route::put('/wilayah/kelurahan/{kelurahan}', [KelurahanController::class, 'update'])->name('kelurahan.update');
    Route::delete('/wilayah/kelurahan/{kelurahan}', [KelurahanController::class, 'destroy'])->name('kelurahan.destroy');

    // Khusus administrator
    Route::middleware('role:admin')->prefix('pengguna')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::post('/', [UserController::class, 'store'])->name('store');
        Route::put('/{user}', [UserController::class, 'update'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->name('destroy');
    });
});
