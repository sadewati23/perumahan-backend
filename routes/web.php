<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\KecamatanController;
use App\Http\Controllers\KelurahanController;
use App\Http\Controllers\LaporanAdminController;
use App\Http\Controllers\LaporanController;
use App\Http\Controllers\LaporanPerbaikanController;
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

// Laporan kerusakan sarana-prasarana dari publik (tanpa login)
Route::get('/lapor', [LaporanController::class, 'create'])->name('lapor.create');
Route::post('/lapor', [LaporanController::class, 'store'])->middleware('throttle:lapor')->name('lapor.store');

/*
|--------------------------------------------------------------------------
| Autentikasi
|--------------------------------------------------------------------------
*/
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');

    // Lupa password
    Route::get('/lupa-password', [AuthController::class, 'showForgotPassword'])->name('password.request');
    Route::post('/lupa-password', [AuthController::class, 'sendResetLink'])->name('password.email')->middleware('throttle:5,1');
    Route::get('/reset-password/{token}', [AuthController::class, 'showResetPassword'])->name('password.reset');
    Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.update')->middleware('throttle:5,1');
});

/*
|--------------------------------------------------------------------------
| Area Admin (butuh login + akun aktif)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active'])->prefix('admin')->group(function () {
    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Peta & titik GIS (didaftarkan sebelum resource agar tidak terbaca sebagai parameter)
    Route::get('/perumahan/peta', [PerumahanController::class, 'peta'])->name('perumahan.peta');
    Route::get('/perumahan/geojson', [PerumahanController::class, 'geojson'])->name('perumahan.geojson');

    Route::resource('perumahan', PerumahanController::class);

    // Laporan kerusakan sarana-prasarana
    Route::get('/laporan', [LaporanAdminController::class, 'index'])->name('laporan.index');

    // Rute statis /laporan/... didaftarkan SEBELUM rute parametrik /laporan/{laporan}
    // agar tidak tertangkap sebagai parameter {laporan}.
    Route::get('/laporan/cetak-rekap', [LaporanAdminController::class, 'cetakRekap'])->name('laporan.cetak-rekap');
    Route::get('/laporan/{laporan}/cetak', [LaporanAdminController::class, 'cetak'])->name('laporan.cetak');

    // Foto hasil perbaikan petugas (dipisah dari foto bukti warga).
    Route::post('/laporan/{laporan}/foto-perbaikan', [LaporanPerbaikanController::class, 'store'])->name('laporan.foto-perbaikan.store');
    Route::delete('/laporan/{laporan}/foto-perbaikan/{foto}', [LaporanPerbaikanController::class, 'destroy'])->name('laporan.foto-perbaikan.destroy');

    Route::get('/laporan/{laporan}', [LaporanAdminController::class, 'show'])->name('laporan.show');
    Route::put('/laporan/{laporan}', [LaporanAdminController::class, 'update'])->name('laporan.update');
    Route::delete('/laporan/{laporan}', [LaporanAdminController::class, 'destroy'])->name('laporan.destroy');

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