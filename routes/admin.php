<?php

use App\Http\Controllers\Admin\Auth\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\KecamatanController;
use App\Http\Controllers\Admin\KelurahanController;
use App\Http\Controllers\Admin\LaporanController;
use App\Http\Controllers\Admin\LaporanPerbaikanController;
use App\Http\Controllers\Admin\Auth\KonfirmasiPasswordController;
use App\Http\Controllers\Admin\PerumahanController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Area Admin (butuh login + akun aktif)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'active', 'tanpa-cache'])->prefix('admin')->group(function () {
    Route::get('/', fn () => redirect()->route('dashboard'));
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    Route::get('/konfirmasi-password', [KonfirmasiPasswordController::class, 'create'])->name('password.confirm');
    Route::post('/konfirmasi-password', [KonfirmasiPasswordController::class, 'store'])
        ->middleware('throttle:6,1')->name('password.confirm.store');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Peta & titik GIS (didaftarkan sebelum resource agar tidak terbaca sebagai parameter)
    Route::get('/perumahan/peta', [PerumahanController::class, 'peta'])->name('perumahan.peta');
    Route::get('/perumahan/geojson', [PerumahanController::class, 'geojson'])->name('perumahan.geojson');

    Route::resource('perumahan', PerumahanController::class)->except('destroy');
    Route::delete('/perumahan/{perumahan}', [PerumahanController::class, 'destroy'])
        ->name('perumahan.destroy')->middleware(['role:admin', 'simpan-tujuan-konfirmasi', 'password.confirm']);

    // Laporan kerusakan sarana-prasarana
    Route::get('/laporan', [LaporanController::class, 'index'])->name('laporan.index');

    // Rute statis /laporan/... didaftarkan SEBELUM rute parametrik /laporan/{laporan}
    // agar tidak tertangkap sebagai parameter {laporan}.
    Route::get('/laporan/cetak-rekap', [LaporanController::class, 'cetakRekap'])->name('laporan.cetak-rekap');
    Route::get('/laporan/cetak-rekap/word', [LaporanController::class, 'wordRekap'])->name('laporan.cetak-rekap.word');
    Route::get('/laporan/{laporan}/cetak', [LaporanController::class, 'cetak'])->name('laporan.cetak');
    Route::get('/laporan/{laporan}/word', [LaporanController::class, 'word'])->name('laporan.word');

    // Foto hasil perbaikan petugas (dipisah dari foto bukti warga).
    Route::post('/laporan/{laporan}/foto-perbaikan', [LaporanPerbaikanController::class, 'store'])->name('laporan.foto-perbaikan.store');
    Route::delete('/laporan/{laporan}/foto-perbaikan/{foto}', [LaporanPerbaikanController::class, 'destroy'])->name('laporan.foto-perbaikan.destroy');

    Route::get('/laporan/{laporan}', [LaporanController::class, 'show'])->name('laporan.show');
    Route::put('/laporan/{laporan}', [LaporanController::class, 'update'])->name('laporan.update');
    Route::delete('/laporan/{laporan}', [LaporanController::class, 'destroy'])->name('laporan.destroy')->middleware(['role:admin', 'simpan-tujuan-konfirmasi', 'password.confirm']);

    // Master wilayah
    Route::get('/wilayah', [KecamatanController::class, 'index'])->name('wilayah.index');
    Route::post('/wilayah/kecamatan', [KecamatanController::class, 'store'])->name('kecamatan.store');
    Route::put('/wilayah/kecamatan/{kecamatan}', [KecamatanController::class, 'update'])->name('kecamatan.update');
    Route::delete('/wilayah/kecamatan/{kecamatan}', [KecamatanController::class, 'destroy'])->name('kecamatan.destroy')->middleware(['role:admin', 'simpan-tujuan-konfirmasi', 'password.confirm']);

    Route::post('/wilayah/kelurahan', [KelurahanController::class, 'store'])->name('kelurahan.store');
    Route::put('/wilayah/kelurahan/{kelurahan}', [KelurahanController::class, 'update'])->name('kelurahan.update');
    Route::delete('/wilayah/kelurahan/{kelurahan}', [KelurahanController::class, 'destroy'])->name('kelurahan.destroy')->middleware(['role:admin', 'simpan-tujuan-konfirmasi', 'password.confirm']);

    // Khusus administrator
    Route::middleware('role:admin')->prefix('pengguna')->name('users.')->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');
        Route::post('/', [UserController::class, 'store'])->middleware(['simpan-tujuan-konfirmasi', 'password.confirm'])->name('store');
        Route::put('/{user}', [UserController::class, 'update'])->middleware(['simpan-tujuan-konfirmasi', 'password.confirm'])->name('update');
        Route::delete('/{user}', [UserController::class, 'destroy'])->middleware(['simpan-tujuan-konfirmasi', 'password.confirm'])->name('destroy');
    });
});
