<?php

namespace App\Http\Controllers;

use App\Http\Requests\CariLaporanRequest;
use App\Models\Laporan;
use App\Support\TurnstileKonfigurasi;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Response;
use Illuminate\View\View;

class CekLaporanController extends Controller
{
    private const PESAN = 'Kode laporan tidak ditemukan atau sudah melewati masa pencarian.';

    public function index(): View
    {
        TurnstileKonfigurasi::catat();

        return view('lapor.cek');
    }

    public function cari(CariLaporanRequest $request): View|RedirectResponse|Response
    {
        if ($request->filled('jawaban_cadangan')) return back()->withInput()->withErrors(['kode' => self::PESAN]);
        $laporan = Laporan::dapatDicari()
            ->with(['perumahan', 'kelurahan.kecamatan', 'fotosPerbaikan'])
            ->where('kode', $request->validated('kode'))
            ->first();
        if (! $laporan) return back()->withInput()->withErrors(['kode' => self::PESAN]);
        return response()->view('lapor.status', compact('laporan'))
            ->header('Cache-Control', 'no-store, private')->header('X-Robots-Tag', 'noindex, nofollow')
            ->header('Referrer-Policy', 'no-referrer');
    }
}
