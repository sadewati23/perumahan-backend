<?php

use App\Http\Controllers\Publik\CekLaporanController;
use App\Http\Controllers\Publik\LaporanController;
use App\Http\Controllers\Publik\PublikController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Portal Publik (tanpa login)
|--------------------------------------------------------------------------
*/
Route::get('/', [PublikController::class, 'beranda'])->middleware('throttle:publik')->name('publik.beranda');
Route::get('/perumahan', [PublikController::class, 'index'])->middleware('throttle:publik')->name('publik.perumahan.index');
Route::get('/perumahan/{perumahan}', [PublikController::class, 'show'])->middleware('throttle:publik')->name('publik.perumahan.show');
Route::get('/peta', [PublikController::class, 'peta'])->middleware('throttle:publik')->name('publik.peta');
Route::get('/peta/geojson', [PublikController::class, 'geojson'])->middleware('throttle:geojson')->name('publik.peta.geojson');

// Laporan kerusakan sarana-prasarana dari publik (tanpa login)
Route::get('/lapor', [LaporanController::class, 'create'])->middleware('throttle:publik')->name('lapor.create');
Route::post('/lapor', [LaporanController::class, 'store'])->middleware('throttle:lapor')->name('lapor.store');
Route::get('/cek-laporan', [CekLaporanController::class, 'index'])->middleware('throttle:publik')->name('cek-laporan.index');
Route::post('/cek-laporan', [CekLaporanController::class, 'cari'])->middleware('throttle:cek-laporan')->name('cek-laporan.cari');
